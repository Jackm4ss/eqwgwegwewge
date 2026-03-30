<!DOCTYPE html>
<html lang="en">
@php
    $metaTitle = 'Songkran Festival 2026';
    $metaDescription = "Malaysia's Premier Songkran Festival";
    $metaImage = asset('favico.png');
    $metaUrl = url()->current();
@endphp
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $metaTitle }}</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    @if (empty($pwaManifestUrl))
        <link rel="apple-touch-icon" href="/favico.png">
    @endif
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $metaUrl }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0c4a6e">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:url" content="{{ $metaUrl }}">
    <meta property="og:site_name" content="{{ $metaTitle }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">
    <meta name="recaptcha-enabled" content="{{ config('services.recaptcha.enabled') ? '1' : '0' }}">
    <meta name="recaptcha-site-key" content="{{ config('services.recaptcha.site_key') }}">
    <meta name="register-url" content="{{ config('admin.future_urls.register') ?: route('register.form') }}">
    <meta name="staff-scanner-posts" content="{{ json_encode($staffScannerPosts ?? ['Gate A']) }}">
    @if (! empty($pwaManifestUrl))
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Songkran Scanner">
        <link rel="manifest" href="{{ $pwaManifestUrl }}">
        <link rel="apple-touch-icon" href="{{ asset('pwa/icons/apple-touch-icon.png') }}">
    @endif
    <script id="app-spa-config" type="application/json">@json($spaConfig ?? [])</script>
    @viteReactRefresh
    @vite('resources/js/src/main.tsx')
</head>
<body class="antialiased">
    <div id="root"></div>
</body>
</html>
