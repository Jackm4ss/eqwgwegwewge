@extends('layouts.app', ['title' => 'Forgot Password | Songkran Festival'])

@section('content')
<div class="card auth-card">
    <h1 class="title">Forgot Password</h1>
    <p class="subtitle">Enter your email to receive a password reset link.</p>

    <form id="forgotPasswordForm">
        @csrf
        <div>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>

        <div style="margin-top: 14px;">
            <button type="submit">Send Reset Link</button>
        </div>
    </form>

    <p class="muted" style="margin-top:16px; text-align:center;">
        <a href="{{ route('login') }}">Back to Login</a>
    </p>

    <p id="forgotPasswordMessage" class="muted" style="margin-top:10px;"></p>
</div>
@endsection