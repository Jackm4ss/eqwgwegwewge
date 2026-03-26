@php
    $embedAsset = static function (?string $path) use ($message): ?string {
        return ($path && is_file($path)) ? $message->embed($path) : null;
    };

    $backgroundUrl = 'https://i.ibb.co/BHPT4KN9/BACKGROUND.jpg';
    $logoCid = $embedAsset($templateAssets['logo'] ?? null);
    $venueSponsorCid = $embedAsset($templateAssets['venueSponsor'] ?? null);
    $sponsorEmbassyCid = $embedAsset($templateAssets['sponsorEmbassy'] ?? null);
    $sponsorDitpCid = $embedAsset($templateAssets['sponsorDitp'] ?? null);
    $sponsorAmazingThailandCid = $embedAsset($templateAssets['sponsorAmazingThailand'] ?? null);
    $sponsorSinghaCid = $embedAsset($templateAssets['sponsorSingha'] ?? null);
    $sponsorSnakeBrandCid = $embedAsset($templateAssets['sponsorSnakeBrand'] ?? null);
    $mediaWobCid = $embedAsset($templateAssets['mediaWob'] ?? null);
    $mediaNoodouCid = $embedAsset($templateAssets['mediaNoodou'] ?? null);

    $identityNumber = trim((string) ($user['identity_number'] ?? ''));
    $identityDisplay = $identityNumber !== '' ? $identityNumber : '-';
    $fullName = trim((string) ($user['full_name'] ?? 'Guest'));
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Songkran Festival E-Ticket Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body,
        html {
            width: 100%;
            height: 100%;
            font-family: 'Outfit', Arial, sans-serif;
            background-color: #e5f5f9;
        }

        .ticket-wrapper {
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            background-image: url('{{ $backgroundUrl }}');
            background-size: 100% calc(100% + 2cm);
            background-position: center bottom;
            background-repeat: no-repeat;
            background-color: #038cb2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #ffffff;
            text-align: center;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
        }

        .top-section {
            padding: 20px 20px 0;
        }

        .bottom-section {
            padding: 30px 20px 120px;
        }

        .middle-gap {
            flex-grow: 1;
            min-height: 250px;
        }

        .logo {
            width: 100%;
            max-width: 500px;
            height: auto;
            margin-bottom: 0;
            filter: drop-shadow(0 10px 20px rgba(0, 150, 200, 0.4));
        }

        .event-info {
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.4);
            letter-spacing: 0.5px;
            margin-top: -15px;
        }

        .event-time {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .event-date {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .event-venue {
            font-size: 1.1rem;
            font-weight: 800;
            margin: 10px 0 5px;
        }

        .event-subtitle {
            font-size: 0.9rem;
            font-weight: 700;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .qr-card {
            background-color: #ffffff;
            width: 180px;
            height: 180px;
            margin: 25px auto 0;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .qr-card img {
            display: block;
            width: 160px;
            height: 160px;
            object-fit: contain;
        }

        .glass-card {
            background-color: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            color: #000000;
            margin: 25px auto 0;
        }

        #ticket-info-card {
            margin-top: 30px;
            padding: 30px 35px;
            width: fit-content;
            min-width: 260px;
            max-width: 85%;
            min-height: 80px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .ticket-user-name {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .ticket-user-passport {
            font-size: 1.1rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .message-box {
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            margin-bottom: 30px;
        }

        .msg-title {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .msg-text {
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.5;
            max-width: 520px;
            margin: 0 auto;
        }

        #bottom-info-card {
            width: 100%;
            max-width: 95%;
            padding: 15px 10px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-evenly;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 0;
        }

        .footer-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .col-title {
            font-size: 0.55rem;
            font-weight: 800;
            color: #000000;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .logo-row {
            display: flex;
            gap: 6px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo-organiser-text {
            font-size: 0.95rem;
            font-family: sans-serif;
            font-weight: 500;
            letter-spacing: -0.5px;
            padding-top: 5px;
        }

        .logo-1utama {
            height: 24px;
            width: auto;
            object-fit: contain;
        }

        .logo-sponsor {
            height: 24px;
            width: auto;
            object-fit: contain;
        }

        .logo-sponsor-ditp {
            height: 18px;
            width: auto;
            object-fit: contain;
            background: white;
            padding: 2px;
            border-radius: 2px;
        }

        .logo-sponsor-amazing {
            height: 22px;
            width: auto;
            object-fit: contain;
        }

        .logo-media {
            height: 30px;
            width: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .fallback-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 22px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.95);
            color: #0369a1;
            font-size: 0.95rem;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 12px 24px rgba(2, 132, 199, 0.24);
        }

        @media (max-width: 480px) {
            .event-date {
                font-size: 2.2rem;
            }

            .event-venue {
                font-size: 0.95rem;
            }

            .msg-title {
                font-size: 1.3rem;
            }

            .msg-text {
                font-size: 0.85rem;
            }

            #bottom-info-card {
                flex-direction: column;
                gap: 15px;
                border-radius: 15px;
            }

            .footer-col {
                width: 100%;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                padding-bottom: 15px;
            }

            .footer-col:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
        }
    </style>
</head>

<body>

    <div style="margin:0; padding:24px 0; background-color:#e5f5f9;">
        <div class="ticket-wrapper"
            style="background-image:url('{{ $backgroundUrl }}'); background-size:100% calc(100% + 2cm); background-position:center bottom; background-repeat:no-repeat; background-color:#038cb2;">
            <div class="top-section">
                @if($logoCid)
                    <img src="{{ $logoCid }}" alt="Songkran Festival Logo" class="logo">
                @else
                    <div
                        style="font-size:28px; font-weight:900; letter-spacing:0.04em; text-transform:uppercase; margin-bottom:20px;">
                        Songkran Festival
                    </div>
                @endif

                <div class="event-info">
                    <p class="event-time">12PM-12AM</p>
                    <p class="event-date">9 - 19 APRIL</p>
                    <p class="event-venue">@GF FORECOURT OUTDOOR CARPARK, 1 UTAMA</p>
                    <p class="event-subtitle">MALAYSIA'S PREMIER SONGKRAN FESTIVAL</p>
                </div>

                <div id="qrcode-container" class="qr-card">
                    <img src="{{ $message->embedData($qrPngBinary, 'ticket-qrcode.png', 'image/png') }}"
                        alt="Songkran Festival ticket QR code">
                </div>

                <div id="ticket-info-card" class="glass-card">
                    <div id="ticket-user-name" class="ticket-user-name">{{ mb_strtoupper($fullName) }}</div>
                    <div id="ticket-user-passport" class="ticket-user-passport">{{ $identityDisplay }}</div>
                </div>
            </div>


            <div class="bottom-section">
                <div class="message-box">
                    <div class="msg-title">Thank you for your registration.</div>
                    <div class="msg-text">
                        Please present your QR code and registered valid ID / passport at the gate.<br>
                        QR only required to scan once per day
                    </div>
                </div>

                <div style="margin:0 0 24px; color:#ffffff;">
                    <!-- <div style="font-size:14px; line-height:1.6; font-weight:700; margin-bottom:8px;">Ticket Code</div>
                    <div style="font-size:22px; line-height:1.5; font-weight:900; letter-spacing:0.06em;">
                        {{ $ticket['ticket_code'] }}
                    </div> -->
                    <div style="margin-top:16px;">
                        <a href="{{ $ticketDownloadUrl }}" class="fallback-link">Download Ticket</a>
                    </div>
                </div>

                <div id="bottom-info-card" class="glass-card">
                    <div class="footer-col" style="flex: 1; min-width: 70px;">
                        <div class="col-title">ORGANISER</div>
                        <div class="logo-organiser-text">eq solutions</div>
                    </div>

                    <div class="footer-col" style="flex: 1; min-width: 70px;">
                        <div class="col-title">VENUE SPONSOR</div>
                        @if($venueSponsorCid)
                            <img src="{{ $venueSponsorCid }}" alt="1 Utama" class="logo-1utama">
                        @endif
                    </div>

                    <div class="footer-col" style="flex: 3; min-width: 180px;">
                        <div class="col-title">SPONSORS</div>
                        <div class="logo-row">
                            @if($sponsorEmbassyCid)
                                <img src="{{ $sponsorEmbassyCid }}" alt="Royal Thai Embassy" class="logo-sponsor">
                            @endif
                            @if($sponsorDitpCid)
                                <img src="{{ $sponsorDitpCid }}" alt="DITP" class="logo-sponsor-ditp">
                            @endif
                            @if($sponsorAmazingThailandCid)
                                <img src="{{ $sponsorAmazingThailandCid }}" alt="Amazing Thailand"
                                    class="logo-sponsor-amazing">
                            @endif
                            @if($sponsorSinghaCid)
                                <img src="{{ $sponsorSinghaCid }}" alt="Singha" class="logo-sponsor">
                            @endif
                            @if($sponsorSnakeBrandCid)
                                <img src="{{ $sponsorSnakeBrandCid }}" alt="Snake Brand" class="logo-sponsor">
                            @endif
                        </div>
                    </div>

                    <div class="footer-col" style="flex: 1.5; min-width: 90px;">
                        <div class="col-title">MEDIA PARTNERS</div>
                        <div class="logo-row" style="gap: 8px;">
                            @if($mediaWobCid)
                                <img src="{{ $mediaWobCid }}" alt="WOB" class="logo-media">
                            @endif
                            @if($mediaNoodouCid)
                                <img src="{{ $mediaNoodouCid }}" alt="NOODOU" class="logo-media">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
