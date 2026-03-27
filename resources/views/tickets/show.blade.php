@extends('layouts.app', ['title' => 'Your Ticket | Songkran Festival'])

@section('content')
    @php
        $fullName = trim((string) ($user['full_name'] ?? 'Guest'));
        $identityNumber = trim((string) ($user['identity_number'] ?? ''));
        $identityDisplay = $identityNumber !== '' ? $identityNumber : '-';

        $backgroundUrl = asset('images/BACKGROUND.jpg');
        $logoUrl = asset('images/Songkran logo.png');
        $venueSponsorUrl = asset('images/123.png');
        $sponsorEmbassyUrl = asset('images/Royal_Thai_Embassy_Seal.svg.png');
        $sponsorDitpUrl = asset('images/ditp.jpeg');
        $sponsorAmazingThailandUrl = asset('images/amazing thailand.png');
        $sponsorSinghaUrl = asset('images/singha-seeklogo.png');
        $sponsorSnakeBrandUrl = asset('images/Snake-Brand-Logo.png');
        $mediaWobUrl = asset('images/wob.png');
        $mediaNoodouUrl = asset('images/noodou.png');
    @endphp

    <style>
        body {
            margin: 0;
            background: linear-gradient(180deg, #d9f4fb 0%, #ecfbff 100%);
        }

        .ticket-page-shell {
            min-height: 100vh;
            padding: 28px 16px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            box-sizing: border-box;
        }

        .ticket-wrapper {
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            min-height: calc(100vh - 56px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #ffffff;
            text-align: center;
            overflow: hidden;
            border-radius: 28px;
            background-image: url('{{ $backgroundUrl }}');
            background-size: 100% calc(100% + 2cm);
            background-position: center bottom;
            background-repeat: no-repeat;
            background-color: #038cb2;
            box-shadow: 0 30px 70px rgba(4, 88, 120, 0.28);
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

        .qr-card svg {
            display: block;
            width: 160px;
            height: 160px;
        }

        .glass-card {
            margin: 25px auto 0;
            color: #000000;
            text-align: center;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            background-color: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
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
            font-size: 1.4rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .message-box {
            margin-bottom: 30px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        .msg-title {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .msg-text {
            max-width: 520px;
            margin: 0 auto;
            font-size: 0.95rem;
            line-height: 1.5;
            font-weight: 700;
        }

        #bottom-info-card {
            width: 100%;
            max-width: 95%;
            margin-bottom: 0;
            padding: 15px 10px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-evenly;
            align-items: flex-start;
            gap: 10px;
        }

        .footer-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .col-title {
            margin-bottom: 5px;
            color: #000000;
            font-size: 0.55rem;
            font-weight: 800;
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
            padding-top: 5px;
            font-size: 0.95rem;
            font-family: sans-serif;
            font-weight: 500;
            letter-spacing: -0.5px;
        }

        .logo-1utama {
            width: auto;
            height: 24px;
            object-fit: contain;
        }

        .logo-sponsor {
            width: auto;
            height: 24px;
            object-fit: contain;
        }

        .logo-sponsor-ditp {
            width: auto;
            height: 18px;
            padding: 2px;
            border-radius: 2px;
            object-fit: contain;
            background: #ffffff;
        }

        .logo-sponsor-amazing {
            width: auto;
            height: 22px;
            object-fit: contain;
        }

        .logo-media {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .ticket-download-wrap {
            margin: 0 0 24px;
            color: #ffffff;
        }

        .ticket-download-button {
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

        .ticket-download-button:hover {
            color: #075985;
            text-decoration: none;
            transform: translateY(-1px);
        }

        @media (max-width: 480px) {
            .ticket-page-shell {
                padding: 12px;
            }

            .ticket-wrapper {
                min-height: calc(100vh - 24px);
                border-radius: 22px;
            }

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

    <div class="ticket-page-shell">
        <div class="ticket-wrapper">
            <div class="top-section">
                <img src="{{ $logoUrl }}" alt="Songkran Festival Logo" class="logo">

                <div class="event-info">
                    <p class="event-time">12PM-12AM</p>
                    <p class="event-date">9-19 APRIL</p>
                    <p class="event-venue">@GF FORECOURT OUTDOOR CARPARK, 1 UTAMA</p>
                    <p class="event-subtitle">MALAYSIA'S PREMIER SONGKRAN FESTIVAL</p>
                </div>

                <div id="qrcode-container" class="qr-card">
                    {!! $qrSvg !!}
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
                        This code remains valid for the duration of the event, though scanning is required upon each day.
                    </div>
                </div>

                <div class="ticket-download-wrap">
                    <a href="{{ $qrDownloadUrl }}" class="ticket-download-button">Download Ticket</a>
                </div>

                <div id="bottom-info-card" class="glass-card">
                    <div class="footer-col" style="flex: 1; min-width: 70px;">
                        <div class="col-title">ORGANISER</div>
                        <div class="logo-organiser-text">eq solutions</div>
                    </div>

                    <div class="footer-col" style="flex: 1; min-width: 70px;">
                        <div class="col-title">VENUE SPONSOR</div>
                        <img src="{{ $venueSponsorUrl }}" alt="1 Utama" class="logo-1utama">
                    </div>

                    <div class="footer-col" style="flex: 3; min-width: 180px;">
                        <div class="col-title">SPONSORS</div>
                        <div class="logo-row">
                            <img src="{{ $sponsorEmbassyUrl }}" alt="Royal Thai Embassy" class="logo-sponsor">
                            <img src="{{ $sponsorDitpUrl }}" alt="DITP" class="logo-sponsor-ditp">
                            <img src="{{ $sponsorAmazingThailandUrl }}" alt="Amazing Thailand" class="logo-sponsor-amazing">
                            <img src="{{ $sponsorSinghaUrl }}" alt="Singha" class="logo-sponsor">
                            <img src="{{ $sponsorSnakeBrandUrl }}" alt="Snake Brand" class="logo-sponsor">
                        </div>
                    </div>

                    <div class="footer-col" style="flex: 1.5; min-width: 90px;">
                        <div class="col-title">MEDIA PARTNERS</div>
                        <div class="logo-row" style="gap: 8px;">
                            <img src="{{ $mediaWobUrl }}" alt="WOB" class="logo-media">
                            <img src="{{ $mediaNoodouUrl }}" alt="NOODOU" class="logo-media">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection