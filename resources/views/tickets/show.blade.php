@extends('layouts.app', ['title' => 'Your Ticket | Songkran Festival'])

@section('content')
<div class="card" style="max-width:720px">
    <div class="success-icon">✓</div>

    <h1 class="title">Your Ticket Is Active</h1>
    <p class="subtitle">
        Account verified successfully for {{ $user['full_name'] }}.
    </p>

    <div style="display:grid; gap:24px; align-items:start;">
        <div style="display:flex; justify-content:center;">
            <div style="background:#fff; padding:18px; border-radius:20px; box-shadow:0 0 18px rgba(0, 200, 255, 0.12);">
                {!! $qrSvg !!}
            </div>
        </div>

        <div class="grid">
            <div>
                <p class="small">Ticket Code</p>
                <p style="font-size:18px; font-weight:700;">{{ $ticket['ticket_code'] }}</p>
            </div>
            <div>
                <p class="small">Status</p>
                <p style="font-size:18px; font-weight:700; color:#34d399;">{{ strtoupper($ticket['status']) }}</p>
            </div>
            <div>
                <p class="small">Email</p>
                <p style="font-size:16px; font-weight:600;">{{ $user['email'] }}</p>
            </div>
            <div>
                <p class="small">Country</p>
                <p style="font-size:16px; font-weight:600;">{{ $user['country'] }}</p>
            </div>
            <div class="full">
                <p class="small">Identity Number</p>
                <p style="font-size:16px; font-weight:600;">{{ $user['identity_number'] }}</p>
            </div>
        </div>
    </div>

    <div class="action-row">
        <a href="{{ route('register.form') }}">
            <button type="button" class="secondary-btn">Register Another Guest</button>
        </a>
        <a href="{{ env('FRONTEND_HOMEPAGE_URL', 'http://127.0.0.1:8000') }}">
            <button type="button">Back to Homepage</button>
        </a>
    </div>
</div>
@endsection
