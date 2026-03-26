@extends('layouts.app', ['title' => 'Registration Success | Songkran Festival'])

@section('content')
<div class="card" style="max-width:560px">
    <div class="success-icon">OK</div>

    <h1 class="title">Registration Successful</h1>

    <p class="subtitle">
        We have sent your QR ticket email to <strong>{{ $maskedEmail }}</strong>.
    </p>

    <p class="muted">
        Please check Inbox, Spam, and Junk folders. Your pass is already active, and the email contains your QR code plus a direct ticket link.
    </p>
</div>
@endsection
