<div style="margin:0; padding:0; background-color:#e0f2fe;">
    <span style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all;">
        Your Songkran Festival ticket is ready. Open your pass, save your QR code, and keep it ready for event entry.
    </span>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#e0f2fe; margin:0; padding:0; width:100%;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px; width:100%;">
                    <tr>
                        <td
                            style="background:linear-gradient(135deg, #0369a1 0%, #0284c7 55%, #0ea5e9 100%); background-color:#0284c7; border-radius:32px 32px 0 0; padding:28px 32px 30px 32px; color:#ffffff;"
                        >
                            <div style="display:inline-block; padding:8px 14px; border-radius:999px; background-color:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.2); font-family:'Segoe UI', Arial, sans-serif; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase;">
                                Festival Pass Active
                            </div>

                            <h1 style="margin:18px 0 10px; font-family:'Segoe UI', Arial, sans-serif; font-size:32px; line-height:1.15; font-weight:800; color:#ffffff;">
                                Your Ticket Is Ready
                            </h1>

                            <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:15px; line-height:1.7; color:rgba(255,255,255,0.92);">
                                Your registration is complete and your Songkran Festival pass is now ready for entry.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#ffffff; border-radius:0 0 32px 32px; padding:32px; box-shadow:0 24px 60px rgba(14, 116, 144, 0.14);">
                            <p style="margin:0 0 18px; font-family:'Segoe UI', Arial, sans-serif; font-size:16px; line-height:1.7; color:#0f172a;">
                                Hi <strong>{{ $user['full_name'] }}</strong>,
                            </p>

                            <p style="margin:0 0 18px; font-family:'Segoe UI', Arial, sans-serif; font-size:16px; line-height:1.8; color:#334155;">
                                Your <strong>Songkran Festival 2026</strong> account is active. Use the QR code below or open your live ticket page anytime before check-in.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px; border:1px solid #dbeafe; border-radius:22px; background:linear-gradient(135deg, rgba(14,165,233,0.1) 0%, rgba(34,211,238,0.08) 100%); background-color:#f8fcff;">
                                <tr>
                                    <td style="padding:20px 22px;">
                                        <p style="margin:0 0 8px; font-family:'Segoe UI', Arial, sans-serif; font-size:13px; line-height:1.5; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#0284c7;">
                                            Ticket Code
                                        </p>
                                        <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:22px; line-height:1.6; font-weight:900; color:#0f172a; word-break:break-word;">
                                            {{ $ticket['ticket_code'] }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px; border:1px solid #dbeafe; border-radius:28px; background-color:#f8fcff;">
                                <tr>
                                    <td align="center" style="padding:24px 18px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 16px;">
                                            <tr>
                                                <td style="padding:8px 14px; border-radius:999px; background-color:#e0f2fe; color:#0369a1; font-family:'Segoe UI', Arial, sans-serif; font-size:12px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase;">
                                                    Festival QR Pass
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:252px; margin:0 auto;">
                                            <tr>
                                                <td align="center" style="padding:16px; border-radius:24px; background:linear-gradient(180deg, #ffffff 0%, #eff8ff 100%); background-color:#ffffff; box-shadow:0 16px 32px rgba(14, 165, 233, 0.12);">
                                                    <img
                                                        src="{{ $message->embedData($qrPngBinary, 'ticket-qrcode.png', 'image/png') }}"
                                                        alt="Songkran Festival ticket QR code"
                                                        width="200"
                                                        height="200"
                                                        style="display:block; width:100%; max-width:200px; height:auto; margin:0 auto; border:0; outline:none; text-decoration:none;"
                                                    >
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin:16px 0 0; font-family:'Segoe UI', Arial, sans-serif; font-size:14px; line-height:1.8; color:#475569;">
                                            Present this QR code at the event entrance to open your ticket details instantly.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                                <tr>
                                    <td align="center">
                                        <a
                                            href="{{ $ticketUrl }}"
                                            style="display:inline-block; padding:15px 28px; border-radius:999px; background:linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); background-color:#0ea5e9; color:#ffffff; text-decoration:none; font-family:'Segoe UI', Arial, sans-serif; font-size:15px; font-weight:800; letter-spacing:0.02em; box-shadow:0 10px 24px rgba(14, 165, 233, 0.28);"
                                        >
                                            Open My Ticket
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

                            <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:14px; line-height:1.8; color:#64748b;">
                                Please keep this QR code safe and have it ready when you arrive at the venue.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
