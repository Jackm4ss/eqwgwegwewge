@extends('layouts.app', ['title' => 'Reset Success | Songkran Festival'])

@section('content')
<div class="card auth-card" style="max-width:560px">
    <div class="success-icon">✔</div>
    <h1 class="title">Password Reset Successful</h1>
    <p class="subtitle">Your password has been updated successfully.</p>

    <div style="margin-top:18px;">
        <button type="button" onclick="window.location.href='{{ route('login') }}'">
            Login
        </button>
    </div>
</div>
@endsection