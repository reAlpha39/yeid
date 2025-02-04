<?php

namespace App\Services;

use App\Models\Inbox;
use App\Models\MasUser;
use App\Models\SpkRecord;
use Illuminate\Database\Eloquent\Model;

class InboxService
{
    public function createMessage(
        Model $source,
        MasUser $user,
        string $title,
        string $message,
        string $category,
        array $metadata = []
    ): Inbox {
        return Inbox::create([
            'source_type' => get_class($source),
            'source_id' => $source->getKey(),
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'category' => $category,
            'metadata' => $metadata
        ]);
    }

    public function createApprovalRequest(MasUser $approver, SpkRecord $record): Inbox
    {
        return $this->createMessage(
            $record,
            $approver,
            "New Approval Request #{$record->recordid}",
            "You have a new request waiting for your approval from {$record->orderempname}.",
            'approval',
            [
                'request_type' => 'spk_approval',
                'requester_id' => $record->createempcode,
                'requester_name' => $record->orderempname
            ]
        );
    }

    public function createRevisionRequest(SpkRecord $record, MasUser $reviewer, string $note): ?Inbox
    {
        $requester = MasUser::find($record->createempcode);
        if (!$requester) return null;

        return $this->createMessage(
            $record,
            $requester,
            "Revision Required #{$record->recordid}",
            "{$reviewer->name} has requested revisions: {$note}",
            'revision',
            [
                'reviewer_id' => $reviewer->id,
                'reviewer_name' => $reviewer->name,
                'note' => $note
            ]
        );
    }

    public function createRejectionNotification(SpkRecord $record, MasUser $rejector, string $note): ?Inbox
    {
        $requester = MasUser::find($record->createempcode);
        if (!$requester) return null;

        return $this->createMessage(
            $record,
            $requester,
            "Request Rejected #{$record->recordid}",
            "{$rejector->name} has rejected the request: {$note}",
            'rejection',
            [
                'rejector_id' => $rejector->id,
                'rejector_name' => $rejector->name,
                'note' => $note
            ]
        );
    }

    public function getUserUnreadCount(int $userId): int
    {
        return Inbox::where('user_id', $userId)
            ->where('status', 'unread')
            ->count();
    }
}
