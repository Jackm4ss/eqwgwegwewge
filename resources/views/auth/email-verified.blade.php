@extends('layouts.app', ['title' => 'Email Verified | Songkran Festival'])

@section('content')
@php
    $homepageUrl = \App\Support\AppRouting::publicUrl();
@endphp
<div class="card" style="max-width:560px">
    <div class="success-icon">✓</div>

    <h1 class="title">Email Verified Successfully</h1>

    <p class="subtitle">
        Your account is now active{{ !empty($maskedEmail) ? ' for '.$maskedEmail : '' }}.
    </p>

    <div class="action-row">
        <a href="{{ route('login') }}">
            <button type="button">Login</button>
        </a>

        <a href="{{ $homepageUrl }}">
            <button type="button" class="secondary-btn">Homepage</button>
        </a>
    </div>
</div>
@endsection
