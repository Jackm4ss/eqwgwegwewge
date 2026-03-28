<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Staff Scanner' }} | Songkran Festival 2026</title>
    @vite('resources/js/staff.ts')
</head>
<body class="staff-shell">
    <div class="mx-auto flex min-h-screen w-full max-w-[430px] flex-col px-3 py-4 pb-28">
        @php($staffUser = auth('staff')->user())

        @php($activeStation = $station ?? null)

        @if($staffUser)
            <header class="mb-4">
                <div class="staff-card flex flex-col gap-4 px-4 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-staff-primary-strong">Operator Console</p>
                            <h1 class="mt-1 text-2xl font-semibold text-staff-ink">{{ $title ?? 'Staff Scanner' }}</h1>
                            <p class="mt-1 text-sm text-staff-soft">{{ $subtitle ?? 'Online validation only. Use one station per device.' }}</p>
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <div class="staff-card-disabled" aria-disabled="true">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Current Station</p>
                                    <p class="mt-1 text-sm font-semibold text-stone-700">{{ optional($activeStation)->scanner_name ?? 'Not selected' }}</p>
                                    <p class="text-xs text-stone-500">{{ optional($activeStation)->gate_name ?? 'Choose station first' }}</p>
                                </div>
                                <span class="staff-badge-muted">Read only</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-staff-danger">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('status'))
            <div class="mb-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-staff-success">
                {{ session('status') }}
            </div>
        @endif

        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    @if($staffUser)
        <nav class="fixed inset-x-0 bottom-0 z-40 mx-auto w-full max-w-[430px] px-3 pb-3">
            <div class="staff-bottom-nav">
                <a
                    href="{{ route('staff.home') }}"
                    class="staff-bottom-nav-item {{ request()->routeIs('staff.scanner') ? 'is-active' : '' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M12.75 3.172a1.5 1.5 0 0 0-1.5 0l-7.5 4.5A1.5 1.5 0 0 0 3 8.957v8.293A2.25 2.25 0 0 0 5.25 19.5h3.75A.75.75 0 0 0 9.75 18v-3.75A.75.75 0 0 1 10.5 13.5h3a.75.75 0 0 1 .75.75V18a.75.75 0 0 0 .75.75h3.75A2.25 2.25 0 0 0 21 17.25V8.957a1.5 1.5 0 0 0-.75-1.285l-7.5-4.5Z" />
                    </svg>
                    <span>Home</span>
                </a>

                <a
                    href="{{ route('staff.station.create') }}"
                    class="staff-bottom-nav-item {{ request()->routeIs('staff.station.create') ? 'is-active' : '' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v7.5a3 3 0 0 1-3 3h-4.19l-2.03 2.71a.75.75 0 0 1-1.2 0l-2.03-2.71H6a3 3 0 0 1-3-3V6Zm4.5 2.25a.75.75 0 0 0 0 1.5h9a.75.75 0 0 0 0-1.5h-9Zm0 3a.75.75 0 0 0 0 1.5h5.25a.75.75 0 0 0 0-1.5H7.5Z" clip-rule="evenodd" />
                    </svg>
                    <span>Station</span>
                </a>

                <a
                    href="{{ route('staff.stats') }}"
                    class="staff-bottom-nav-item {{ request()->routeIs('staff.stats') ? 'is-active' : '' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M3.75 3A.75.75 0 0 1 4.5 3.75v15a1.5 1.5 0 0 0 1.5 1.5h14.25a.75.75 0 0 1 0 1.5H6A3 3 0 0 1 3 18.75v-15A.75.75 0 0 1 3.75 3Z" />
                        <path d="M9 17.25a.75.75 0 0 1-.75-.75v-6a.75.75 0 0 1 1.5 0v6a.75.75 0 0 1-.75.75Zm4.5 0a.75.75 0 0 1-.75-.75V7.5a.75.75 0 0 1 1.5 0v9a.75.75 0 0 1-.75.75Zm4.5 0a.75.75 0 0 1-.75-.75v-3a.75.75 0 0 1 1.5 0v3a.75.75 0 0 1-.75.75Z" />
                    </svg>
                    <span>Stats</span>
                </a>

                <a
                    href="{{ route('staff.profile') }}"
                    class="staff-bottom-nav-item {{ request()->routeIs('staff.profile') ? 'is-active' : '' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z" />
                        <path fill-rule="evenodd" d="M3.75 20.25a8.25 8.25 0 1 1 16.5 0 .75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                    <span>Profile</span>
                </a>
            </div>
        </nav>
    @endif
</body>
</html>
