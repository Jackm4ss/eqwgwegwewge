<?php

namespace App\Mail;

use App\Models\PublicReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PublicReportReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PublicReport $report,
        public readonly string $reportTypeLabel,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Case id: '.$this->report->case_id)
            ->view('emails.public-report-receipt', [
                'report' => $this->report,
                'reportTypeLabel' => $this->reportTypeLabel,
            ]);
    }
}
