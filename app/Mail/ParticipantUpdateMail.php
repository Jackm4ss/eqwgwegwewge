<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ParticipantUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $templateAssets;

    public function __construct(
        public readonly array $user,
        public readonly array $ticket,
        public readonly string $updateType,
        public readonly array $changes = [],
        public readonly ?string $ticketUrl = null,
        public readonly ?string $qrPngBinary = null,
    ) {
        $assetDirectory = rtrim(
            (string) env('SONGKRAN_MAILER_TEMPLATE_PATH', public_path('images')),
            "\\/"
        );

        $this->templateAssets = [
            'background' => $assetDirectory . DIRECTORY_SEPARATOR . 'BACKGROUND.jpg',
            'logo' => $assetDirectory . DIRECTORY_SEPARATOR . 'Songkran logo.png',
            'eventHeader' => $assetDirectory . DIRECTORY_SEPARATOR . 'ticket-event-header-email.png',
            'venueSponsor' => $assetDirectory . DIRECTORY_SEPARATOR . '123.png',
            'sponsorEmbassy' => $assetDirectory . DIRECTORY_SEPARATOR . 'Royal_Thai_Embassy_Seal.svg.png',
            'sponsorDitp' => $assetDirectory . DIRECTORY_SEPARATOR . 'ditp-new.png',
            'sponsorAmazingThailand' => $assetDirectory . DIRECTORY_SEPARATOR . 'amazing thailand.png',
            'sponsorSingha' => $assetDirectory . DIRECTORY_SEPARATOR . 'singha-seeklogo.png',
            'sponsorSnakeBrand' => $assetDirectory . DIRECTORY_SEPARATOR . 'Snake-Brand-Logo.png',
            'mapToLocation' => $assetDirectory . DIRECTORY_SEPARATOR . 'Map to Location.png',
            'mediaWob' => $assetDirectory . DIRECTORY_SEPARATOR . 'wob.png',
            'mediaNoodou' => $assetDirectory . DIRECTORY_SEPARATOR . 'noodou.png',
        ];
    }

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine())
            ->view($this->updateType === 'qr_regenerated'
                ? 'emails.participant-qr-refresh'
                : 'emails.participant-update');
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
