@extends('layouts.app', ['title' => 'Registration Success | Songkran Festival'])

@section('content')
<div class="card" style="max-width:560px">
    <div class="success-icon">OK</div>

    <h1 class="title">Registration Successful</h1>

    <p class="subtitle">
        We have sent your QR ticket email to <strong>{{ $maskedEmail }}</strong>.
    </p>

    <p class="muted">
        Please check Inbox, Spam, and Junk folders. Your QR code is inside the email. You can request a new ticket email if it has not arrived yet.
    </p>

    <p class="muted" id="cooldown">
        You can request a new ticket email in 60 seconds.
    </p>

    <button id="resendBtn" data-email="{{ $email }}" disabled>Resend Ticket Email</button>

    <p id="msg" class="muted form-message"></p>
</div>
@endsection
