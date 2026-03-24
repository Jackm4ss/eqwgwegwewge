@extends('layouts.app', ['title' => 'Your Ticket | Songkran Festival'])

@section('content')
@php
    $ticketStatus = strtoupper((string) ($ticket['status'] ?? 'active'));
    $identityLabel = (($user['identity_type'] ?? 'passport') === 'national_id') ? 'National ID' : 'Passport';
@endphp

<style>
    .ticket-shell {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .ticket-card-modern {
        position: relative;
        width: 100%;
        max-width: 1040px;
        overflow: hidden;
        border-radius: 34px;
        background:
            radial-gradient(circle at top right, rgba(14, 165, 233, 0.18), transparent 28%),
            radial-gradient(circle at bottom left, rgba(34, 211, 238, 0.16), transparent 30%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 252, 255, 0.98) 100%);
        border: 1px solid rgba(125, 211, 252, 0.35);
        box-shadow:
            0 30px 80px rgba(7, 89, 133, 0.28),
            0 10px 24px rgba(14, 165, 233, 0.12);
        color: #0f172a;
    }

    .ticket-hero {
        position: relative;
        padding: 32px 32px 28px;
        background: linear-gradient(135deg, #0369a1 0%, #0284c7 52%, #0ea5e9 100%);
        color: #fff;
    }

    .ticket-hero::after {
        content: "";
        position: absolute;
        right: -80px;
        top: -80px;
        width: 220px;
        height: 220px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
    }

    .ticket-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.18);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .ticket-badge-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #86efac;
        box-shadow: 0 0 0 4px rgba(134, 239, 172, 0.18);
    }

    .ticket-title {
        margin: 20px 0 10px;
        font-size: clamp(32px, 5vw, 50px);
        line-height: 1.05;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .ticket-subtitle {
        max-width: 640px;
        font-size: 16px;
        line-height: 1.75;
        color: rgba(255, 255, 255, 0.92);
    }

    .ticket-body {
        display: grid;
        grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
        gap: 28px;
        padding: 30px 32px 32px;
    }

    .ticket-qr-panel,
    .ticket-summary-panel {
        position: relative;
        border-radius: 28px;
        border: 1px solid rgba(186, 230, 253, 0.9);
        background: rgba(255, 255, 255, 0.84);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .ticket-qr-panel {
        padding: 24px;
        text-align: center;
        overflow: hidden;
    }

    .ticket-panel-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 18px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(2, 132, 199, 0.08);
        color: #0369a1;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .ticket-qr-frame {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        padding: 18px;
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #eff8ff 100%);
        box-shadow:
            0 16px 32px rgba(14, 165, 233, 0.12),
            inset 0 0 0 1px rgba(186, 230, 253, 0.9);
        overflow: hidden;
    }

    .ticket-qr-frame svg {
        display: block;
        width: min(100%, 280px);
        max-width: 100%;
        height: auto;
    }

    .ticket-qr-note {
        margin-top: 16px;
        color: #64748b;
        font-size: 13px;
        line-height: 1.7;
    }

    .ticket-qr-actions {
        display: flex;
        margin-top: 18px;
    }

    .ticket-summary-panel {
        padding: 24px;
    }

    .ticket-code-card {
        margin-bottom: 18px;
        padding: 20px 22px;
        border-radius: 24px;
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(34, 211, 238, 0.08) 100%);
        border: 1px solid rgba(125, 211, 252, 0.45);
    }

    .ticket-code-label {
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #0369a1;
    }

    .ticket-code-value {
        font-size: clamp(18px, 2vw, 22px);
        line-height: 1.5;
        font-weight: 900;
        color: #0f172a;
        word-break: break-word;
    }

    .ticket-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .ticket-meta-card {
        padding: 18px 18px 16px;
        border-radius: 22px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(248, 252, 255, 0.96) 100%);
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .ticket-meta-card.full-width {
        grid-column: 1 / -1;
    }

    .ticket-meta-label {
        margin-bottom: 8px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .ticket-meta-value {
        font-size: 18px;
        line-height: 1.55;
        font-weight: 800;
        color: #0f172a;
        word-break: break-word;
    }

    .ticket-country-inline {
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }

    .ticket-flag {
        width: 30px;
        height: 22px;
        flex: 0 0 auto;
        border-radius: 6px;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        box-shadow:
            0 10px 18px rgba(15, 23, 42, 0.12),
            inset 0 0 0 1px rgba(148, 163, 184, 0.2);
    }

    .ticket-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.12);
        color: #047857;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .ticket-status-pill::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.16);
    }

    .ticket-actions {
        display: flex;
        gap: 14px;
        margin-top: 24px;
        flex-wrap: wrap;
    }

    .ticket-action {
        flex: 1 1 220px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        min-height: 54px;
        padding: 14px 18px;
        border-radius: 18px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 900;
        letter-spacing: 0.04em;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .ticket-action:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }

    .ticket-action-primary {
        background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
        color: #ffffff;
        box-shadow: 0 16px 30px rgba(14, 165, 233, 0.28);
    }

    .ticket-action-secondary {
        background: rgba(255, 255, 255, 0.88);
        color: #0369a1;
        border: 1px solid rgba(125, 211, 252, 0.7);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
    }

    @media (max-width: 860px) {
        .ticket-body {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .ticket-hero,
        .ticket-body {
            padding-left: 22px;
            padding-right: 22px;
        }

        .ticket-meta-grid {
            grid-template-columns: 1fr;
        }

        .ticket-title {
            font-size: 34px;
        }

        .ticket-qr-panel,
        .ticket-summary-panel {
            padding: 20px;
        }

        .ticket-qr-frame {
            padding: 14px;
            border-radius: 20px;
        }
    }
</style>

<div class="ticket-shell">
    <div class="ticket-card-modern">
        <div class="ticket-hero">
            <span class="ticket-badge">
                <span class="ticket-badge-dot"></span>
                Verification Complete
            </span>

            <h1 class="ticket-title">Your Ticket Is Active</h1>

            <p class="ticket-subtitle">
                Account verified successfully for <strong>{{ $user['full_name'] }}</strong>.
                Your Songkran Festival pass is now ready to use.
            </p>
        </div>

        <div class="ticket-body">
            <div class="ticket-qr-panel">
                <div class="ticket-panel-label">Festival Pass</div>

                <div class="ticket-qr-frame">
                    {!! $qrSvg !!}
                </div>

                <p class="ticket-qr-note">
                    Present this QR code at check-in to access your ticket details instantly.
                </p>

                <div class="ticket-qr-actions">
                    <a
                        href="{{ $qrSvgDownloadUrl }}"
                        download="songkran-ticket-{{ $ticket['ticket_code'] }}.svg"
                        class="ticket-action ticket-action-secondary"
                    >
                        Download QR Code
                    </a>
                </div>
            </div>

            <div class="ticket-summary-panel">
                <div class="ticket-code-card">
                    <div class="ticket-code-label">Ticket Code</div>
                    <div class="ticket-code-value">{{ $ticket['ticket_code'] }}</div>
                </div>

                <div class="ticket-meta-grid">
                    <div class="ticket-meta-card">
                        <div class="ticket-meta-label">Status</div>
                        <div class="ticket-meta-value">
                            <span class="ticket-status-pill">{{ $ticketStatus }}</span>
                        </div>
                    </div>

                    <div class="ticket-meta-card">
                        <div class="ticket-meta-label">Country</div>
                        <div class="ticket-meta-value">
                            <span class="ticket-country-inline">
                                @if ($countryFlagUrl)
                                    <span
                                        class="ticket-flag"
                                        style="background-image: url('{{ $countryFlagUrl }}');"
                                        aria-hidden="true"
                                    ></span>
                                @endif
                                <span>{{ $countryName }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="ticket-meta-card full-width">
                        <div class="ticket-meta-label">Email</div>
                        <div class="ticket-meta-value">{{ $user['email'] }}</div>
                    </div>

                    <div class="ticket-meta-card full-width">
                        <div class="ticket-meta-label">Identity Document</div>
                        <div class="ticket-meta-value">{{ $identityLabel }} : {{ $user['identity_number'] }}</div>
                    </div>
                </div>

                <div class="ticket-actions">
                    <a href="{{ route('register.form') }}" class="ticket-action ticket-action-secondary">
                        Register Another Guest
                    </a>
                    <a href="{{ env('FRONTEND_HOMEPAGE_URL', 'http://127.0.0.1:8000') }}" class="ticket-action ticket-action-primary">
                        Back to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
