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

class UpdateInventorySummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $jobProgressId;
    private $isRecreate;
    public $timeout = 7200; // 2 hours
    public $tries = 1;

    private $shouldCancel = false;

    public function __construct(bool $isRecreate)
    {
        $this->isRecreate = $isRecreate;
    }

    public function middleware()
    {
        return [new CheckJobCancellation];
    }

    private function checkIfCancelled(): bool
    {
        return Cache::get("job_cancelled_{$this->jobProgressId}", false);
    }

    private function cleanupOldCancelledJobs()
    {
        // Find all cancelled jobs older than 24 hours
        JobProgress::where('status', 'cancelled')
            ->where('completed_at', '<', now()->subDay())
            ->get()
            ->each(function ($job) {
                Cache::forget("job_cancelled_{$job->id}");
            });
    }

    public function handle()
    {
        try {
            // Clean up old cancelled jobs first
            $this->cleanupOldCancelledJobs();

            // Create progress record
            $jobProgress = JobProgress::create([
                'job_type' => 'inventory_summary_update',
                'status' => 'processing',
                'progress' => 0,
                'total_items' => 0,
                'processed_items' => 0,
                'started_at' => now(),
            ]);

            $this->jobProgressId = $jobProgress->id;

            Cache::put("job_cancelled_{$this->jobProgressId}", false, now()->addHours(3));

            // Get part codes
            $partCodes = DB::table('mas_inventory')
                ->select('partcode')
                ->orderBy('partcode')
                ->get()
                ->pluck('partcode');

            if ($partCodes->isEmpty()) {
                $this->updateProgress(100, 0, 0, 'completed', 'No parts found');
                return;
            }

            $totalParts = $partCodes->count();
            $this->updateProgress(0, $totalParts, 0, 'processing');

            // Get minimum date
            $minDate = $this->getMinimumDate();

            // Process in chunks
            $processedCount = 0;
            $errors = [];

            foreach ($partCodes->chunk(100) as $partCodeChunk) {
                // Cancellation check
                if ($this->checkIfCancelled()) {
                    $this->updateProgress(
                        ($processedCount / $totalParts) * 100,
                        $totalParts,
                        $processedCount,
                        'cancelled',
                        'Job was cancelled by user'
                    );
                    return;
                }

                foreach ($partCodeChunk as $partCode) {
                    // Cancellation check
                    if ($this->checkIfCancelled()) {
                        $this->updateProgress(
                            ($processedCount / $totalParts) * 100,
                            $totalParts,
                            $processedCount,
                            'cancelled',
                            'Job was cancelled by user'
                        );
                        return;
                    }

                    $success = $this->processPartInventory($partCode, $minDate, $this->isRecreate);

                    if (!$success) {
                        $errors[] = $partCode;
                    }

                    $processedCount++;

                    // Update progress
                    $progress = ($processedCount / $totalParts) * 100;
                    $this->updateProgress(
                        $progress,
                        $totalParts,
                        $processedCount,
                        'processing',
                        $errors ? json_encode($errors) : null
                    );
                }

                // Release memory
                DB::disconnect();
            }

            // Update final status
            $finalStatus = empty($errors) ? 'completed' : 'completed_with_errors';
            $this->updateProgress(
                100,
                $totalParts,
                $processedCount,
                $finalStatus,
                $errors ? json_encode($errors) : null
            );
        } catch (\Exception $e) {
            $this->updateProgress(
                0,
                0,
                0,
                'failed',
                $e->getMessage()
            );

            throw $e;
        } finally {
            Cache::forget("job_cancelled_{$this->jobProgressId}");
        }
    }

    private function updateProgress(
        float $progress,
        int $totalItems,
        int $processedItems,
        string $status,
        ?string $error = null
    ) {
        JobProgress::where('id', $this->jobProgressId)->update([
            'progress' => $progress,
            'total_items' => $totalItems,
            'processed_items' => $processedItems,
            'status' => $status,
            'error_message' => $error,
            'completed_at' => in_array($status, ['completed', 'completed_with_errors', 'failed']) ? now() : null
        ]);
    }

    /**
     * Get minimum date for inventory calculations
     */
    private function getMinimumDate(): string
    {
        $result = DB::table('tbl_invrecord')
            ->select(DB::raw('MIN(jobdate) as min_date'))
            ->first();

        if (!$result || !$result->min_date) {
            return Carbon::now()->subMonth()->format('Ymd');
        }

        return $result->min_date;
    }

    /**
     * Process inventory for a single part
     */
    private function processPartInventory(string $partCode, string $minDate, bool $isRecreate): bool
    {
        try {
            // Check for cancellation
            if ($this->checkIfCancelled()) {
                return false;
            }

            DB::beginTransaction();

            // Get last stock info
            $inventory = DB::table('mas_inventory')
                ->select(['laststocknumber', 'laststockdate'])
                ->where('partcode', $partCode)
                ->first();

            if (!$inventory) {
                DB::rollBack();
                return false;
            }

            $stockDate = Carbon::createFromFormat('Ymd', $inventory->laststockdate);
            $currentMonth = Carbon::now()->startOfMonth();

            // Delete existing summary data if full update
            if ($isRecreate) {
                DB::table('tbl_invsummary')
                    ->where('partcode', $partCode)
                    ->delete();
            }

            // Calculate inventory movements
            $movements = DB::table('tbl_invrecord')
                ->select(
                    DB::raw("to_char(to_date(jobdate, 'YYYYMMDD'), 'YYYYMM') as yearmonth"),
                    DB::raw("SUM(CASE WHEN jobcode = 'I' THEN quantity ELSE 0 END) as inbound"),
                    DB::raw("SUM(CASE WHEN jobcode = 'O' THEN quantity ELSE 0 END) as outbound"),
                    DB::raw("SUM(CASE WHEN jobcode = 'A' THEN quantity ELSE 0 END) as adjust")
                )
                ->where('partcode', $partCode)
                ->where('jobdate', '>=', $stockDate->format('Ymd'))
                ->where('jobdate', '<', $currentMonth->format('Ymd'))
                ->groupBy(DB::raw("to_char(to_date(jobdate, 'YYYYMMDD'), 'YYYYMM')"))
                ->orderBy('yearmonth')
                ->get();

            // Process movements and update summary
            $stockNumber = $inventory->laststocknumber;

            foreach ($movements as $movement) {
                $stockNumber += ($movement->inbound - $movement->outbound + $movement->adjust);

                // Using updateOrInsert to handle both new records and updates
                DB::table('tbl_invsummary')->updateOrInsert(
                    [
                        'yearmonth' => $movement->yearmonth,
                        'partcode' => $partCode
                    ],
                    [
                        'laststocknumber' => $inventory->laststocknumber,
                        'laststockdate' => $inventory->laststockdate,
                        'inbound' => $movement->inbound,
                        'outbound' => $movement->outbound,
                        'adjust' => $movement->adjust,
                        'stocknumber' => $stockNumber,
                        'jobnumber' => 1,
                        'status' => 'C',
                        'updatetime' => now()
                    ]
                );
            }

            // If this is a Recreate, process historical data
            if ($isRecreate) {
                $this->processHistoricalData($partCode, $stockDate, $inventory);
            }

            // Check for cancellation
            if ($this->checkIfCancelled()) {
                DB::rollBack();
                return false;
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing part {$partCode}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process historical inventory data for a part
     */
    private function processHistoricalData(string $partCode, Carbon $stockDate, object $inventory): void
    {
        // Get historical movements
        $historicalMovements = DB::table('tbl_invrecord')
            ->select(
                DB::raw("to_char(to_date(jobdate, 'YYYYMMDD'), 'YYYYMM') as yearmonth"),
                DB::raw("SUM(CASE WHEN jobcode = 'I' THEN quantity ELSE 0 END) as inbound"),
                DB::raw("SUM(CASE WHEN jobcode = 'O' THEN quantity ELSE 0 END) as outbound"),
                DB::raw("SUM(CASE WHEN jobcode = 'A' THEN quantity ELSE 0 END) as adjust")
            )
            ->where('partcode', $partCode)
            ->where('jobdate', '<', $stockDate->format('Ymd'))
            ->where('jobdate', '>=', '20111101') // Historical start date
            ->groupBy(DB::raw("to_char(to_date(jobdate, 'YYYYMMDD'), 'YYYYMM')"))
            ->orderBy('yearmonth', 'desc')
            ->get();

        $stockNumber = $inventory->laststocknumber;

        // Process each historical month in reverse
        foreach ($historicalMovements as $movement) {
            // Subtract the movements since we're going backwards
            $stockNumber -= ($movement->inbound - $movement->outbound + $movement->adjust);

            DB::table('tbl_invsummary')->updateOrInsert(
                [
                    'yearmonth' => $movement->yearmonth,
                    'partcode' => $partCode
                ],
                [
                    'laststocknumber' => $inventory->laststocknumber,
                    'laststockdate' => $inventory->laststockdate,
                    'inbound' => $movement->inbound,
                    'outbound' => $movement->outbound,
                    'adjust' => $movement->adjust,
                    'stocknumber' => $stockNumber,
                    'jobnumber' => 0,
                    'status' => 'C',
                    'updatetime' => now()
                ]
            );
        }
    }
}
