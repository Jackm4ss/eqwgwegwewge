@extends('layouts.app', ['title' => 'Your Ticket | Songkran Festival'])

@section('content')
    @php
        $fullName = trim((string) ($user['full_name'] ?? 'Guest'));
        $identityNumber = trim((string) ($user['identity_number'] ?? ''));
        $identityDisplay = $identityNumber !== '' ? $identityNumber : '-';
        $entryCodeDisplay = trim((string) ($ticket['entry_code_display'] ?? ''));

        $backgroundUrl = asset('images/BACKGROUND.jpg');
        $logoUrl = asset('images/Songkran logo.png');
        $sponsorsFooterUrl = asset('images/email-sponsors-footer.png');
        $mapToLocationUrl = asset('images/Map to Location.png');
    @endphp

    <style>
        html,
        body {
            color-scheme: light;
        }

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
            padding: 10px 20px 28px;
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
            font-family: "Tilt Warp", sans-serif;
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
            width: min(280px, calc(100% - 32px));
            margin: 25px auto 0;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            padding: 14px 14px 18px;
            overflow: hidden;
        }

        .qr-card svg {
            display: block;
            width: 160px;
            height: 160px;
            flex-shrink: 0;
        }

        .qr-card .entry-code-card {
            width: 100%;
            min-width: 0;
            margin: 0;
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

        .ticket-validity-note {
            margin: 22px auto 20px;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.45);
        }

        .entry-code-card {
            margin: 18px auto 0;
            display: inline-flex;
            min-width: 210px;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 18px 22px;
            border-radius: 22px;
            border: 1px solid rgba(255, 255, 255, 0.52);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0.48));
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow:
                0 10px 28px rgba(15, 23, 42, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.72);
            color: #000000;
        }

        .entry-code-label {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(15, 23, 42, 0.68);
        }

        .entry-code-value {
            font-size: 1.08rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            color: #0f172a;
        }

        .entry-code-help {
            margin-top: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1.45;
            color: rgba(15, 23, 42, 0.72);
            max-width: 220px;
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

        .maps-location-note {
            margin-top: 16px;
            display: inline-block;
            text-decoration: none;
            cursor: pointer;
        }

        .maps-location-image {
            width: 220px;
            max-width: 100%;
            height: auto;
            display: block;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
        }

        .bottom-info-wrap {
            padding: 0 6px;
        }

        #bottom-info-card {
            width: 100%;
            margin: 0;
            padding: 14px 14px;
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            background: linear-gradient(180deg, rgba(214, 236, 244, 0.98) 0%, rgba(206, 230, 241, 0.94) 100%);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        .sponsors-footer-image {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 20px;
        }

        .ticket-download-wrap {
            margin: 20px 0 24px;
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
            background-color: #ffffff;
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
                    {{-- Temporary: hide Entry Code block. --}}
                </div>

                <div class="ticket-download-wrap">
                    <a href="{{ $qrDownloadUrl }}" class="ticket-download-button">Download Ticket</a>
                </div>

                <div id="ticket-info-card" class="glass-card">
                    <div id="ticket-user-name" class="ticket-user-name">{{ mb_strtoupper($fullName) }}</div>
                    <div id="ticket-user-passport" class="ticket-user-passport">{{ $identityDisplay }}</div>
                </div>
            </div>

            <div class="ticket-validity-note">Ticket valid from 9-19 April 2026</div>

            <div class="bottom-section">
                <div class="message-box">
                    <div class="msg-title">Thank you for your registration.</div>
                    <div class="msg-text">
                        Please present your QR code and registered valid ID / passport at the gate.
                    </div>
                    <a href="https://maps.app.goo.gl/UEPceTqzjesMy1ze8?g_st=iw" target="_blank" rel="noopener noreferrer"
                        class="maps-location-note">
                        <img src="{{ $mapToLocationUrl }}" alt="Map to Location" class="maps-location-image">
                    </a>
                </div>

                <div class="bottom-info-wrap">
                    <div id="bottom-info-card">
                        <img src="{{ $sponsorsFooterUrl }}"
                            alt="Songkran Festival organiser, venue sponsor, sponsors, and media partners"
                            class="sponsors-footer-image">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
