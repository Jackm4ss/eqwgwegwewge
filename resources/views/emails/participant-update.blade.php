@php
    $isQrRefresh = $updateType === 'qr_regenerated';
    $isDeletion = $updateType === 'participant_deleted';
    $preheader = $isDeletion
        ? 'Your Songkran Festival registration was removed. Any linked ticket or QR pass is no longer active.'
        : ($isQrRefresh
            ? 'Your Songkran Festival QR pass was refreshed. Please use the latest QR code for event entry.'
            : 'Your participant details were updated by the Songkran Festival team. Review the latest account information here.');
    $eyebrow = $isDeletion
        ? 'Registration Update'
        : ($isQrRefresh ? 'Festival Pass Refresh' : 'Participant Update');
    $title = $isDeletion
        ? 'Your Registration Was Removed'
        : ($isQrRefresh ? 'Your QR Pass Was Updated' : 'Your Participant Details Were Updated');
    $intro = $isDeletion
        ? 'The Songkran Festival team removed this participant record from the system. Please review the details below if you need to coordinate a re-registration or support follow-up.'
        : ($isQrRefresh
            ? 'A new QR pass has been generated for your Songkran Festival ticket. Please use the latest QR below or open your live ticket page before arriving at the venue.'
            : 'The Songkran Festival team updated information on your participant profile. Please review the latest details below.');
    $ctaLabel = $isQrRefresh ? 'Open My Latest Ticket' : 'Review My Ticket';
    $participantName = $user['full_name'] ?? 'Participant';
    $ticketCode = trim((string) ($ticket['ticket_code'] ?? ''));
    $bodyLead = $isDeletion
        ? 'This notice confirms that your registration is no longer active in the <strong>Songkran Festival 2026</strong> system.'
        : 'This update was made by the <strong>Songkran Festival 2026</strong> admin team. Please keep this email for your latest participant and ticket reference.';
    $footerCopy = $isDeletion
        ? 'If this removal was unexpected, please contact the Songkran Festival support team before creating a new registration.'
        : 'If you did not expect this update, please contact the Songkran Festival support team.';
@endphp

<div style="margin:0; padding:0; background-color:#e0f2fe;">
    <span style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all;">
        {{ $preheader }}
    </span>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#e0f2fe; margin:0; padding:0; width:100%;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px; width:100%;">
                    <tr>
                        <td style="background:linear-gradient(135deg, #0369a1 0%, #0284c7 55%, #0ea5e9 100%); background-color:#0284c7; border-radius:32px 32px 0 0; padding:28px 32px 30px 32px; color:#ffffff;">
                            <div style="display:inline-block; padding:8px 14px; border-radius:999px; background-color:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.2); font-family:'Segoe UI', Arial, sans-serif; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase;">
                                {{ $eyebrow }}
                            </div>

                            <h1 style="margin:18px 0 10px; font-family:'Segoe UI', Arial, sans-serif; font-size:32px; line-height:1.15; font-weight:800; color:#ffffff;">
                                {{ $title }}
                            </h1>

                            <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:15px; line-height:1.7; color:rgba(255,255,255,0.92);">
                                {{ $intro }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#ffffff; border-radius:0 0 32px 32px; padding:32px; box-shadow:0 24px 60px rgba(14, 116, 144, 0.14);">
                            <p style="margin:0 0 18px; font-family:'Segoe UI', Arial, sans-serif; font-size:16px; line-height:1.7; color:#0f172a;">
                                Hi <strong>{{ $participantName }}</strong>,
                            </p>

                            <p style="margin:0 0 18px; font-family:'Segoe UI', Arial, sans-serif; font-size:16px; line-height:1.8; color:#334155;">
                                {!! $bodyLead !!}
                            </p>

                            @if ($changes !== [])
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px; border:1px solid #dbeafe; border-radius:22px; background-color:#f8fcff;">
                                    <tr>
                                        <td style="padding:20px 22px;">
                                            <p style="margin:0 0 14px; font-family:'Segoe UI', Arial, sans-serif; font-size:13px; line-height:1.5; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#0284c7;">
                                                Update Summary
                                            </p>

                                            @foreach ($changes as $change)
                                                <div style="{{ $loop->last ? '' : 'margin-bottom:14px;' }}">
                                                    <p style="margin:0 0 4px; font-family:'Segoe UI', Arial, sans-serif; font-size:13px; line-height:1.5; font-weight:700; color:#0f172a;">
                                                        {{ $change['label'] ?? '-' }}
                                                    </p>
                                                    <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:14px; line-height:1.8; color:#475569;">
                                                        {{ $change['value'] ?? '-' }}
                                                    </p>
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if ($ticketCode !== '')
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px; border:1px solid #dbeafe; border-radius:22px; background:linear-gradient(135deg, rgba(14,165,233,0.1) 0%, rgba(34,211,238,0.08) 100%); background-color:#f8fcff;">
                                    <tr>
                                        <td style="padding:20px 22px;">
                                            <p style="margin:0 0 8px; font-family:'Segoe UI', Arial, sans-serif; font-size:13px; line-height:1.5; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#0284c7;">
                                                Ticket Code
                                            </p>
                                            <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:22px; line-height:1.6; font-weight:900; color:#0f172a; word-break:break-word;">
                                                {{ $ticketCode }}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if ($qrPngBinary)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px; border:1px solid #dbeafe; border-radius:28px; background-color:#f8fcff;">
                                    <tr>
                                        <td align="center" style="padding:24px 18px;">
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 16px;">
                                                <tr>
                                                    <td style="padding:8px 14px; border-radius:999px; background-color:#e0f2fe; color:#0369a1; font-family:'Segoe UI', Arial, sans-serif; font-size:12px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase;">
                                                        Latest Festival QR Pass
                                                    </td>
                                                </tr>
                                            </table>

                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:252px; margin:0 auto;">
                                                <tr>
                                                    <td align="center" style="padding:16px; border-radius:24px; background:linear-gradient(180deg, #ffffff 0%, #eff8ff 100%); background-color:#ffffff; box-shadow:0 16px 32px rgba(14, 165, 233, 0.12);">
                                                        <img
                                                            src="{{ $message->embedData($qrPngBinary, 'updated-ticket-qrcode.png', 'image/png') }}"
                                                            alt="Updated Songkran Festival ticket QR code"
                                                            width="200"
                                                            height="200"
                                                            style="display:block; width:100%; max-width:200px; height:auto; margin:0 auto; border:0; outline:none; text-decoration:none;"
                                                        >
                                                    </td>
                                                </tr>
                                            </table>

                                            <p style="margin:16px 0 0; font-family:'Segoe UI', Arial, sans-serif; font-size:14px; line-height:1.8; color:#475569;">
                                                Please discard any older QR image and keep only this latest version for event entry.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if ($ticketUrl)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                                    <tr>
                                        <td align="center">
                                            <a
                                                href="{{ $ticketUrl }}"
                                                style="display:inline-block; padding:15px 28px; border-radius:999px; background:linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); background-color:#0ea5e9; color:#ffffff; text-decoration:none; font-family:'Segoe UI', Arial, sans-serif; font-size:15px; font-weight:800; letter-spacing:0.02em; box-shadow:0 10px 24px rgba(14, 165, 233, 0.28);"
                                            >
                                                {{ $ctaLabel }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px; border:1px solid #dbeafe; border-radius:22px; background-color:#f8fcff;">
                                    <tr>
                                        <td style="padding:20px 22px;">
                                            <p style="margin:0 0 8px; font-family:'Segoe UI', Arial, sans-serif; font-size:13px; line-height:1.5; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#0284c7;">
                                                Direct Access Link
                                            </p>
                                            <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:14px; line-height:1.8; color:#475569;">
                                                If the button above does not open correctly, copy and paste this link into your browser:
                                            </p>
                                            <p style="margin:10px 0 0; word-break:break-all;">
                                                <a href="{{ $ticketUrl }}" style="font-family:'Segoe UI', Arial, sans-serif; font-size:13px; line-height:1.7; color:#0284c7; text-decoration:underline;">
                                                    {{ $ticketUrl }}
                                                </a>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:14px; line-height:1.8; color:#64748b;">
                                {{ $footerCopy }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
