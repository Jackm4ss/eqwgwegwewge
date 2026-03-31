<!doctype html>
<html lang="en">
@php
    $shareImagePath = implode('/', array_map('rawurlencode', explode('/', 'images/Songkran logo.png')));
    $siteTitle = 'Songkran Festival 2026';
    $pageTitle = $title ?? $siteTitle;
    $metaTitle = $metaTitle ?? $siteTitle;
    $metaDescription = $metaDescription ?? "Malaysia's Premier Songkran Festival. Join us for 11 days of pure celebration!";
    $metaImage = $metaImage ?? asset($shareImagePath);
    $metaUrl = $metaUrl ?? url()->current();
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $metaUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:image:alt" content="Songkran Festival 2026 logo">
    <meta property="og:url" content="{{ $metaUrl }}">
    <meta property="og:site_name" content="{{ $siteTitle }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">
    <meta name="twitter:image:alt" content="Songkran Festival 2026 logo">
    <link rel="icon" type="image/png" href="{{ $metaImage }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ $metaImage }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tilt+Warp&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
    @yield('content')
</body>
</html>
