<div style="font-family:Segoe UI, Arial, sans-serif; color:#0f172a; line-height:1.6;">
    <h2 style="margin-bottom:12px;">Verify Your Registration</h2>

    <p>Hi {{ $user['full_name'] }},</p>

    <p>
        Thank you for registering for <strong>Songkran Festival 2026</strong>.
        Please verify your email address to activate your account and unlock your QR ticket.
    </p>

    <p style="margin:24px 0;">
        <a
            href="{{ $verificationUrl }}"
            style="display:inline-block; padding:12px 18px; border-radius:999px; background:#0ea5e9; color:#ffffff; text-decoration:none; font-weight:700;"
        >
            Verify My Email
        </a>
    </p>

    <p>This verification link is valid for 24 hours.</p>
    <p>If you did not request this registration, you can safely ignore this email.</p>
</div>
