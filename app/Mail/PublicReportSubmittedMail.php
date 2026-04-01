<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PublicReportSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly array $payload,
        public readonly string $reference,
        public readonly string $recipient,
        public readonly CarbonInterface $submittedAt,
        public readonly string $ipAddress,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('New Public Report Submitted | '.$this->reference)
            ->view('emails.public-report-submitted', [
                'payload' => $this->payload,
                'reference' => $this->reference,
                'recipient' => $this->recipient,
                'submittedAt' => $this->submittedAt,
                'ipAddress' => $this->ipAddress,
                'reportTypeLabel' => $this->reportTypeLabel((string) ($this->payload['report_type'] ?? '')),
            ]);
    }

    private function reportTypeLabel(string $value): string
    {
        return match ($value) {
            'incident_security' => 'Incident / Security',
            'lost_item' => 'Lost Item',
            'lost_locker_card' => 'Lost Locker Card',
            'medical_attention' => 'Medical Attention',
            default => 'Others',
        };
    }
}
