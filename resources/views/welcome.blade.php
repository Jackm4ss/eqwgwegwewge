<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Songkran Festival 2026</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0c4a6e">
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
