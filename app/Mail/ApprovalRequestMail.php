<?php

namespace App\Mail;

use App\Models\MasUser;
use App\Models\SpkRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MasUser $approver,
        public SpkRecord $spkRecord
    ) {}

    public function build()
    {
        return $this->subject('New SPK Record Approval Request #' . $this->spkRecord->recordid)
            ->view('emails.approval-request');
    }
}
