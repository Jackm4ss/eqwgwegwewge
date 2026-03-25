<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ParticipantUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $user,
        public readonly array $ticket,
        public readonly string $updateType,
        public readonly array $changes = [],
        public readonly ?string $ticketUrl = null,
        public readonly ?string $qrPngBinary = null,
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine())
            ->view('emails.participant-update');
    }

    private function subjectLine(): string
    {
        return match ($this->updateType) {
            'qr_regenerated' => 'Your QR Pass Was Updated | Songkran Festival 2026',
            'participant_deleted' => 'Your Registration Was Removed | Songkran Festival 2026',
            default => 'Your Participant Details Were Updated | Songkran Festival 2026',
        };
    }
}
