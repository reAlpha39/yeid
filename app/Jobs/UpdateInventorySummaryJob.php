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
            $minDate = $this->getMinimumDate();

            // Process parts in chunks using lazy loading
            $processedCount = 0;
            $errors = [];
            $batchData = [];

            $partCodesQuery->lazyById(self::CHUNK_SIZE, 'partcode')->each(function ($part) use (
                $minDate,
                &$processedCount,
                &$errors,
                $totalParts,
                &$batchData
            ) {
                if ($this->checkIfCancelled()) {
                    return false;
                }

                $partCode = $part->partcode;
                $inventoryData = $this->processPartInventory($partCode, $minDate);

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

    private function processPartInventory(string $partCode, string $minDate): array|false
    {
        try {
            // Get inventory data
            $inventory = DB::table('mas_inventory')
                ->select('partcode', 'laststocknumber', 'laststockdate')
                ->where('partcode', $partCode)
                ->first();

            if (!$inventory) {
                Log::warning("Inventory record not found for part: {$partCode}");
                return false;
            }

            $lastStockDate = $inventory->laststockdate;
            $lastStockNumber = floatval($inventory->laststocknumber);

            if (is_null($lastStockDate) || is_null($lastStockNumber)) {
                Log::warning("Part {$partCode} has null stock data");
                return false;
            }

            // Validate date format (should be YYYYMMDD)
            if (strlen($lastStockDate) !== 8 || !is_numeric($lastStockDate)) {
                Log::warning("Part {$partCode} has invalid date format: {$lastStockDate}");
                return false;
            }

            $lastStockMonth = substr($lastStockDate, 0, 6); // 202505 in your example

            Log::info("Processing part {$partCode}: stock={$lastStockNumber}, date={$lastStockDate}, month={$lastStockMonth}");

            // Get all movements for the part at once
            $movements = DB::table('tbl_invrecord')
                ->where('partcode', $partCode)
                ->select(
                    DB::raw("substring(jobdate from 1 for 6) as yearmonth"),
                    DB::raw("SUM(CASE WHEN jobcode = 'I' THEN quantity ELSE 0 END) as inbound"),
                    DB::raw("SUM(CASE WHEN jobcode = 'O' THEN quantity ELSE 0 END) as outbound"),
                    DB::raw("SUM(CASE WHEN jobcode = 'A' THEN quantity ELSE 0 END) as adjust")
                )
                ->groupBy(DB::raw("substring(jobdate from 1 for 6)"))
                ->get()
                ->keyBy('yearmonth');

            // Generate all months from 201111 to current month
            $allMonths = $this->generateMonthRange('201111', $lastStockMonth);

            return $this->calculateStockLevels($partCode, $inventory, $movements, $allMonths, $lastStockMonth);
        } catch (\Exception $e) {
            Log::error("Error processing part {$partCode}: " . $e->getMessage());
            return false;
        }
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

    private function calculateStockLevels(string $partCode, $inventory, $movements, array $allMonths, string $lastStockMonth): array
    {
        $records = [];
        $lastStockNumber = floatval($inventory->laststocknumber);
        $lastStockDate = $inventory->laststockdate;

        // Start from the last stock month and work backward
        $runningStock = $lastStockNumber; // This is the current stock (end of last stock month)
        $monthIndex = array_search($lastStockMonth, $allMonths);

        // First, handle the last stock month
        if ($monthIndex !== false) {
            // Get full month movements for display
            $fullMonthMovement = $movements->get($lastStockMonth);
            $fullInbound = floatval($fullMonthMovement->inbound ?? 0);
            $fullOutbound = floatval($fullMonthMovement->outbound ?? 0);
            $fullAdjust = floatval($fullMonthMovement->adjust ?? 0);

            $records[$lastStockMonth] = [
                'yearmonth' => $lastStockMonth,
                'partcode' => $partCode,
                'laststocknumber' => $inventory->laststocknumber,
                'laststockdate' => $inventory->laststockdate,
                'inbound' => $fullInbound,
                'outbound' => $fullOutbound,
                'adjust' => $fullAdjust,
                'stocknumber' => $runningStock, // Current stock (end of this month)
                'jobnumber' => 1,
                'status' => 'C',
                'updatetime' => now()
            ];
        }

        // Now work backward through all previous months
        // The key insight: Stock at end of previous month = Stock at end of current month - movements during current month
        for ($i = $monthIndex - 1; $i >= 0; $i--) {
            $currentMonth = $allMonths[$i];     // Month we're calculating 
            $nextMonth = $allMonths[$i + 1];    // Month after the one we're calculating

            // Get movements for the NEXT month (not current month)
            $nextMonthMovement = $movements->get($nextMonth);
            $nextInbound = floatval($nextMonthMovement->inbound ?? 0);
            $nextOutbound = floatval($nextMonthMovement->outbound ?? 0);
            $nextAdjust = floatval($nextMonthMovement->adjust ?? 0);

            // Calculate stock at end of current month
            // Stock at end of current month = Stock at end of next month - movements during next month
            $runningStock = $runningStock - $nextInbound + $nextOutbound - $nextAdjust;

            // Get movements for the current month (for display purposes)
            $currentMonthMovement = $movements->get($currentMonth);
            $currentInbound = floatval($currentMonthMovement->inbound ?? 0);
            $currentOutbound = floatval($currentMonthMovement->outbound ?? 0);
            $currentAdjust = floatval($currentMonthMovement->adjust ?? 0);

            $records[$currentMonth] = [
                'yearmonth' => $currentMonth,
                'partcode' => $partCode,
                'laststocknumber' => $inventory->laststocknumber,
                'laststockdate' => $inventory->laststockdate,
                'inbound' => $currentInbound,
                'outbound' => $currentOutbound,
                'adjust' => $currentAdjust,
                'stocknumber' => $runningStock, // Stock at end of current month
                'jobnumber' => 0, // Historical data
                'status' => 'C',
                'updatetime' => now()
            ];
        }

        // Return records in chronological order
        ksort($records);
        return array_values($records);
    }

    private function getMovementsFromDate(string $partCode, string $fromDate): object
    {
        $movements = DB::table('tbl_invrecord')
            ->where('partcode', $partCode)
            ->where('jobdate', '>=', $fromDate)
            ->select(
                DB::raw("SUM(CASE WHEN jobcode = 'I' THEN quantity ELSE 0 END) as inbound"),
                DB::raw("SUM(CASE WHEN jobcode = 'O' THEN quantity ELSE 0 END) as outbound"),
                DB::raw("SUM(CASE WHEN jobcode = 'A' THEN quantity ELSE 0 END) as adjust")
            )
            ->first();

        return $movements ?? (object)['inbound' => 0, 'outbound' => 0, 'adjust' => 0];
    }

    private function getMinimumDate(): string
    {
        $result = DB::table('tbl_invrecord')
            ->select(DB::raw('MIN(jobdate) as min_date'))
            ->first();

        return $result && $result->min_date
            ? $result->min_date
            : Carbon::now()->subMonth()->format('Ymd');
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
