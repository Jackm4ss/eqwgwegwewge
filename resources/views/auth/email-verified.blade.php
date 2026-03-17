@extends('layouts.app', ['title' => 'Email Verified'])

@section('content')
<div class="card" style="max-width:560px">
    <div class="success-icon">✓</div>
    <h1 class="title">Email Verified Successfully</h1>
    <p class="subtitle">Your account is now active{{ $maskedEmail ? ' for '.$maskedEmail : '' }}.</p>
    <div style="display:flex;gap:10px">
        <a href="{{ env('FRONTEND_LOGIN_URL', '/login') }}"><button>Login</button></a>
        <a href="{{ env('FRONTEND_HOMEPAGE_URL', 'https://songkremfestival.my') }}"><button style="background:#fff;border:1px solid #1e3a8a;color:#1e3a8a">Back to Homepage</button></a>
    </div>
</div>
@endsection
