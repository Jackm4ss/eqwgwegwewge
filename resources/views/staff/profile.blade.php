@extends('staff.layouts.app', ['title' => 'Profile', 'subtitle' => 'View operator information and sign out from this device.', 'station' => $station])

@section('content')
    @php($staffUser = auth('staff')->user())

    <div class="space-y-4">
        <section class="staff-card px-5 py-5">
            <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Operator Profile</p>
            <h2 class="mt-1 text-2xl font-semibold text-staff-ink">{{ $staffUser?->name }}</h2>
            <p class="mt-2 text-sm text-staff-soft">{{ $staffUser?->email }}</p>

            <div class="mt-5 rounded-3xl border border-orange-100 bg-orange-50/80 px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white text-staff-primary shadow-[0_10px_25px_rgba(249,115,22,0.14)]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7" aria-hidden="true">
                            <path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z" />
                            <path fill-rule="evenodd" d="M3.75 20.25a8.25 8.25 0 1 1 16.5 0 .75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Operator</p>
                        <p class="mt-1 truncate text-base font-semibold text-staff-ink">{{ $staffUser?->name }}</p>
                        <p class="truncate text-sm text-staff-soft">{{ $staffUser?->email }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="staff-card px-5 py-5">
            <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Session</p>
            <h2 class="mt-1 text-xl font-semibold text-staff-ink">Sign Out</h2>
            <p class="mt-2 text-sm text-staff-soft">Use this button when the shift ends or when another operator will use this device.</p>

            <form method="POST" action="{{ route('staff.logout') }}" class="mt-5">
                @csrf
                <button type="submit" class="staff-button-danger-soft w-full">Logout</button>
            </form>
        </section>
    </div>
@endsection
