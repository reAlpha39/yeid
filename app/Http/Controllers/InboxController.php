<?php

namespace App\Http\Controllers;

use App\Services\InboxService;
use App\Models\Inbox;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    private $inboxService;

    public function __construct(InboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    public function index(Request $request)
    {
        // $query = Inbox::with(['source'])
        //     ->where('user_id', $request->user()->id);
        $query = Inbox::where('user_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        $perPage = $request->input('per_page', 15);
        $messages = $query->latest()->paginate($perPage);

        return response()->json($messages);
    }

    public function markAsRead(Request $request, $id)
    {
        $inbox = Inbox::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $inbox->markAsRead();

        return response()->json(['message' => 'Message marked as read']);
    }

    public function archive(Request $request, $id)
    {
        $inbox = Inbox::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $inbox->archive();

        return response()->json(['message' => 'Message archived']);
    }

    public function getUnreadCount(Request $request)
    {
        $count = $this->inboxService->getUserUnreadCount($request->user()->id);
        return response()->json(['unread_count' => $count]);
    }

    public function batchMarkAsRead(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        Inbox::where('user_id', $request->user()->id)
            ->whereIn('id', $request->ids)
            ->update([
                'status' => 'read',
                'read_at' => now()
            ]);

        return response()->json(['message' => 'Messages marked as read']);
    }

    public function batchArchive(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        Inbox::where('user_id', $request->user()->id)
            ->whereIn('id', $request->ids)
            ->update([
                'status' => 'archived',
                'archived_at' => now()
            ]);

        return response()->json(['message' => 'Messages archived']);
    }
}
