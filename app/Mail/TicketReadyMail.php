<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $user,
        public readonly array $ticket,
        public readonly string $ticketUrl,
        public readonly string $qrPngBinary,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Your Songkran Festival Ticket Is Ready')
            ->view('emails.ticket-ready');
    }
}
