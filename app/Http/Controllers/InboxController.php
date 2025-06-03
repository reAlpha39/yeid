<?php

namespace App\Http\Controllers;

use App\Services\InboxService;
use App\Models\Inbox;
use Illuminate\Http\Request;
use Exception;

class InboxController extends Controller
{
    private $inboxService;

    public function __construct(InboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    public function index(Request $request)
    {
        try {
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

            // Pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $results = $query->latest()->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                    'next_page_url' => $results->nextPageUrl(),
                    'prev_page_url' => $results->previousPageUrl(),
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function markAsRead(Request $request, $id)
    {
        try {
            $inbox = Inbox::where('user_id', $request->user()->id)
                ->findOrFail($id);

            $inbox->markAsRead();

            return response()->json([
                'status' => true,
                'message' => 'Message marked as read',
                'data' => $inbox
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to mark message as read',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function archive(Request $request, $id)
    {
        try {
            $inbox = Inbox::where('user_id', $request->user()->id)
                ->findOrFail($id);

            $inbox->archive();

            return response()->json([
                'status' => true,
                'message' => 'Message archived',
                'data' => $inbox
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to archive message',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $inbox = Inbox::where('user_id', $request->user()->id)
                ->findOrFail($id);

            $inbox->delete();

            return response()->json([
                'status' => true,
                'message' => 'Message deleted',
                'data' => $inbox
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete message',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getUnreadCount(Request $request)
    {
        try {
            $count = $this->inboxService->getUserUnreadCount($request->user()->id);

            return response()->json([
                'status' => true,
                'data' => [
                    'unread_count' => $count
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get unread count',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function batchMarkAsRead(Request $request)
    {
        try {
            Inbox::where('user_id', $request->user()->id)
                ->where('status', '!=', 'read')
                ->update([
                    'status' => 'read',
                    'read_at' => now()
                ]);

            return response()->json([
                'status' => true,
                'message' => 'All messages marked as read'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to mark messages as read',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function batchArchive(Request $request)
    {
        try {
            $request->validate(['ids' => 'required|array']);

            Inbox::where('user_id', $request->user()->id)
                ->whereIn('id', $request->ids)
                ->update([
                    'status' => 'archived',
                    'archived_at' => now()
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Messages archived'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to archive messages',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function batchDestroy(Request $request)
    {
        try {
            $request->validate(['ids' => 'required|array']);

            Inbox::where('user_id', $request->user()->id)
                ->whereIn('id', $request->ids)
                ->update([
                    'status' => 'archived',
                    'deleted_at' => now()
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Messages deleted'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete messages',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
