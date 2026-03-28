<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Panel Setup Required</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            color: #0f172a;
        }

        .panel {
            width: min(640px, 100%);
            padding: 28px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
        }

        h1 {
            margin: 0 0 12px;
            font-size: 1.6rem;
        }

        p {
            margin: 0 0 12px;
            line-height: 1.6;
        }

        code {
            display: block;
            margin: 14px 0;
            padding: 14px 16px;
            border-radius: 12px;
            background: #0f172a;
            color: #e2e8f0;
            overflow-x: auto;
            font-size: 0.95rem;
        }

        a {
            color: #0369a1;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main class="panel">
        <h1>Staff panel setup is incomplete</h1>
        <p>The staff authentication tables are not ready yet on this environment, so Laravel cannot safely restore the staff session.</p>
        <p>Run the following commands once:</p>
        <code>php artisan migrate
php artisan staff:seed --password=YOUR_PASSWORD</code>
        <p>After that, open <a href="{{ $loginPath }}">{{ $loginPath }}</a> and sign in with one of the seeded staff accounts.</p>
    </main>
</body>
</html>
