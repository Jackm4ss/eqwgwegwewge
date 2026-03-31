<!DOCTYPE html>
<html lang="en" style="color-scheme: light;">
@php
    $shareImagePath = implode('/', array_map('rawurlencode', explode('/', 'images/Songkran logo.png')));
    $metaTitle = 'Songkran Festival 2026';
    $metaDescription = "Malaysia's Premier Songkran Festival.";
    $metaImage = asset($shareImagePath);
    $metaUrl = url()->current();
    $backgroundImage = asset('images/BACKGROUND.jpg');
    $heroStageImage = asset('images/islands.png');
    $heroTextImage = asset('images/text.png');
    $fontStylesheetHref = 'https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700&family=Tilt+Warp&display=swap';
@endphp
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $metaTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="{{ $fontStylesheetHref }}">
    <link rel="stylesheet" href="{{ $fontStylesheetHref }}">
    <link rel="preload" as="image" href="{{ $backgroundImage }}" fetchpriority="high">
    <link rel="preload" as="image" href="{{ $metaImage }}" fetchpriority="high">
    <link rel="preload" as="image" href="{{ $heroStageImage }}">
    <link rel="preload" as="image" href="{{ $heroTextImage }}">
    <link rel="icon" type="image/png" href="{{ $metaImage }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    @if (empty($pwaManifestUrl))
        <link rel="apple-touch-icon" href="{{ $metaImage }}">
    @endif
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $metaUrl }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0c4a6e">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:image:alt" content="Songkran Festival 2026 logo">
    <meta property="og:url" content="{{ $metaUrl }}">
    <meta property="og:site_name" content="{{ $metaTitle }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">
    <meta name="twitter:image:alt" content="Songkran Festival 2026 logo">
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
<body class="antialiased" style="color-scheme: light; background-color: #e0f7ff;">
    <div id="root"></div>
</body>
</html>
