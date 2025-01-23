<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\UpdateInventorySummaryJob;
use Illuminate\Support\Facades\Cache;
use App\Models\JobProgress;

class InventoryController extends Controller
{
    public function updateInventorySummary(Request $request)
    {
        // Check if there's already a job running
        $runningJob = JobProgress::where('job_type', 'inventory_summary_update')
            ->whereIn('status', ['processing', 'queued'])
            ->first();

        if ($runningJob) {
            return response()->json([
                'status' => 'error',
                'message' => 'Another inventory update job is already running',
                'job_progress' => $runningJob
            ], 409);
        }

        // Dispatch new job
        $isRecreate = $request->input('recreate', '0');
        UpdateInventorySummaryJob::dispatch($isRecreate === '1' ? true : false);

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory update job has been queued'
        ]);
    }

    public function getJobProgress()
    {
        $latestJob = JobProgress::where('job_type', 'inventory_summary_update')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestJob) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No inventory update jobs found'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $latestJob
        ]);
    }

    public function cancelJob(Request $request)
    {
        $runningJob = JobProgress::where('job_type', 'inventory_summary_update')
            ->whereIn('status', ['processing', 'queued'])
            ->first();

        if (!$runningJob) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active inventory update job found to cancel'
            ], 404);
        }

        // Set cache flag for cancellation
        Cache::put("job_cancelled_{$runningJob->id}", true, now()->addHours(3));

        // Update the job status
        $runningJob->update([
            'status' => 'cancelled',
            'completed_at' => now(),
            'error_message' => 'Job cancelled by user'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory update job has been cancelled',
            'data' => $runningJob
        ]);
    }
}
