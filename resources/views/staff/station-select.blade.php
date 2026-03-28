@extends('staff.layouts.app', ['title' => 'Setup Scanner', 'subtitle' => 'Choose one gate for this device, then continue to the scanner page.'])

@section('content')
    <div class="space-y-4">
        @if ($stations->isEmpty())
            <div class="staff-card px-5 py-5 text-sm text-staff-soft">
                No active stations found. Run <code>php artisan staff:seed --password=...</code> to create the default gate and staff account data.
            </div>
        @else
            <form method="POST" action="{{ route('staff.station.store') }}" class="staff-card w-full px-5 py-5">
                @csrf

                <div class="space-y-4">
                    <div>
                        <h2 class="text-xl font-semibold text-staff-ink">Select Gate</h2>
                        <p class="mt-2 text-sm text-staff-soft">Choose the gate where this staff member is assigned. After selection, the device will go directly to the scanner page.</p>
                    </div>

                    <div>
                        <label for="station_id" class="mb-2 block text-sm font-semibold text-staff-ink">Gate / Station</label>
                        <div class="relative">
                            <select id="station_id" name="station_id" class="staff-input appearance-none pr-12">
                                <option value="" disabled @selected(! old('station_id', $selectedStationId))>Select scanner gate</option>

                                @foreach ($stations as $station)
                                    <option
                                        value="{{ $station->id }}"
                                        @selected((int) old('station_id', $selectedStationId) === (int) $station->id)
                                    >
                                        {{ $station->gate_name }} - {{ $station->scanner_name }}
                                    </option>
                                @endforeach
                            </select>

                            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-staff-soft" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="staff-badge-success">{{ $stations->count() }} active gates</span>
                    </div>

                    <button type="submit" class="staff-button-primary w-full">Continue to Scanner</button>
                </div>
            </form>
        @endif
    </div>
@endsection
