{{-- Legacy qr_regenerated layout is intentionally kept in emails.participant-update
    while this ticket-style version waits for final approval and can be rolled back quickly. --}}
@php
    $emailDocumentTitle = 'Festival Pass Refresh | Songkran Festival 2026';
    $noticeEyebrow = 'Festival Pass Refresh';
    $noticeTitle = 'Your QR Pass Was Updated';
    $noticeCopy = 'A new QR pass has been generated for your Songkran Festival ticket. Please use the latest QR below or open your live ticket page before arriving at the venue.';
    $ticketButtonLabel = 'Open My Latest Ticket';
    $messageTitle = 'Your latest festival QR pass is ready.';
    $messageCopy = 'Please discard any older QR image and keep only this latest version for event entry.<br>This update was made by the <strong>Songkran Festival 2026</strong> admin team. Please keep this email for your latest participant and ticket reference.';
    $supportNote = 'If you did not expect this update, please contact the Songkran Festival support team.';
    $emailPreviewText = 'Your Songkran Festival QR pass was refreshed. Please use the latest QR code for event entry.';
    $qrAltText = 'Updated Songkran Festival ticket QR code';
    $qrImageFilename = 'updated-ticket-qrcode.png';
@endphp

@include('emails.ticket-ready')
