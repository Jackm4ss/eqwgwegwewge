@extends('staff.layouts.app', ['title' => 'Staff Login', 'subtitle' => 'Use your seeded operator account to access the scanner.'])

@section('content')
    <div class="mx-auto flex min-h-[72vh] max-w-md items-center">
        <div class="staff-card w-full px-5 py-6">
            <div class="mb-6">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-staff-primary-strong">Songkran Operations</p>
                <h2 class="mt-2 text-3xl font-semibold text-staff-ink">Scanner Staff Login</h2>
                <p class="mt-2 text-sm text-staff-soft">One account per operator. After login, choose the device station before starting scans.</p>
            </div>

            <form method="POST" action="{{ route('staff.login.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-staff-ink">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required class="staff-input" value="{{ old('email') }}" placeholder="staff01@songkran.local">
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-staff-ink">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="staff-input" placeholder="Shared password">
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-staff-line bg-white px-4 py-3 text-sm text-staff-soft">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-staff-line text-staff-primary focus:ring-staff-primary">
                    Keep this device signed in for the shift
                </label>

                <button type="submit" class="staff-button-primary w-full">Continue to Station Setup</button>
            </form>
        </div>
    </div>
@endsection
