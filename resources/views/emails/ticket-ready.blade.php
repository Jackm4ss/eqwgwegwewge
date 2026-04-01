@php
    $mailMessage = isset($message) && is_object($message) ? $message : null;
    $publicImageUrl = static function (string $filename): string {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $resolvedBaseUrl = $baseUrl !== '' ? $baseUrl : rtrim(url('/'), '/');

        return $resolvedBaseUrl . '/images/' . rawurlencode($filename);
    };

    $backgroundSrc = $publicImageUrl('BACKGROUND.jpg');
    $logoSrc = $publicImageUrl('Songkran logo.png');
    $eventHeaderSrc = $publicImageUrl('ticket-event-header-email.png');
    $mapToLocationSrc = $publicImageUrl('Map to Location.png');
    $sponsorsFooterFilename = 'email-sponsors-footer.png';
    $sponsorsFooterSrc = file_exists(public_path('images/' . $sponsorsFooterFilename))
        ? $publicImageUrl($sponsorsFooterFilename)
        : '';

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
    $showValidityPill = (bool) ($showValidityPill ?? true);
    $emailPreviewText = trim((string) ($emailPreviewText ?? 'Your Songkran Festival 2026 ticket is ready. Open your ticket and present your QR code at the gate.'));
    $qrAltText = trim((string) ($qrAltText ?? 'Songkran Festival ticket QR code'));
    $qrImageFilename = trim((string) ($qrImageFilename ?? 'ticket-qrcode.png'));
    $ticketId = trim((string) ($ticket['ticket_id'] ?? ''));
    $ticketQrSrc = trim((string) ($ticketQrUrl ?? ''));
    $cardSurfaceColor = '#F8FCFF';
    $cardBorderColor = '#C8DDF2';
    $cardHeadingColor = '#0A2F63';
    $cardTitleColor = '#0A2F63';
    $cardCopyColor = '#294A72';
    $buttonSurfaceColor = '#F8FCFF';
    $buttonTextColor = '#0A2F63';

    if ($ticketQrSrc === '' && $ticketId !== '') {
        $ticketQrSrc = \Illuminate\Support\Facades\URL::signedRoute('ticket.qr', ['ticketId' => $ticketId]);
    }

    if ($ticketQrSrc === '' && $mailMessage && isset($qrPngBinary) && $qrPngBinary !== '') {
        $ticketQrSrc = $mailMessage->embedData($qrPngBinary, $qrImageFilename, 'image/png');
    }
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="only light">
    <title>{{ $emailDocumentTitle }}</title>
    <style>
        body,
        table,
        td,
        p,
        a {
            font-family: Arial, Helvetica, sans-serif;
            mso-line-height-rule: exactly;
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

        .event-header-image {
            width: 100%;
            max-width: 430px;
            height: auto;
            margin: 0 auto;
            display: block;
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
            border: 1px solid #C8DDF2;
            background-color: #F8FCFF;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
        }

        .notice-card td {
            padding: 18px 22px;
        }

        .notice-eyebrow {
            color: #0A2F63;
            font-size: 10px;
            line-height: 14px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            margin: 0 0 8px;
        }

        .notice-title {
            color: #0A2F63;
            font-size: 24px;
            line-height: 30px;
            font-weight: 800;
            margin: 0 0 10px;
        }

        .notice-copy {
            color: #294A72;
            font-size: 14px;
            line-height: 22px;
            font-weight: 700;
            margin: 0;
        }

        .qr-card {
            border-radius: 14px;
        }

        .qr-card td {
            padding: 13px;
        }

        .identity-card {
            background-color: #00C0FD;
            border: 1px solid #00C0FD;
            border-radius: 20px;
            box-shadow: 0 10px 24px rgba(2, 12, 27, 0.24);
        }

        .identity-card td {
            padding: 30px 35px 28px;
        }

        .identity-name {
            color: #052F4A;
            font-size: 17px;
            line-height: 24px;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin: 0 0 4px;
        }

        .identity-number {
            color: #052F4A;
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
            text-shadow: none;
            margin: 0;
        }

        .entry-code-card {
            border-radius: 22px;
            border: 1px solid #4fa6ff;
            background-color: #0a2f63;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.18);
        }

        .entry-code-card td {
            padding: 18px 22px;
        }

        .entry-code-label {
            color: #bfe0ff;
            font-size: 10px;
            line-height: 14px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            margin: 0 0 8px;
        }

        .entry-code-value {
            color: #ffffff;
            font-size: 17px;
            line-height: 22px;
            font-weight: 800;
            letter-spacing: 0.12em;
            margin: 0;
        }

        .entry-code-help {
            color: #d7e8ff;
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            margin: 8px 0 0;
        }

        .maps-location-note {
            margin: 16px 0 0;
            text-decoration: none;
            display: inline-block;
        }

        .maps-location-image {
            width: 220px;
            max-width: 100%;
            height: auto;
            display: block;
        }

        .button-table {
            margin: 0 auto;
        }

        .button-link {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 999px;
            background-color: #00C0FD;
            color: #052F4A;
            font-size: 15px;
            line-height: 20px;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 12px 24px rgba(2, 12, 27, 0.2);
        }

        .footer-sponsors-image {
            width: 100%;
            max-width: 520px;
            height: auto;
            display: block;
            margin: 0 auto;
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

        }
    </style>
</head>

<body>
    <div
        style="display:none; font-size:1px; color:#e5f5f9; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        {{ $emailPreviewText }}
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="width:100%; background-color:#e5f5f9;">
        <tr>
            <td align="center" style="padding:24px 0;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="shell"
                    style="width:100%; max-width:600px; margin:0 auto;">
                    <tr>
                        <td class="ticket-surface" background="{{ $backgroundSrc }}" bgcolor="#038cb2"
                            style="background-color:#038cb2; background-image:url('{{ $backgroundSrc }}'); background-repeat:no-repeat; background-position:center bottom; background-size:cover;">
                            <!--[if gte mso 9]>
                            <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false"
                                style="width:600px;">
                                <v:fill type="frame" src="{{ $backgroundSrc }}" color="#038cb2" />
                                <v:textbox inset="0,0,0,0">
                            <![endif]-->
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
                                                    @if($eventHeaderSrc)
                                                        <img src="{{ $eventHeaderSrc }}"
                                                            alt="12PM-12AM, 9-19 APRIL, at GF Forecourt Outdoor Carpark 1 Utama, Malaysia's premier Songkran festival"
                                                            width="430" class="event-header-image"
                                                            style="width:100%; max-width:430px; height:auto; margin:0 auto; display:block;">
                                                    @else
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
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>

                                        @if($hasNotice)
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                                border="0" style="width:100%; max-width:460px; margin:24px auto 0;">
                                                <tr>
                                                    <td class="notice-card"
                                                        style="border-radius:22px; border:1px solid #C8DDF2; background-color:#F8FCFF; box-shadow:0 14px 30px rgba(15,23,42,0.18);">
                                                        <table role="presentation" width="100%" cellpadding="0"
                                                            cellspacing="0" border="0">
                                                            <tr>
                                                                <td align="center" style="padding:18px 22px;">
                                                                    @if($noticeEyebrow !== '')
                                                                        <p class="notice-eyebrow"
                                                                            style="color:#0A2F63; font-size:10px; line-height:14px; font-weight:800; letter-spacing:0.16em; text-transform:uppercase; margin:0 0 8px;">
                                                                            {{ $noticeEyebrow }}
                                                                        </p>
                                                                    @endif
                                                                    @if($noticeTitle !== '')
                                                                        <p class="notice-title"
                                                                            style="color:#0A2F63; font-size:24px; line-height:30px; font-weight:800; margin:0 0 10px;">
                                                                            {{ $noticeTitle }}
                                                                        </p>
                                                                    @endif
                                                                    @if($noticeCopy !== '')
                                                                        <p class="notice-copy"
                                                                            style="color:#294A72; font-size:14px; line-height:22px; font-weight:700; margin:0;">
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
                                                    style="height:{{ $heroSpacerHeight }}px; line-height:{{ $heroSpacerHeight }}px; font-size:{{ $heroSpacerHeight }}px;">
                                                    &nbsp;</td>
                                            </tr>
                                        </table>

                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                            class="qr-wrap" style="margin:0 auto;">
                                            <tr>
                                                <td class="qr-card"
                                                    style="width:{{ $qrCardWidth }}px; border-radius:14px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0"
                                                        border="0" width="{{ $qrCardWidth }}">
                                                        <tr>
                                                            <td align="center"
                                                                style="padding:13px;">
                                                                @if($ticketQrSrc !== '')
                                                                    <img src="{{ $ticketQrSrc }}"
                                                                        alt="{{ $qrAltText }}" width="160" height="160"
                                                                        style="width:160px; height:160px; display:block;">
                                                                @else
                                                                    <p
                                                                        style="margin:0; color:#ffffff; font-size:13px; line-height:20px; font-weight:700; text-align:center;">
                                                                        QR code available from the Open Ticket button.
                                                                    </p>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        {{-- Temporary: hide Entry Code block. --}}
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            border="0">
                                            <tr>
                                                <td align="center" style="padding:20px 0 8px;">
                                                    <!--[if mso]>
                                                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml"
                                                        xmlns:w="urn:schemas-microsoft-com:office:word"
                                                        href="{{ $ticketUrl }}" style="height:48px; v-text-anchor:middle; width:170px;"
                                                        arcsize="50%" stroke="f" fillcolor="#00C0FD">
                                                        <w:anchorlock/>
                                                        <center
                                                            style="color:#052F4A; font-family:Arial, Helvetica, sans-serif; font-size:15px; font-weight:800;">
                                                            {{ $ticketButtonLabel }}
                                                        </center>
                                                    </v:roundrect>
                                                    <![endif]-->
                                                    <!--[if !mso]><!-- -->
                                                        <table role="presentation" cellpadding="0" cellspacing="0"
                                                            border="0" class="button-table" style="margin:0 auto;">
                                                        <tr>
                                                            <td align="center" bgcolor="#00C0FD"
                                                                style="border-radius:999px; background-color:#00C0FD; box-shadow:0 12px 24px rgba(2,12,27,0.2);">
                                                                <a href="{{ $ticketUrl }}" target="_blank"
                                                                    rel="noopener noreferrer" class="button-link"
                                                                    style="display:inline-block; padding:14px 28px; border-radius:999px; background-color:#00C0FD; color:#052F4A; -webkit-text-fill-color:#052F4A; font-size:15px; line-height:20px; font-weight:800; text-decoration:none;">
                                                                    {{ $ticketButtonLabel }}
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <!--<![endif]-->
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                            width="85%" style="width:85%; max-width:320px; margin:28px auto 0;">
                                            <tr>
                                                <td class="identity-card" bgcolor="#00C0FD"
                                                    style="background-color:#00C0FD; border:1px solid #00C0FD; border-radius:20px; box-shadow:0 10px 24px rgba(2,12,27,0.24);">
                                                    <table role="presentation" width="100%" cellpadding="0"
                                                        cellspacing="0" border="0">
                                                        <tr>
                                                            <td align="center" style="padding:30px 35px 28px;">
                                                                <p class="identity-name"
                                                                    style="color:#052F4A; -webkit-text-fill-color:#052F4A; font-size:17px; line-height:24px; font-weight:800; letter-spacing:0.03em; text-transform:uppercase; margin:0 0 4px;">
                                                                    {{ mb_strtoupper($fullName) }}
                                                                </p>
                                                                <p class="identity-number"
                                                                    style="color:#052F4A; -webkit-text-fill-color:#052F4A; font-size:14px; line-height:20px; font-weight:600; margin:0;">
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
                                            style="color:#ffffff; font-size:13px; line-height:18px; font-weight:800; letter-spacing:0.04em; text-transform:uppercase; text-align:center; text-shadow:none; margin:0;">
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
                                                        <a href="https://maps.app.goo.gl/UEPceTqzjesMy1ze8?g_st=iw"
                                                            target="_blank" rel="noopener noreferrer"
                                                            class="maps-location-note"
                                                            style="margin:16px 0 0; text-decoration:none; display:inline-block;">
                                                            <img src="{{ $mapToLocationSrc }}" alt="Map to Location"
                                                                class="maps-location-image"
                                                                style="display:block; width:220px; max-width:100%; height:auto;">
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
                                                        cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="padding:0 6px;">
                                                                @if($sponsorsFooterSrc !== '')
                                                                    <img src="{{ $sponsorsFooterSrc }}"
                                                                        alt="Songkran Festival organiser, venue sponsor, sponsors, and media partners"
                                                                        width="520" class="footer-sponsors-image"
                                                                        style="display:block; width:100%; max-width:520px; height:auto; margin:0 auto;">
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
                            <!--[if gte mso 9]>
                                </v:textbox>
                            </v:rect>
                            <![endif]-->
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
