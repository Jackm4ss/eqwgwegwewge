@extends('staff.layouts.app', ['title' => 'Station Stats', 'subtitle' => 'Monitor throughput and scan quality for the currently bound station.', 'station' => $station])

@section('content')
    <div data-staff-stats data-stats-url="{{ url('/api/scanner/stats/today') }}" class="space-y-4">
        <section class="staff-card px-5 py-5">
            <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Today</p>
            <h2 class="mt-1 text-2xl font-semibold text-staff-ink">{{ $station->scanner_name }} Performance</h2>
            <p class="mt-2 text-sm text-staff-soft">This view auto-refreshes to keep the gate supervisor informed without leaving the phone idle on the scanner page.</p>
        </section>

        <section class="grid grid-cols-2 gap-4">
            <div class="staff-card px-5 py-5">
                <p class="text-xs text-staff-soft">Total scans</p>
                <p id="stats-total" class="mt-2 text-4xl font-semibold text-staff-ink">{{ $stats['total_scans'] ?? 0 }}</p>
            </div>
            <div class="staff-card px-5 py-5">
                <p class="text-xs text-staff-soft">Valid scans</p>
                <p id="stats-valid" class="mt-2 text-4xl font-semibold text-staff-success">{{ $stats['valid_scans'] ?? 0 }}</p>
            </div>
            <div class="staff-card px-5 py-5">
                <p class="text-xs text-staff-soft">Duplicate scans</p>
                <p id="stats-duplicate" class="mt-2 text-4xl font-semibold text-staff-warning">{{ $stats['duplicate_scans'] ?? 0 }}</p>
            </div>
            <div class="staff-card px-5 py-5">
                <p class="text-xs text-staff-soft">Invalid + expired</p>
                <p id="stats-invalid" class="mt-2 text-4xl font-semibold text-staff-danger">{{ ($stats['invalid_scans'] ?? 0) + ($stats['expired_scans'] ?? 0) }}</p>
            </div>
        </section>

        <section class="staff-card px-5 py-5">
            <p class="text-xs text-staff-soft">Latest activity</p>
            <p id="stats-last-result" class="mt-2 text-lg font-semibold text-staff-ink">{{ $stats['last_result'] ?? 'No scans yet' }}</p>
            <p id="stats-last-time" class="mt-1 text-sm text-staff-soft">{{ $stats['last_scanned_at'] ?? '-' }}</p>
        </section>
    </div>
@endsection
