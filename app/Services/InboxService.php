<?php

namespace App\Services;

use App\Models\Inbox;
use App\Models\MasUser;
use App\Models\SpkRecord;
use App\Models\SpkRecordApproval;
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
        // Get the full class name
        $sourceType = class_basename(get_class($source));
        return Inbox::create([
            'source_type' => $sourceType,
            'source_id' => $source->getKey(),
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'category' => $category,
            'metadata' => $metadata
        ]);
    }

    public function createApprovalRequest(MasUser $user, SpkRecord $record): Inbox
    {
        return $this->createMessage(
            $record,
            $user,
            "New Approval Request #{$record->recordid}",
            "You have a new request waiting for your approval from {$user->name}.",
            'approval',
            [
                'request_type' => 'spk_approval',
                'requester_id' => $user->id,
                'requester_name' => $user->name
            ]
        );
    }

    public function createRevisedApprovalRequest(MasUser $user, SpkRecord $record): Inbox
    {
        return $this->createMessage(
            $record,
            $user,
            "Revised Approval Request #{$record->recordid}",
            "You have a revised request waiting for your approval from {$user->name}.",
            'approval',
            [
                'request_type' => 'spk_approval',
                'requester_id' => $user->id,
                'requester_name' => $user->name
            ]
        );
    }

    public function createRevisionRequest(SpkRecord $record, MasUser $reviewer, ?string $note): ?Inbox
    {
        $requester = MasUser::find(
            SpkRecordApproval::where('record_id', $record->recordid)
                ->value('created_by')
        );

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

    public function createRejectionNotification(SpkRecord $record, MasUser $rejector, ?string $note): ?Inbox
    {
        $requester = MasUser::find(
            SpkRecordApproval::where('record_id', $record->recordid)
                ->value('created_by')
        );

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
