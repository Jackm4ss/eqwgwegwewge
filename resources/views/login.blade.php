@extends('layouts.app', ['title' => 'Login | Songkran Festival'])

@section('content')
<div class="card auth-card" style="max-width:560px">
    <h1 class="title">Login</h1>
    <p class="subtitle">Welcome back! Please login to continue.</p>

    <form id="loginForm">
        @csrf
        <div style="margin-bottom:14px;">
            <input type="email" name="email" placeholder="Email" required>
        </div>

        <div style="margin-bottom:10px;">
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <div style="text-align:right; font-size:13px; margin-bottom:14px;">
            <a href="{{ route('password.request') }}">Forgot Password?</a>
        </div>

        <button type="submit">Login</button>
    </form>

    <hr style="margin:20px 0; border:none; border-top:1px solid #e2e8f0;">

    <p class="muted" style="text-align:center;">Don't have an account?</p>

    <button type="button" style="width:100%;" onclick="window.location.href='{{ route('register.form') }}'">
        Create Account
    </button>

    <p id="loginMessage" class="muted" style="margin-top:12px;"></p>
</div>
@endsection