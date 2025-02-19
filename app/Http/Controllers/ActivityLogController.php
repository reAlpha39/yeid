<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    use PermissionCheckerTrait;

    public function downloadTodayLog()
    {
        if (!$this->checkAccess(['pressShotExcData', 'pressShotHistoryAct', 'pressShotMasterPart', 'pressShotPartList', 'pressShotProdData'], 'view')) {
            return $this->unauthorizedResponse();
        }

        // Get today's date in YYYYMMDD format
        $today = Carbon::now()->format('Ymd');
        $filename = $today . '.log';

        // Get today's logs
        $logs = ActivityLog::whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'asc')
            ->get();

        // Log content
        $content = '';
        foreach ($logs as $log) {
            $content .= sprintf(
                "[%s] User:%s Page:%s Action:%s Description:%s IP:%s\n",
                $log->created_at,
                $log->user_id,
                $log->page,
                $log->action,
                $log->description,
                $log->ip_address
            );
        }

        // Response with file download
        $headers = [
            'Content-type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($content)
        ];

        return Response::make($content, 200, $headers);
    }

    public function store(Request $request)
    {
        if (!$this->checkAccess(['pressShotExcData', 'pressShotHistoryAct', 'pressShotMasterPart', 'pressShotPartList', 'pressShotProdData'], ['view', 'create', 'update', 'delete'])) {
            return;
        }

        $request->validate([
            'page' => 'required|string',
            'action' => 'required|string',
            'description' => 'nullable|string'
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'page' => $request->page,
            'action' => $request->action,
            'description' => $request->description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity logged successfully',
        ]);
    }
}
