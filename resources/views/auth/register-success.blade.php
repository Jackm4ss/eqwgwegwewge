@extends('layouts.app', ['title' => 'Registration Success | Songkran Festival'])

@section('content')
<div class="card" style="max-width:560px">
    <div class="success-icon">OK</div>

    <h1 class="title">Registration Successful</h1>

    <p class="subtitle">
        We have sent a verification email to <strong>{{ $maskedEmail }}</strong>.
    </p>

    <p class="muted">
        Please check Inbox, Spam, and Junk folders. You can request a new verification email if it has not arrived yet.
    </p>

    <p class="muted" id="cooldown">
        You can request a new verification link in 60 seconds.
    </p>

    <button id="resendBtn" data-email="{{ $email }}" disabled>Resend Verification Email</button>

    <p id="msg" class="muted form-message"></p>
</div>
@endsection
