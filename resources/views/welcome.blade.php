<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Songkran Festival 2026</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="recaptcha-enabled" content="{{ config('services.recaptcha.enabled') ? '1' : '0' }}">
    <meta name="recaptcha-site-key" content="{{ config('services.recaptcha.site_key') }}">
    <meta name="staff-scanner-posts" content="{{ json_encode(array_values(config('scanner.posts', ['Gate A']))) }}">
    @viteReactRefresh
    @vite('resources/js/src/main.tsx')
</head>
<body class="antialiased">
    <div id="root"></div>
</body>
</html>
