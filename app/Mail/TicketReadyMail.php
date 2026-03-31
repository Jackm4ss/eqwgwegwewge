<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $templateAssets;

    public function __construct(
        public readonly array $user,
        public readonly array $ticket,
        public readonly string $ticketUrl,
        public readonly string $qrPngBinary,
    ) {
        $assetDirectory = rtrim(
            (string) env('SONGKRAN_MAILER_TEMPLATE_PATH', public_path('images')),
            "\\/"
        );

        $this->templateAssets = [
            'background' => $assetDirectory.DIRECTORY_SEPARATOR.'BACKGROUND.jpg',
            'logo' => $assetDirectory.DIRECTORY_SEPARATOR.'Songkran logo.png',
            'organiser' => $assetDirectory.DIRECTORY_SEPARATOR.'eq-solution.png',
            'venueSponsor' => $assetDirectory.DIRECTORY_SEPARATOR.'123.png',
            'sponsorEmbassy' => $assetDirectory.DIRECTORY_SEPARATOR.'Royal_Thai_Embassy_Seal.svg.png',
            'sponsorDitp' => $assetDirectory.DIRECTORY_SEPARATOR.'ditp-new.png',
            'sponsorAmazingThailand' => $assetDirectory.DIRECTORY_SEPARATOR.'amazing thailand.png',
            'sponsorSingha' => $assetDirectory.DIRECTORY_SEPARATOR.'singha-seeklogo.png',
            'sponsorSnakeBrand' => $assetDirectory.DIRECTORY_SEPARATOR.'Snake-Brand-Logo.png',
            'sponsorThaigo' => $assetDirectory.DIRECTORY_SEPARATOR.'thaigo.png',
            'sponsorLayer0' => $assetDirectory.DIRECTORY_SEPARATOR.'Layer 0.png',
            'mediaWob' => $assetDirectory.DIRECTORY_SEPARATOR.'wob.png',
            'mediaNoodou' => $assetDirectory.DIRECTORY_SEPARATOR.'noodou.png',
        ];
    }

    public function build(): self
    {
        return $this
            ->subject('Your Ticket Is Ready | Songkran Festival 2026')
            ->view('emails.ticket-ready');
    }
}
