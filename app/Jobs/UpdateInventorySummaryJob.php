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
            $jobProgress = $this->initializeJob();
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

    private function initializeJob(): JobProgress
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

        return $jobProgress;
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
            $inventory = DB::table('mas_inventory')
                ->where('partcode', $partCode)
                ->first();

            if (!$inventory) {
                return false;
            }

            // Get all movements for the part at once
            $movements = DB::table('tbl_invrecord')
                ->where('partcode', $partCode)
                ->select(
                    DB::raw("substr(jobdate, 1, 6) as yearmonth"),
                    DB::raw("SUM(CASE WHEN jobcode = 'I' THEN quantity ELSE 0 END) as inbound"),
                    DB::raw("SUM(CASE WHEN jobcode = 'O' THEN quantity ELSE 0 END) as outbound"),
                    DB::raw("SUM(CASE WHEN jobcode = 'A' THEN quantity ELSE 0 END) as adjust")
                )
                ->groupBy(DB::raw("substr(jobdate, 1, 6)"))
                ->get()
                ->keyBy('yearmonth');

            $runningStock = 0;
            $batchRecords = [];

            foreach ($this->monthsToProcess as $yearMonth) {
                $movement = $movements->get($yearMonth);

                $inbound = floatval($movement->inbound ?? 0);
                $outbound = floatval($movement->outbound ?? 0);
                $adjust = floatval($movement->adjust ?? 0);

                $monthlyChange = $inbound - $outbound + $adjust;
                $runningStock += $monthlyChange;

                $batchRecords[] = [
                    'yearmonth' => $yearMonth,
                    'partcode' => $partCode,
                    'laststocknumber' => $inventory->laststocknumber,
                    'laststockdate' => $inventory->laststockdate,
                    'inbound' => $inbound,
                    'outbound' => $outbound,
                    'adjust' => $adjust,
                    'stocknumber' => $runningStock,
                    'jobnumber' => 1,
                    'status' => 'C',
                    'updatetime' => now()
                ];
            }

            return $batchRecords;
        } catch (\Exception $e) {
            Log::error("Error processing part {$partCode}: " . $e->getMessage());
            return false;
        }
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
