<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Public Report Submitted</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
        <div style="padding:24px 28px;background:linear-gradient(135deg,#075985,#0891b2);color:#ffffff;">
            <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.2em;text-transform:uppercase;font-weight:700;opacity:0.9;">Public Report</p>
            <h1 style="margin:0;font-size:28px;line-height:1.2;">{{ $reference }}</h1>
            <p style="margin:12px 0 0;font-size:14px;line-height:1.6;opacity:0.95;">A new public report was submitted from the website and sent to {{ $recipient }}.</p>
        </div>

        <div style="padding:28px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td style="padding:0 0 14px;font-size:13px;color:#475569;">Submitted At</td>
                    <td style="padding:0 0 14px;font-size:14px;font-weight:700;text-align:right;">{{ $submittedAt->format('Y-m-d H:i:s') }}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 14px;font-size:13px;color:#475569;">Type of Report</td>
                    <td style="padding:0 0 14px;font-size:14px;font-weight:700;text-align:right;">{{ $reportTypeLabel }}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 14px;font-size:13px;color:#475569;">Name</td>
                    <td style="padding:0 0 14px;font-size:14px;font-weight:700;text-align:right;">{{ $payload['name'] }}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 14px;font-size:13px;color:#475569;">Phone</td>
                    <td style="padding:0 0 14px;font-size:14px;font-weight:700;text-align:right;">{{ $payload['phone'] }}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 14px;font-size:13px;color:#475569;">IC / Passport No.</td>
                    <td style="padding:0 0 14px;font-size:14px;font-weight:700;text-align:right;">{{ $payload['identity_number'] ?: '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 14px;font-size:13px;color:#475569;">Email</td>
                    <td style="padding:0 0 14px;font-size:14px;font-weight:700;text-align:right;">{{ $payload['email'] ?: '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 14px;font-size:13px;color:#475569;">Staff Name</td>
                    <td style="padding:0 0 14px;font-size:14px;font-weight:700;text-align:right;">{{ $payload['staff_name'] ?: '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:0;font-size:13px;color:#475569;">IP Address</td>
                    <td style="padding:0;font-size:14px;font-weight:700;text-align:right;">{{ $ipAddress }}</td>
                </tr>
            </table>

            <div style="margin-top:24px;padding:18px;border:1px solid #bae6fd;border-radius:16px;background:#f0f9ff;">
                <p style="margin:0 0 10px;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;font-weight:700;color:#0369a1;">Report / Chronology</p>
                <p style="margin:0;font-size:14px;line-height:1.7;color:#0f172a;white-space:pre-line;">{{ $payload['chronology'] }}</p>
            </div>
        </div>
    </div>
</body>
</html>
