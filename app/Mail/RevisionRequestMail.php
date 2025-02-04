<?php

namespace App\Mail;

use App\Models\MasUser;
use App\Models\SpkRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RevisionRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SpkRecord $spkRecord,
        public MasUser $reviewer,
        public string $note
    ) {}

    public function build()
    {
        return $this->subject('SPK Record Revision Required #' . $this->spkRecord->recordid)
            ->view('emails.revision-request');
    }
}
