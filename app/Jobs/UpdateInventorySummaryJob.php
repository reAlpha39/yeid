<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\JobProgress;
use Illuminate\Support\Facades\Log;
use App\Jobs\Middleware\CheckJobCancellation;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\LazyCollection;

class UpdateInventorySummaryJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  private const CHUNK_SIZE = 500;
  private const CACHE_TTL = 10800; // 3 hours in seconds
  private const BATCH_INSERT_SIZE = 1000;

  public $jobProgressId;
  private $isRecreate;
  private $monthsToProcess = [];

  public function __construct(bool $isRecreate)
  {
    $this->isRecreate = $isRecreate;
    $this->prepareMonthsToProcess();
  }

  public function middleware()
  {
    return [new CheckJobCancellation];
  }

  private function prepareMonthsToProcess(): void
  {
    $currentMonth = Carbon::now()->startOfMonth();
    $historicalStartDate = Carbon::createFromFormat('Ymd', '20111101');

    while ($historicalStartDate->lt($currentMonth)) {
      $this->monthsToProcess[] = $historicalStartDate->format('Ym');
      $historicalStartDate->addMonth();
    }

    // Add the current month
    $this->monthsToProcess[] = $currentMonth->format('Ym');
  }

  private function checkIfCancelled(): bool
  {
    return Cache::get("job_cancelled_{$this->jobProgressId}", false);
  }

  private function cleanupOldCancelledJobs(): void
  {
    JobProgress::where('status', 'cancelled')
      ->where('completed_at', '<', now()->subDay())
      ->lazyById(1000)
      ->each(function ($job) {
        Cache::forget("job_cancelled_{$job->id}");
      });
  }

  public function handle(): void
  {
    try {
      $this->initializeJob();
      $this->cleanupOldCancelledJobs();

      $partCodesQuery = DB::table('mas_inventory')
        ->select('partcode')
        ->orderBy('partcode');

      $totalParts = $partCodesQuery->count();

      if ($totalParts === 0) {
        $this->updateProgress(100, 0, 0, 'completed', 'No parts found');
        return;
      }

      $this->updateProgress(0, $totalParts, 0, 'processing');

      // Process parts in chunks using lazy loading
      $processedCount = 0;
      $errors = [];
      $batchData = [];

      $partCodesQuery->lazyById(self::CHUNK_SIZE, 'partcode')->each(function ($part) use (
        &$processedCount,
        &$errors,
        $totalParts,
        &$batchData
      ) {
        if ($this->checkIfCancelled()) {
          return false;
        }

        $partCode = $part->partcode;
        $inventoryData = $this->processPartInventory($partCode);

        if ($inventoryData === false) {
          $errors[] = $partCode;
        } else {
          $batchData = array_merge($batchData, $inventoryData);
        }

        $processedCount++;

        // Batch insert when threshold is reached
        if (count($batchData) >= self::BATCH_INSERT_SIZE) {
          $this->performBatchInsert($batchData);
          $batchData = [];
        }

        $this->updateProgress(
          ($processedCount / $totalParts) * 100,
          $totalParts,
          $processedCount,
          'processing',
          $errors ? json_encode($errors) : null
        );
      });

      // Insert remaining records
      if (!empty($batchData)) {
        $this->performBatchInsert($batchData);
      }

      $this->finishJob($totalParts, $processedCount, $errors);
    } catch (\Exception $e) {
      $this->handleError($e);
      throw $e;
    }
  }

  private function initializeJob(): void
  {
    $jobProgress = JobProgress::create([
      'job_type' => 'inventory_summary_update',
      'status' => 'processing',
      'progress' => 0,
      'total_items' => 0,
      'processed_items' => 0,
      'started_at' => now(),
    ]);

    $this->jobProgressId = $jobProgress->id;
    Cache::put(
      "job_cancelled_{$this->jobProgressId}",
      false,
      self::CACHE_TTL
    );
  }

  private function performBatchInsert(array $records): void
  {
    if ($this->isRecreate) {
      $partCodes = array_unique(array_column($records, 'partcode'));
      DB::table('tbl_invsummary')
        ->whereIn('partcode', $partCodes)
        ->delete();
    }

    DB::table('tbl_invsummary')
      ->insertOrIgnore($records);
  }

  private function processPartInventory(string $partCode): array|false
  {
    try {
      // Get inventory data
      $inventory = DB::table('mas_inventory')
        ->select('partcode', 'laststocknumber', 'laststockdate')
        ->where('partcode', $partCode)
        ->first();

      if (!$inventory) {
        // Log::warning("Inventory record not found for part: {$partCode}");
        return false;
      }

      $lastStockDate = $inventory->laststockdate;
      $lastStockNumber = floatval($inventory->laststocknumber);

      // Log::info("Processing part {$partCode}: last_stock={$lastStockNumber}, last_date={$lastStockDate}");

      // Get all movements for the part, ordered by date
      $movements = DB::table('tbl_invrecord')
        ->where('partcode', $partCode)
        ->select(
          'jobdate',
          DB::raw("substring(jobdate from 1 for 6) as yearmonth"),
          DB::raw("SUM(CASE WHEN jobcode = 'I' THEN quantity ELSE 0 END) as inbound"),
          DB::raw("SUM(CASE WHEN jobcode = 'O' THEN quantity ELSE 0 END) as outbound"),
          DB::raw("SUM(CASE WHEN jobcode = 'A' THEN quantity ELSE 0 END) as adjust")
        )
        ->groupBy('jobdate', DB::raw("substring(jobdate from 1 for 6)"))
        ->orderBy('jobdate')
        ->get();

      // Group movements by month for easier processing
      $monthlyMovements = [];
      foreach ($movements as $movement) {
        $yearMonth = $movement->yearmonth;
        if (!isset($monthlyMovements[$yearMonth])) {
          $monthlyMovements[$yearMonth] = [
            'inbound' => 0,
            'outbound' => 0,
            'adjust' => 0
          ];
        }
        $monthlyMovements[$yearMonth]['inbound'] += floatval($movement->inbound);
        $monthlyMovements[$yearMonth]['outbound'] += floatval($movement->outbound);
        $monthlyMovements[$yearMonth]['adjust'] += floatval($movement->adjust);
      }

      return $this->calculateInventoryFlow($partCode, $inventory, $monthlyMovements);
    } catch (\Exception $e) {
      // Log::error("Error processing part {$partCode}: " . $e->getMessage());
      return false;
    }
  }

  private function calculateInventoryFlow(string $partCode, $inventory, array $monthlyMovements): array
  {
    $records = [];
    $lastStockNumber = floatval($inventory->laststocknumber);
    $lastStockDate = $inventory->laststockdate;
    $currentMonth = Carbon::now()->format('Ym');

    // Convert lastStockDate to month format for comparison
    $lastStockMonth = $lastStockDate ? substr($lastStockDate, 0, 6) : null;

    // Generate all months from 201111 to current month
    $allMonths = $this->generateMonthRange('201111', $currentMonth);

    // Find the index of lastStockMonth in the array
    $lastStockMonthIndex = array_search($lastStockMonth, $allMonths);

    // Log::info("Part {$partCode}: Starting calculation with lastStock={$lastStockNumber}, lastDate={$lastStockDate}, lastMonth={$lastStockMonth}");

    // New approach: calculate chronologically and adjust starting point to match reference
    $stockLevels = [];

    if ($lastStockMonthIndex !== false) {
      // First, calculate what the stock would be at the reference point if we started from 0
      $testStock = 0;

      for ($i = 0; $i <= $lastStockMonthIndex; $i++) {
        $yearMonth = $allMonths[$i];
        $movements = $monthlyMovements[$yearMonth] ?? ['inbound' => 0, 'outbound' => 0, 'adjust' => 0];

        $inbound = floatval($movements['inbound']);
        $outbound = floatval($movements['outbound']);
        $adjust = floatval($movements['adjust']);

        if ($yearMonth === $lastStockMonth) {
          // For lastStockMonth, only count movements BEFORE the lastStockDate
          $movementsBeforeLastStock = DB::table('tbl_invrecord')
            ->where('partcode', $partCode)
            ->where('jobdate', '<=', $lastStockDate)
            ->where(DB::raw("substring(jobdate from 1 for 6)"), $lastStockMonth)
            ->selectRaw("
                            SUM(CASE WHEN jobcode = 'I' THEN quantity ELSE 0 END) as inbound,
                            SUM(CASE WHEN jobcode = 'O' THEN quantity ELSE 0 END) as outbound,
                            SUM(CASE WHEN jobcode = 'A' THEN quantity ELSE 0 END) as adjust
                        ")
            ->first();

          $beforeInbound = $movementsBeforeLastStock ? floatval($movementsBeforeLastStock->inbound) : 0;
          $beforeOutbound = $movementsBeforeLastStock ? floatval($movementsBeforeLastStock->outbound) : 0;
          $beforeAdjust = $movementsBeforeLastStock ? floatval($movementsBeforeLastStock->adjust) : 0;

          $testStock += $beforeInbound - $beforeOutbound + $beforeAdjust;
        } else {
          $testStock += $inbound - $outbound + $adjust;
        }
      }

      // Calculate the adjustment needed to match the reference point
      $adjustment = $lastStockNumber - $testStock;

      // Log::info("Part {$partCode}: Test stock at reference = {$testStock}, target = {$lastStockNumber}, adjustment = {$adjustment}");

      // Now calculate all stock levels using the adjusted starting point
      $runningStock = $adjustment;

      for ($i = 0; $i < count($allMonths); $i++) {
        $yearMonth = $allMonths[$i];
        $movements = $monthlyMovements[$yearMonth] ?? ['inbound' => 0, 'outbound' => 0, 'adjust' => 0];

        $inbound = floatval($movements['inbound']);
        $outbound = floatval($movements['outbound']);
        $adjust = floatval($movements['adjust']);

        if ($yearMonth === $lastStockMonth) {
          // Split movements for lastStockMonth
          $movementsBeforeLastStock = DB::table('tbl_invrecord')
            ->where('partcode', $partCode)
            ->where('jobdate', '<=', $lastStockDate)
            ->where(DB::raw("substring(jobdate from 1 for 6)"), $lastStockMonth)
            ->selectRaw("
                            SUM(CASE WHEN jobcode = 'I' THEN quantity ELSE 0 END) as inbound,
                            SUM(CASE WHEN jobcode = 'O' THEN quantity ELSE 0 END) as outbound,
                            SUM(CASE WHEN jobcode = 'A' THEN quantity ELSE 0 END) as adjust
                        ")
            ->first();

          $movementsAfterLastStock = DB::table('tbl_invrecord')
            ->where('partcode', $partCode)
            ->where('jobdate', '>', $lastStockDate)
            ->where(DB::raw("substring(jobdate from 1 for 6)"), $lastStockMonth)
            ->selectRaw("
                            SUM(CASE WHEN jobcode = 'I' THEN quantity ELSE 0 END) as inbound,
                            SUM(CASE WHEN jobcode = 'O' THEN quantity ELSE 0 END) as outbound,
                            SUM(CASE WHEN jobcode = 'A' THEN quantity ELSE 0 END) as adjust
                        ")
            ->first();

          $beforeInbound = $movementsBeforeLastStock ? floatval($movementsBeforeLastStock->inbound) : 0;
          $beforeOutbound = $movementsBeforeLastStock ? floatval($movementsBeforeLastStock->outbound) : 0;
          $beforeAdjust = $movementsBeforeLastStock ? floatval($movementsBeforeLastStock->adjust) : 0;

          $afterInbound = $movementsAfterLastStock ? floatval($movementsAfterLastStock->inbound) : 0;
          $afterOutbound = $movementsAfterLastStock ? floatval($movementsAfterLastStock->outbound) : 0;
          $afterAdjust = $movementsAfterLastStock ? floatval($movementsAfterLastStock->adjust) : 0;

          // Apply movements before lastStockDate
          $runningStock += $beforeInbound - $beforeOutbound + $beforeAdjust;

          // This should equal lastStockNumber at this point
          $stockLevels[$yearMonth] = $runningStock + $afterInbound - $afterOutbound + $afterAdjust;

          // Continue with movements after lastStockDate
          $runningStock += $afterInbound - $afterOutbound + $afterAdjust;
        } else {
          // Apply movements for this month
          $runningStock += $inbound - $outbound + $adjust;
          $stockLevels[$yearMonth] = $runningStock;
        }

        // Log::info("Chronological calc - {$yearMonth}: movements=+{$inbound}-{$outbound}+{$adjust}, stock={$stockLevels[$yearMonth]}");
      }
    }

    // Build the final records
    foreach ($allMonths as $yearMonth) {
      $movements = $monthlyMovements[$yearMonth] ?? ['inbound' => 0, 'outbound' => 0, 'adjust' => 0];

      // Always use ALL movements for the month in the display fields
      $inbound = floatval($movements['inbound']);
      $outbound = floatval($movements['outbound']);
      $adjust = floatval($movements['adjust']);

      $isCurrentPeriod = ($yearMonth === $currentMonth) ? 1 : 0;
      $endStock = $stockLevels[$yearMonth] ?? 0;

      $records[] = [
        'yearmonth' => $yearMonth,
        'partcode' => $partCode,
        'laststocknumber' => $inventory->laststocknumber,
        'laststockdate' => $inventory->laststockdate,
        'inbound' => $inbound,
        'outbound' => $outbound,
        'adjust' => $adjust,
        'stocknumber' => $endStock,
        'jobnumber' => $isCurrentPeriod,
        'status' => 'C',
        'updatetime' => now()
      ];
    }

    return $records;
  }

  private function generateMonthRange(string $startMonth, string $endMonth): array
  {
    $months = [];
    $current = Carbon::createFromFormat('Ym', $startMonth);
    $end = Carbon::createFromFormat('Ym', $endMonth);

    while ($current <= $end) {
      $months[] = $current->format('Ym');
      $current->addMonth();
    }

    return $months;
  }

  private function updateProgress(
    float $progress,
    int $totalItems,
    int $processedItems,
    string $status,
    ?string $error = null
  ): void {
    JobProgress::where('id', $this->jobProgressId)
      ->update([
        'progress' => $progress,
        'total_items' => $totalItems,
        'processed_items' => $processedItems,
        'status' => $status,
        'error_message' => $error,
        'completed_at' => in_array($status, ['completed', 'completed_with_errors', 'failed'])
          ? now()
          : null
      ]);
  }

  private function finishJob(int $totalParts, int $processedCount, array $errors): void
  {
    $finalStatus = empty($errors) ? 'completed' : 'completed_with_errors';
    $this->updateProgress(
      100,
      $totalParts,
      $processedCount,
      $finalStatus,
      $errors ? json_encode($errors) : null
    );
    Cache::forget("job_cancelled_{$this->jobProgressId}");
  }

  private function handleError(\Exception $e): void
  {
    $this->updateProgress(0, 0, 0, 'failed', $e->getMessage());
    Cache::forget("job_cancelled_{$this->jobProgressId}");
  }
}
