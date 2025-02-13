<?php

namespace App\Services;

use App\Models\MasUser;
use App\Models\SpkRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovalRequestMail;
use App\Mail\RevisionRequestMail;
use App\Mail\RejectedRequestMail;
use Illuminate\Support\Facades\Log;
use Exception;

class MailService
{
    private $inboxService;

    public function __construct(InboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    public function sendApprovalRequest(MasUser $approver, SpkRecord $spkRecord)
    {
        $this->inboxService->createApprovalRequest($approver, $spkRecord);
        try {
            if ($this->validateEmail($approver->email)) {
                Mail::to($approver->email)
                    ->queue(new ApprovalRequestMail($approver, $spkRecord));
            }
        } catch (Exception $e) {
            Log::error('Failed to send approval request email to ' . $approver->email . ': ' . $e->getMessage());
            // throw new Exception('Failed to send email: ' . $e->getMessage());
        }
    }

    public function sendApprovalRequestBatch(Collection|array $approvers, SpkRecord $spkRecord)
    {
        $validApprovers = collect($approvers)->filter(function (MasUser $approver) {
            return $this->validateEmail($approver->email);
        });

        if ($validApprovers->isEmpty()) {
            Log::warning('No valid email addresses found in approvers list');
        }

        /** @var Collection<MasUser> $validApprovers */
        foreach ($validApprovers as $approver) {
            $this->inboxService->createApprovalRequest($approver, $spkRecord);
            try {
                Mail::to($approver->email)
                    ->queue(new ApprovalRequestMail($approver, $spkRecord));
            } catch (Exception $e) {
                Log::error('Failed to send approval request email to ' . $approver->email . ': ' . $e->getMessage());
                continue;
            }
        }
    }

    public function sendRevisionRequest(SpkRecord $spkRecord, MasUser $reviewer, string $note)
    {
        $requester = MasUser::find($spkRecord->approvalRecord->created_by);

        if ($requester) {
            $this->inboxService->createRevisionRequest($spkRecord, $reviewer, $note);

            try {
                if ($this->validateEmail($requester->email)) {
                    Mail::to($requester->email)
                        ->queue(new RevisionRequestMail($spkRecord, $reviewer, $note));
                }
            } catch (Exception $e) {
                Log::error('Failed to send revision notification: ' . $requester->email . ': ' . $e->getMessage());
                // throw $e;
            }
        }
    }

    public function sendRejectionNotification(SpkRecord $spkRecord, MasUser $rejector, string $note)
    {
        $requester = MasUser::find($spkRecord->approvalRecord->created_by);

        if ($requester) {
            $this->inboxService->createRejectionNotification($spkRecord, $rejector, $note);

            try {
                if ($this->validateEmail($requester->email)) {
                    Mail::to($requester->email)
                        ->queue(new RejectedRequestMail($spkRecord, $rejector, $note));
                }
            } catch (Exception $e) {
                Log::error('Failed to send rejection notification: '. $requester->email . ': '. $e->getMessage());
                // throw $e;
            }
        }
    }

    private function validateEmail(?string $email): bool
    {
        if (!$email) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
