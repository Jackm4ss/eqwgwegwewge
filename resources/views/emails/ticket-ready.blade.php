@php
    $embedAsset = static function (?string $path) use ($message): ?string {
        return ($path && is_file($path)) ? $message->embed($path) : null;
    };
    $publicImageUrl = static function (string $filename): string {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $resolvedBaseUrl = $baseUrl !== '' ? $baseUrl : rtrim(url('/'), '/');

        return $resolvedBaseUrl . '/images/' . rawurlencode($filename);
    };

    $backgroundCid = $embedAsset($templateAssets['background'] ?? null);
    $backgroundSrc = $backgroundCid ?: $publicImageUrl('BACKGROUND.jpg');
    $logoCid = $embedAsset($templateAssets['logo'] ?? null);
    $logoSrc = $logoCid ?: $publicImageUrl('Songkran logo.png');
    $venueSponsorCid = $embedAsset($templateAssets['venueSponsor'] ?? null);
    $sponsorEmbassyCid = $embedAsset($templateAssets['sponsorEmbassy'] ?? null);
    $sponsorDitpCid = $embedAsset($templateAssets['sponsorDitp'] ?? null);
    $sponsorAmazingThailandCid = $embedAsset($templateAssets['sponsorAmazingThailand'] ?? null);
    $sponsorSinghaCid = $embedAsset($templateAssets['sponsorSingha'] ?? null);
    $sponsorSnakeBrandCid = $embedAsset($templateAssets['sponsorSnakeBrand'] ?? null);
    $sponsorThaigoCid = $embedAsset($templateAssets['sponsorThaigo'] ?? null);
    $sponsorLayer0Cid = $embedAsset($templateAssets['sponsorLayer0'] ?? null);
    $mediaWobCid = $embedAsset($templateAssets['mediaWob'] ?? null);
    $mediaNoodouCid = $embedAsset($templateAssets['mediaNoodou'] ?? null);
    $venueSponsorSrc = $venueSponsorCid ?: $publicImageUrl('123.png');
    $sponsorEmbassySrc = $sponsorEmbassyCid ?: $publicImageUrl('Royal_Thai_Embassy_Seal.svg.png');
    $sponsorDitpSrc = $sponsorDitpCid ?: $publicImageUrl('ditp.jpeg');
    $sponsorAmazingThailandSrc = $sponsorAmazingThailandCid ?: $publicImageUrl('amazing thailand.png');
    $sponsorSinghaSrc = $sponsorSinghaCid ?: $publicImageUrl('singha-seeklogo.png');
    $sponsorSnakeBrandSrc = $sponsorSnakeBrandCid ?: $publicImageUrl('Snake-Brand-Logo.png');
    $sponsorThaigoSrc = $sponsorThaigoCid ?: $publicImageUrl('thaigo.png');
    $sponsorLayer0Src = $sponsorLayer0Cid ?: $publicImageUrl('Layer 0.png');
    $mediaWobSrc = $mediaWobCid ?: $publicImageUrl('wob.png');
    $mediaNoodouSrc = $mediaNoodouCid ?: $publicImageUrl('noodou.png');

    $identityNumber = trim((string) ($user['identity_number'] ?? ''));
    $identityDisplay = $identityNumber !== '' ? $identityNumber : '-';
    $fullName = trim((string) ($user['full_name'] ?? 'Guest'));
    $entryCodeDisplay = trim((string) ($ticket['entry_code_display'] ?? ''));
    $qrCardWidth = $entryCodeDisplay !== '' ? 280 : 186;
    $emailDocumentTitle = trim((string) ($emailDocumentTitle ?? 'Songkran Festival E-Ticket Registration'));
    $noticeEyebrow = trim((string) ($noticeEyebrow ?? ''));
    $noticeTitle = trim((string) ($noticeTitle ?? ''));
    $noticeCopy = trim((string) ($noticeCopy ?? ''));
    $hasNotice = $noticeEyebrow !== '' || $noticeTitle !== '' || $noticeCopy !== '';
    $heroSpacerHeight = $hasNotice ? 28 : 40;
    $ticketButtonLabel = trim((string) ($ticketButtonLabel ?? 'Open Ticket'));
    $messageTitle = trim((string) ($messageTitle ?? 'Thank you for your registration.'));
    $messageCopy = (string) ($messageCopy ?? 'Please present your QR code and registered valid ID / passport at the gate.<br>QR only required to scan once per day');
    $supportNote = trim((string) ($supportNote ?? ''));
    $showMapsLink = (bool) ($showMapsLink ?? true);
    $qrAltText = trim((string) ($qrAltText ?? 'Songkran Festival ticket QR code'));
    $qrImageFilename = trim((string) ($qrImageFilename ?? 'ticket-qrcode.png'));
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailDocumentTitle }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        body,
        table,
        td,
        p,
        a {
            font-family: 'Outfit', Arial, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #e5f5f9;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
        }

        img {
            border: 0;
            outline: none;
            text-decoration: none;
            display: block;
            max-width: 100%;
        }

        .shell {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .ticket-surface {
            background-color: #038cb2;
            background-image: url('{{ $backgroundSrc }}');
            background-repeat: no-repeat;
            background-position: center bottom;
            background-size: cover;
        }

        .top-pad {
            padding: 22px 20px 0;
        }

        .bottom-pad {
            padding: 32px 20px 28px;
        }

        .logo {
            width: 100%;
            max-width: 380px;
            margin: 0 auto 8px;
        }

        .event-block {
            color: #ffffff;
            text-align: center;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.35);
        }

        .event-time {
            font-size: 18px;
            line-height: 24px;
            font-weight: 800;
            margin: 0;
        }

        .event-date {
            font-size: 54px;
            line-height: 54px;
            font-weight: 900;
            letter-spacing: 1px;
            margin: 8px 0 6px;
        }

        .event-venue {
            font-size: 14px;
            line-height: 22px;
            font-weight: 800;
            margin: 0;
        }

        .event-subtitle {
            font-size: 10px;
            line-height: 18px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin: 6px 0 0;
        }

        .qr-wrap {
            padding-top: 0;
        }

        .notice-card {
            border-radius: 22px;
            border: 1px solid rgba(255, 255, 255, 0.42);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.72), rgba(255, 255, 255, 0.48));
            box-shadow:
                0 14px 30px rgba(15, 23, 42, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.72);
        }

        .notice-card td {
            padding: 18px 22px;
        }

        .notice-eyebrow {
            color: rgba(15, 23, 42, 0.68);
            font-size: 10px;
            line-height: 14px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            margin: 0 0 8px;
        }

        .notice-title {
            color: #0f172a;
            font-size: 24px;
            line-height: 30px;
            font-weight: 800;
            margin: 0 0 10px;
        }

        .notice-copy {
            color: rgba(15, 23, 42, 0.78);
            font-size: 14px;
            line-height: 22px;
            font-weight: 700;
            margin: 0;
        }

        .qr-card {
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.22);
        }

        .qr-card td {
            padding: 13px;
        }

        .identity-card,
        .footer-card {
            background: rgba(255, 255, 255, 0.36);
            border: 1px solid rgba(255, 255, 255, 0.32);
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .identity-card td {
            padding: 30px 35px 28px;
        }

        .identity-name {
            color: #000000;
            font-size: 17px;
            line-height: 24px;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin: 0 0 4px;
        }

        .identity-number {
            color: rgba(0, 0, 0, 0.9);
            font-size: 14px;
            line-height: 20px;
            font-weight: 600;
            margin: 0;
        }

        .message-title {
            color: #ffffff;
            font-size: 20px;
            line-height: 28px;
            font-weight: 800;
            text-align: center;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.45);
            margin: 0 0 10px;
        }

        .message-copy {
            color: #ffffff;
            font-size: 14px;
            line-height: 22px;
            font-weight: 700;
            text-align: center;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.45);
            margin: 0;
        }

        .support-note {
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            line-height: 20px;
            font-weight: 700;
            text-align: center;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.35);
            margin: 16px auto 0;
            max-width: 420px;
        }

        .ticket-validity-note {
            color: #ffffff;
            font-size: 13px;
            line-height: 18px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            text-align: center;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.45);
            margin: 0;
        }

        .entry-code-card {
            border-radius: 22px;
            border: 1px solid rgba(255, 255, 255, 0.52);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0.48));
            box-shadow:
                0 10px 28px rgba(15, 23, 42, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.72);
        }

        .entry-code-card td {
            padding: 18px 22px;
        }

        .entry-code-label {
            color: rgba(15, 23, 42, 0.68);
            font-size: 10px;
            line-height: 14px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            margin: 0 0 8px;
        }

        .entry-code-value {
            color: #0f172a;
            font-size: 17px;
            line-height: 22px;
            font-weight: 800;
            letter-spacing: 0.12em;
            margin: 0;
        }

        .entry-code-help {
            color: rgba(15, 23, 42, 0.72);
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            margin: 8px 0 0;
        }

        .maps-location-note {
            color: #ffffff;
            font-size: 20px;
            line-height: 28px;
            font-weight: 800;
            text-align: center;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.45);
            margin: 16px 0 0;
            text-decoration: none;
        }

        .maps-location-icon {
            width: 30px;
            height: 30px;
            vertical-align: middle;
            margin-right: 10px;
        }

        .button-table {
            margin: 0 auto;
        }

        .button-link {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.95);
            color: #0956c8;
            font-size: 15px;
            line-height: 20px;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 12px 24px rgba(2, 132, 199, 0.24);
        }

        .footer-card td {
            padding: 16px 10px;
        }

        .footer-heading {
            color: #000000;
            font-size: 9px;
            line-height: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0 0 6px;
        }

        .footer-text {
            color: #000000;
            font-size: 14px;
            line-height: 22px;
            font-weight: 500;
            margin: 0;
        }

        .logo-inline {
            display: inline-block;
            vertical-align: middle;
            margin: 2px 3px;
        }

        .logo-venue {
            max-height: 24px;
            width: auto;
        }

        .logo-sponsor {
            max-height: 24px;
            width: auto;
        }

        .logo-sponsor-ditp {
            max-height: 18px;
            width: auto;
            background: #ffffff;
            padding: 2px;
            border-radius: 2px;
        }

        .logo-sponsor-amazing {
            max-height: 22px;
            width: auto;
        }

        .logo-media {
            width: 30px;
            height: 30px;
            border-radius: 50%;
        }

        .spacer-24 {
            height: 24px;
            line-height: 24px;
            font-size: 24px;
        }

        @media screen and (max-width: 600px) {
            .shell {
                width: 100% !important;
            }

            .top-pad,
            .bottom-pad {
                padding-left: 14px !important;
                padding-right: 14px !important;
            }

            .logo {
                max-width: 300px !important;
            }

            .event-date {
                font-size: 42px !important;
                line-height: 42px !important;
            }

            .event-venue {
                font-size: 13px !important;
                line-height: 20px !important;
            }

            .identity-card td {
                padding: 24px 20px !important;
            }

            .stack-col,
            .stack-col td {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .footer-cell {
                padding-bottom: 14px !important;
                border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
            }

            .footer-cell-last {
                padding-bottom: 0 !important;
                border-bottom: 0 !important;
            }
        }
    </style>
</head>

<body>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="width:100%; background-color:#e5f5f9;">
        <tr>
            <td align="center" style="padding:24px 0;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="shell"
                    style="width:100%; max-width:600px; margin:0 auto;">
                    <tr>
                        <td class="ticket-surface" background="{{ $backgroundSrc }}" bgcolor="#038cb2"
                            style="background-color:#038cb2; background-image:url('{{ $backgroundSrc }}'); background-repeat:no-repeat; background-position:center bottom; background-size:cover;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" class="top-pad" style="padding:22px 20px 0;">
                                        @if($logoSrc)
                                            <img src="{{ $logoSrc }}" alt="Songkran Festival Logo" width="380" class="logo"
                                                style="width:100%; max-width:380px; margin:0 auto 8px; display:block;">
                                        @else
                                            <div
                                                style="font-size:28px; line-height:34px; font-weight:900; letter-spacing:0.04em; text-transform:uppercase; color:#ffffff; text-align:center; margin:0 auto 10px;">
                                                Songkran Festival
                                            </div>
                                        @endif

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            border="0">
                                            <tr>
                                                <td class="event-block"
                                                    style="color:#ffffff; text-align:center; text-shadow:1px 1px 4px rgba(0,0,0,0.35);">
                                                    <p class="event-time"
                                                        style="font-size:18px; line-height:24px; font-weight:800; margin:0;">
                                                        12PM-12AM
                                                    </p>
                                                    <p class="event-date"
                                                        style="font-size:54px; line-height:54px; font-weight:900; letter-spacing:1px; margin:8px 0 6px;">
                                                        9-19 APRIL
                                                    </p>
                                                    <p class="event-venue"
                                                        style="font-size:14px; line-height:22px; font-weight:800; margin:0;">
                                                        @GF FORECOURT OUTDOOR CARPARK, 1 UTAMA
                                                    </p>
                                                    <p class="event-subtitle"
                                                        style="font-size:10px; line-height:18px; font-weight:800; letter-spacing:0.06em; text-transform:uppercase; margin:6px 0 0;">
                                                        MALAYSIA'S PREMIER SONGKRAN FESTIVAL
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        @if($hasNotice)
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                                border="0" style="width:100%; max-width:460px; margin:24px auto 0;">
                                                <tr>
                                                    <td class="notice-card"
                                                        style="border-radius:22px; border:1px solid rgba(255,255,255,0.42); background:linear-gradient(135deg, rgba(255,255,255,0.72), rgba(255,255,255,0.48)); box-shadow:0 14px 30px rgba(15,23,42,0.12), inset 0 1px 0 rgba(255,255,255,0.72);">
                                                        <table role="presentation" width="100%" cellpadding="0"
                                                            cellspacing="0" border="0">
                                                            <tr>
                                                                <td align="center" style="padding:18px 22px;">
                                                                    @if($noticeEyebrow !== '')
                                                                        <p class="notice-eyebrow"
                                                                            style="color:rgba(15,23,42,0.68); font-size:10px; line-height:14px; font-weight:800; letter-spacing:0.16em; text-transform:uppercase; margin:0 0 8px;">
                                                                            {{ $noticeEyebrow }}
                                                                        </p>
                                                                    @endif
                                                                    @if($noticeTitle !== '')
                                                                        <p class="notice-title"
                                                                            style="color:#0f172a; font-size:24px; line-height:30px; font-weight:800; margin:0 0 10px;">
                                                                            {{ $noticeTitle }}
                                                                        </p>
                                                                    @endif
                                                                    @if($noticeCopy !== '')
                                                                        <p class="notice-copy"
                                                                            style="color:rgba(15,23,42,0.78); font-size:14px; line-height:22px; font-weight:700; margin:0;">
                                                                            {{ $noticeCopy }}
                                                                        </p>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            border="0">
                                            <tr>
                                                <td align="center"
                                                    style="height:{{ $heroSpacerHeight }}px; line-height:{{ $heroSpacerHeight }}px; font-size:{{ $heroSpacerHeight }}px;">&nbsp;</td>
                                            </tr>
                                        </table>

                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                            class="qr-wrap" style="margin:0 auto;">
                                            <tr>
                                                <td class="qr-card"
                                                    style="width:{{ $qrCardWidth }}px; border-radius:14px; background:#ffffff; box-shadow:0 8px 24px rgba(0,0,0,0.22);">
                                                    <table role="presentation" cellpadding="0" cellspacing="0"
                                                        border="0" width="{{ $qrCardWidth }}">
                                                        <tr>
                                                            <td align="center"
                                                                style="padding:13px 13px {{ $entryCodeDisplay !== '' ? '0' : '13px' }};">
                                                                <img src="{{ $message->embedData($qrPngBinary, $qrImageFilename, 'image/png') }}"
                                                                    alt="{{ $qrAltText }}" width="160"
                                                                    style="width:160px; height:160px; display:block;">
                                                            </td>
                                                        </tr>
                                                        @if($entryCodeDisplay !== '')
                                                            <tr>
                                                                <td align="center" style="padding:14px 13px 18px;">
                                                                    <table role="presentation" width="100%"
                                                                        cellpadding="0" cellspacing="0" border="0">
                                                                        <tr>
                                                                            <td class="entry-code-card"
                                                                                style="border-radius:22px; border:1px solid rgba(255,255,255,0.52); background:linear-gradient(135deg, rgba(255,255,255,0.7), rgba(255,255,255,0.48)); box-shadow:0 10px 28px rgba(15,23,42,0.08), inset 0 1px 0 rgba(255,255,255,0.72);">
                                                                                <table role="presentation"
                                                                                    width="100%" cellpadding="0"
                                                                                    cellspacing="0" border="0">
                                                                                    <tr>
                                                                                        <td align="center"
                                                                                            style="padding:18px 22px;">
                                                                                            <p class="entry-code-label"
                                                                                                style="color:rgba(15,23,42,0.68); font-size:10px; line-height:14px; font-weight:800; letter-spacing:0.16em; text-transform:uppercase; margin:0 0 8px;">
                                                                                                Entry Code
                                                                                            </p>
                                                                                            <p class="entry-code-value"
                                                                                                style="color:#0f172a; font-size:17px; line-height:22px; font-weight:800; letter-spacing:0.12em; margin:0;">
                                                                                                {{ $entryCodeDisplay }}
                                                                                            </p>
                                                                                            <p class="entry-code-help"
                                                                                                style="color:rgba(15,23,42,0.72); font-size:12px; line-height:18px; font-weight:700; margin:8px auto 0; max-width:220px;">
                                                                                                Use this code for manual
                                                                                                lookup if your QR cannot
                                                                                                be scanned at the gate.
                                                                                            </p>
                                                                                        </td>
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            border="0">
                                            <tr>
                                                <td align="center" style="padding:20px 0 8px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0"
                                                        border="0" class="button-table" style="margin:0 auto;">
                                                        <tr>
                                                            <td align="center" bgcolor="#FFFFFF"
                                                                style="border-radius:999px; background:rgba(255,255,255,0.95); box-shadow:0 12px 24px rgba(2,132,199,0.24);">
                                                                <a href="{{ $ticketUrl }}" target="_blank"
                                                                    rel="noopener noreferrer" class="button-link"
                                                                    style="display:inline-block; padding:14px 28px; border-radius:999px; background:rgba(255,255,255,0.95); color:#0956c8; font-size:15px; line-height:20px; font-weight:800; text-decoration:none;">
                                                                    {{ $ticketButtonLabel }}
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                            width="85%" style="width:85%; max-width:320px; margin:28px auto 0;">
                                            <tr>
                                                <td class="identity-card"
                                                    style="background:rgba(255,255,255,0.36); border:1px solid rgba(255,255,255,0.32); border-radius:20px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
                                                    <table role="presentation" width="100%" cellpadding="0"
                                                        cellspacing="0" border="0">
                                                        <tr>
                                                            <td align="center" style="padding:30px 35px 28px;">
                                                                <p class="identity-name"
                                                                    style="color:#000000; font-size:17px; line-height:24px; font-weight:800; letter-spacing:0.03em; text-transform:uppercase; margin:0 0 4px;">
                                                                    {{ mb_strtoupper($fullName) }}
                                                                </p>
                                                                <p class="identity-number"
                                                                    style="color:rgba(0,0,0,0.9); font-size:14px; line-height:20px; font-weight:600; margin:0;">
                                                                    {{ $identityDisplay }}
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>

                                <tr>
                                    <td align="center" style="padding:22px 20px 12px;">
                                        <p class="ticket-validity-note"
                                            style="color:#ffffff; font-size:13px; line-height:18px; font-weight:800; letter-spacing:0.04em; text-transform:uppercase; text-align:center; text-shadow:1px 1px 3px rgba(0,0,0,0.45); margin:0;">
                                            Ticket valid from 9-19 April 2026
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="bottom-pad" style="padding:32px 20px 28px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            border="0">
                                            <tr>
                                                <td align="center">
                                                    <p class="message-title"
                                                        style="color:#ffffff; font-size:20px; line-height:28px; font-weight:800; text-align:center; text-shadow:1px 1px 3px rgba(0,0,0,0.45); margin:0 0 10px;">
                                                        {{ $messageTitle }}
                                                    </p>
                                                    <p class="message-copy"
                                                        style="color:#ffffff; font-size:14px; line-height:22px; font-weight:700; text-align:center; text-shadow:1px 1px 3px rgba(0,0,0,0.45); margin:0;">
                                                        {!! $messageCopy !!}
                                                    </p>
                                                    @if($showMapsLink)
                                                        <a href="https://maps.app.goo.gl/UEPceTqzjesMy1ze8?g_st=iw" target="_blank"
                                                            rel="noopener noreferrer" class="maps-location-note"
                                                            style="color:#ffffff; font-size:20px; line-height:28px; font-weight:800; text-align:center; text-shadow:1px 1px 3px rgba(0,0,0,0.45); margin:16px 0 0; text-decoration:none; display:inline-block;">
                                                            <svg class="maps-location-icon" viewBox="0 0 24 24"
                                                                aria-hidden="true"
                                                                style="width:30px; height:30px; vertical-align:middle; margin-right:10px;">
                                                                <path fill="#4285F4"
                                                                    d="M12 2C8.13 2 5 5.13 5 9c0 4.91 5.37 11.62 6.08 12.49a1.18 1.18 0 0 0 1.84 0C13.63 20.62 19 13.91 19 9c0-3.87-3.13-7-7-7Z" />
                                                                <path fill="#34A853"
                                                                    d="M12 2a6.96 6.96 0 0 0-5.17 2.29l4.24 4.24A2.5 2.5 0 0 1 14.5 12l4.21 4.21C18.9 13.91 19 11.15 19 9c0-3.87-3.13-7-7-7Z" />
                                                                <path fill="#FBBC04"
                                                                    d="M7.04 4.06A6.97 6.97 0 0 0 5 9c0 4.91 5.37 11.62 6.08 12.49.49.61 1.27.61 1.84 0 .29-.36 1.42-1.78 2.63-3.63L7.04 9.35A2.49 2.49 0 0 1 7.04 4.06Z" />
                                                                <circle cx="12" cy="9" r="3.2" fill="#EA4335" />
                                                            </svg>
                                                            <span style="vertical-align:middle;">Maps to Location</span>
                                                        </a>
                                                    @endif
                                                    @if($supportNote !== '')
                                                        <p class="support-note"
                                                            style="color:rgba(255,255,255,0.9); font-size:13px; line-height:20px; font-weight:700; text-align:center; text-shadow:1px 1px 3px rgba(0,0,0,0.35); margin:16px auto 0; max-width:420px;">
                                                            {{ $supportNote }}
                                                        </p>
                                                    @endif
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="spacer-24" align="center"
                                                    style="height:24px; line-height:24px; font-size:24px;">&nbsp;</td>
                                            </tr>

                                            <tr>
                                                <td class="spacer-24" align="center"
                                                    style="height:24px; line-height:24px; font-size:24px;">&nbsp;</td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    <table role="presentation" width="100%" cellpadding="0"
                                                        cellspacing="0" border="0" class="footer-card"
                                                        style="width:100%; background:rgba(255,255,255,0.36); border:1px solid rgba(255,255,255,0.32); border-radius:20px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
                                                        <tr>
                                                            <td style="padding:16px 10px;">
                                                                <table role="presentation" width="100%" cellpadding="0"
                                                                    cellspacing="0" border="0">
                                                                    <tr>
                                                                        <td width="18%" align="center" valign="top"
                                                                            class="stack-col footer-cell"
                                                                            style="width:18%; padding:0 6px;">
                                                                            <p class="footer-heading"
                                                                                style="color:#000000; font-size:9px; line-height:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; margin:0 0 6px;">
                                                                                Organiser
                                                                            </p>
                                                                            <p class="footer-text"
                                                                                style="color:#000000; font-size:14px; line-height:22px; font-weight:500; margin:0;">
                                                                                eq solutions
                                                                            </p>
                                                                        </td>
                                                                        <td width="18%" align="center" valign="top"
                                                                            class="stack-col footer-cell"
                                                                            style="width:18%; padding:0 6px;">
                                                                            <p class="footer-heading"
                                                                                style="color:#000000; font-size:9px; line-height:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; margin:0 0 6px;">
                                                                                Venue Sponsor
                                                                            </p>
                                                                            @if($venueSponsorSrc)
                                                                                <img src="{{ $venueSponsorSrc }}"
                                                                                    alt="1 Utama"
                                                                                    class="logo-inline logo-venue"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 3px; max-height:24px; width:auto;">
                                                                            @endif
                                                                        </td>
                                                                        <td width="42%" align="center" valign="top"
                                                                            class="stack-col footer-cell"
                                                                            style="width:42%; padding:0 6px;">
                                                                            <p class="footer-heading"
                                                                                style="color:#000000; font-size:9px; line-height:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; margin:0 0 6px;">
                                                                                Sponsors
                                                                            </p>
                                                                            @if($sponsorEmbassySrc)
                                                                                <img src="{{ $sponsorEmbassySrc }}"
                                                                                    alt="Royal Thai Embassy"
                                                                                    class="logo-inline logo-sponsor"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 3px; max-height:24px; width:auto;">
                                                                            @endif
                                                                            @if($sponsorDitpSrc)
                                                                                <img src="{{ $sponsorDitpSrc }}" alt="DITP"
                                                                                    class="logo-inline logo-sponsor-ditp"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 3px; max-height:18px; width:auto; background:#ffffff; padding:2px; border-radius:2px;">
                                                                            @endif
                                                                            @if($sponsorAmazingThailandSrc)
                                                                                <img src="{{ $sponsorAmazingThailandSrc }}"
                                                                                    alt="Amazing Thailand"
                                                                                    class="logo-inline logo-sponsor-amazing"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 3px; max-height:22px; width:auto;">
                                                                            @endif
                                                                            @if($sponsorSinghaSrc)
                                                                                <img src="{{ $sponsorSinghaSrc }}"
                                                                                    alt="Singha"
                                                                                    class="logo-inline logo-sponsor"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 3px; max-height:24px; width:auto;">
                                                                            @endif
                                                                            @if($sponsorSnakeBrandSrc)
                                                                                <img src="{{ $sponsorSnakeBrandSrc }}"
                                                                                    alt="Snake Brand"
                                                                                    class="logo-inline logo-sponsor"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 3px; max-height:24px; width:auto;">
                                                                            @endif
                                                                            @if($sponsorThaigoSrc)
                                                                                <img src="{{ $sponsorThaigoSrc }}"
                                                                                    alt="Thaigo"
                                                                                    class="logo-inline logo-sponsor"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 3px; max-height:24px; width:auto;">
                                                                            @endif
                                                                            @if($sponsorLayer0Src)
                                                                                <img src="{{ $sponsorLayer0Src }}"
                                                                                    alt="Layer 0"
                                                                                    class="logo-inline logo-sponsor"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 3px; max-height:24px; width:auto;">
                                                                            @endif
                                                                        </td>
                                                                        <td width="22%" align="center" valign="top"
                                                                            class="stack-col footer-cell footer-cell-last"
                                                                            style="width:22%; padding:0 6px;">
                                                                            <p class="footer-heading"
                                                                                style="color:#000000; font-size:9px; line-height:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; margin:0 0 6px;">
                                                                                Media Partners
                                                                            </p>
                                                                            @if($mediaWobSrc)
                                                                                <img src="{{ $mediaWobSrc }}" alt="WOB"
                                                                                    class="logo-inline logo-media"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 4px; width:30px; height:30px; border-radius:50%;">
                                                                            @endif
                                                                            @if($mediaNoodouSrc)
                                                                                <img src="{{ $mediaNoodouSrc }}"
                                                                                    alt="NOODOU"
                                                                                    class="logo-inline logo-media"
                                                                                    style="display:inline-block; vertical-align:middle; margin:2px 4px; width:30px; height:30px; border-radius:50%;">
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
