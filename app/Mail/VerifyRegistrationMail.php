<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $user,
        public readonly string $verificationUrl,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Verify Your Registration | Songkran Festival 2026')
            ->view('emails.verify-registration');
    }
}
