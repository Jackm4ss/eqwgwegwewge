<div style="margin:0; padding:0; background-color:#e0f2fe;">
    <span style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all;">
        Verify your Songkran Festival 2026 registration to activate your account and unlock your QR ticket.
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
                                Songkran Festival 2026
                            </div>

                            <h1 style="margin:18px 0 10px; font-family:'Segoe UI', Arial, sans-serif; font-size:32px; line-height:1.15; font-weight:800; color:#ffffff;">
                                Verify Your Registration
                            </h1>

                            <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:15px; line-height:1.7; color:rgba(255,255,255,0.92);">
                                One last step to activate your account and unlock your QR ticket for the festival.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#ffffff; border-radius:0 0 32px 32px; padding:32px; box-shadow:0 24px 60px rgba(14, 116, 144, 0.14);">
                            <p style="margin:0 0 18px; font-family:'Segoe UI', Arial, sans-serif; font-size:16px; line-height:1.7; color:#0f172a;">
                                Hi <strong>{{ $user['full_name'] }}</strong>,
                            </p>

                            <p style="margin:0 0 16px; font-family:'Segoe UI', Arial, sans-serif; font-size:16px; line-height:1.8; color:#334155;">
                                Thank you for registering for <strong>Songkran Festival 2026</strong>. Please confirm your email address to complete your registration and secure access to your ticket details.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0 24px;">
                                <tr>
                                    <td align="center">
                                        <a
                                            href="{{ $verificationUrl }}"
                                            style="display:inline-block; padding:15px 28px; border-radius:999px; background:linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); background-color:#0ea5e9; color:#ffffff; text-decoration:none; font-family:'Segoe UI', Arial, sans-serif; font-size:15px; font-weight:800; letter-spacing:0.02em; box-shadow:0 10px 24px rgba(14, 165, 233, 0.28);"
                                        >
                                            Verify My Email
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px; border:1px solid #dbeafe; border-radius:22px; background-color:#f8fcff;">
                                <tr>
                                    <td style="padding:20px 22px;">
                                        <p style="margin:0 0 8px; font-family:'Segoe UI', Arial, sans-serif; font-size:13px; line-height:1.5; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#0284c7;">
                                            Verification Window
                                        </p>
                                        <p style="margin:0 0 10px; font-family:'Segoe UI', Arial, sans-serif; font-size:15px; line-height:1.7; color:#0f172a; font-weight:700;">
                                            This link stays active for 24 hours.
                                        </p>
                                        <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:14px; line-height:1.7; color:#475569;">
                                            If the button does not work, copy and paste this link into your browser:
                                        </p>
                                        <p style="margin:10px 0 0; word-break:break-all;">
                                            <a href="{{ $verificationUrl }}" style="font-family:'Segoe UI', Arial, sans-serif; font-size:13px; line-height:1.7; color:#0284c7; text-decoration:underline;">
                                                {{ $verificationUrl }}
                                            </a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; font-family:'Segoe UI', Arial, sans-serif; font-size:14px; line-height:1.8; color:#64748b;">
                                If you did not request this registration, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
