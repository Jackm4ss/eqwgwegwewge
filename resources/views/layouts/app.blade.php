<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Songkran Festival Registration' }}</title>
    <style>
        body{font-family:Inter,system-ui,-apple-system,sans-serif;background:linear-gradient(145deg,#eff6ff,#dbeafe);min-height:100vh;margin:0;display:flex;align-items:center;justify-content:center;padding:24px;color:#0f172a}
        .card{width:100%;max-width:680px;background:#fff;border-radius:24px;box-shadow:0 20px 35px rgba(15,23,42,.1);padding:32px}
        .title{font-size:1.8rem;font-weight:700;margin:0 0 8px}
        .subtitle{color:#475569;margin-bottom:24px}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .full{grid-column:1 / -1}
        input,select,textarea{width:100%;border:1px solid #cbd5e1;border-radius:12px;padding:12px 14px;font-size:14px;box-sizing:border-box}
        button{background:linear-gradient(135deg,#2563eb,#1e3a8a);color:#fff;border:0;border-radius:12px;padding:12px 18px;font-weight:600;cursor:pointer}
        .error{color:#dc2626;font-size:12px;margin-top:4px}
        .success-icon{width:56px;height:56px;border-radius:50%;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:12px}
        .muted{color:#64748b}
        @media (max-width: 768px){.grid{grid-template-columns:1fr}.card{padding:24px}}
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
