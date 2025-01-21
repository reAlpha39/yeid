<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\UpdateInventorySummaryJob;
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
        $isShiftUpdate = $request->input('shift_flag', false);
        UpdateInventorySummaryJob::dispatch($isShiftUpdate);

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
}
