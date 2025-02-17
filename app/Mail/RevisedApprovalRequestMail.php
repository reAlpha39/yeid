<?php

namespace App\Mail;

use App\Models\MasUser;
use App\Models\SpkRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RevisedApprovalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MasUser $approver,
        public SpkRecord $spkRecord
    ) {}

    public function build()
    {
        return $this->subject('Revised SPK Record Approval Request #' . $this->spkRecord->recordid)
            ->view('emails.revised-approval-request');
    }
}
