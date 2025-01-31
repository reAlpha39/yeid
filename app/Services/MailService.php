<?php

namespace App\Services;

use App\Models\MasUser;
use App\Models\SpkRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovalRequestMail;

class MailService
{
    public function sendApprovalRequest(MasUser $approver, SpkRecord $spkRecord)
    {
        Mail::to($approver->email)
            ->queue(new ApprovalRequestMail($approver, $spkRecord));
    }

    public function sendApprovalRequestBatch(Collection|array $approvers, SpkRecord $spkRecord)
    {
        foreach ($approvers as $approver) {
            $this->sendApprovalRequest($approver, $spkRecord);
        }
    }
}
