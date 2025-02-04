<?php

namespace App\Services;

use App\Models\MasUser;
use App\Models\SpkRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovalRequestMail;
use App\Mail\RevisionRequestMail;

class MailService
{
    private $inboxService;

    public function __construct(InboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    public function sendApprovalRequest(MasUser $approver, SpkRecord $spkRecord)
    {
        Mail::to($approver->email)
            ->queue(new ApprovalRequestMail($approver, $spkRecord));

        $this->inboxService->createApprovalRequest($approver, $spkRecord);
    }

    public function sendApprovalRequestBatch(Collection|array $approvers, SpkRecord $spkRecord)
    {
        foreach ($approvers as $approver) {
            $this->sendApprovalRequest($approver, $spkRecord);
        }
    }

    public function sendRevisionRequest(SpkRecord $spkRecord, MasUser $reviewer, string $note)
    {
        $requester = MasUser::find($spkRecord->createempcode);

        if ($requester) {
            Mail::to($requester->email)
                ->queue(new RevisionRequestMail($spkRecord, $reviewer, $note));

            $this->inboxService->createRevisionRequest($spkRecord, $reviewer, $note);
        }
    }

    public function sendRejectionNotification(SpkRecord $spkRecord, MasUser $rejector, string $note)
    {
        $requester = MasUser::find($spkRecord->createempcode);

        if ($requester) {
            $this->inboxService->createRejectionNotification($spkRecord, $rejector, $note);
        }
    }
}
