<div style="font-family:Segoe UI, Arial, sans-serif; color:#0f172a; line-height:1.6;">
    <h2 style="margin-bottom:12px;">Your Ticket Is Ready</h2>

    <p>Hi {{ $user['full_name'] }},</p>

    <p>
        Your Songkran Festival account is now active. Here is your ticket QR code and direct access link.
    </p>

    <p style="margin:20px 0 8px;">
        <strong>Ticket Code:</strong> {{ $ticket['ticket_code'] }}
    </p>

    <div style="margin:20px 0;">
        <img
            src="{{ $message->embedData($qrPngBinary, 'ticket-qrcode.png', 'image/png') }}"
            alt="Songkran Festival ticket QR code"
            style="display:block; width:240px; height:240px; max-width:100%; border-radius:12px; border:1px solid #cbd5e1;"
        >
    </div>

    <p style="margin:24px 0;">
        <a
            href="{{ $ticketUrl }}"
            style="display:inline-block; padding:12px 18px; border-radius:999px; background:#0ea5e9; color:#ffffff; text-decoration:none; font-weight:700;"
        >
            Open My Ticket
        </a>
    </p>

    <p>Please keep this QR code safe and present it at the event entrance.</p>
</div>
