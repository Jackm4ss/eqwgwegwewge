@extends('admin.layouts.app')

@php
  $title = 'Data Attendance';
@endphp

@push('vendor-styles')
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/fonts/flag-icons.css') }}" />
  <style>
    .attendance-sync-status {
      min-width: min(100%, 22rem);
    }

    .attendance-participant-table .avatar {
      --bs-avatar-size: 2.75rem;
    }

    .attendance-country-flag {
      width: 1.15rem;
      height: 0.85rem;
      border-radius: 0.2rem;
      flex: 0 0 auto;
      box-shadow: inset 0 0 0 1px rgba(67, 89, 113, 0.14);
    }

    .attendance-gate-card {
      border: 1px solid rgba(67, 89, 113, 0.12);
      border-radius: 1rem;
      background: rgba(248, 250, 252, 0.82);
    }

    .attendance-progress-track {
      position: relative;
      width: 100%;
      height: 0.55rem;
      border-radius: 999px;
      overflow: hidden;
      background: rgba(var(--bs-primary-rgb), 0.12);
    }

    .attendance-progress-fill {
      position: absolute;
      inset: 0 auto 0 0;
      border-radius: inherit;
      background: linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.7), rgba(var(--bs-primary-rgb), 1));
    }
  </style>
@endpush

@section('content')
  @php
    $adminEventTimezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));
    $countryFlagClass = static function (?string $countryCode): string {
      $countryCode = strtolower(trim((string) $countryCode));

      return preg_match('/^[a-z]{2}$/', $countryCode) ? $countryCode : 'xx';
    };
    $formatDateTime = static function (?string $value) use ($adminEventTimezone): string {
      if (!$value) {
        return '-';
      }

      try {
        return \Carbon\CarbonImmutable::parse($value)->timezone($adminEventTimezone)->format('d M Y, h:i A');
      } catch (\Throwable) {
        return (string) $value;
      }
    };
    $attendanceMeta = static function (?string $status): array {
      return match ($status ?: 'not_checked_in') {
        'checked_in' => ['label' => 'Checked In', 'class' => 'bg-label-success', 'icon' => 'tabler-user-check'],
        'cancelled', 'invalid' => ['label' => ucwords(str_replace('_', ' ', (string) $status)), 'class' => 'bg-label-danger', 'icon' => 'tabler-alert-circle'],
        default => ['label' => 'Not Checked In', 'class' => 'bg-label-warning', 'icon' => 'tabler-clock-hour-4'],
      };
    };
    $scanMeta = static function (?string $status): array {
      return match (strtolower(trim((string) $status))) {
        'success' => ['label' => 'Checked In', 'class' => 'bg-label-success'],
        'duplicate' => ['label' => 'Repeat Scan', 'class' => 'bg-label-warning'],
        default => ['label' => 'Needs Review', 'class' => 'bg-label-danger'],
      };
    };
    $activeFilterCount = collect([
      $filters['q'] ?? null,
      $filters['country'] ?? null,
      $filters['identity_type'] ?? null,
      $filters['attendance_status'] ?? null,
      $filters['scan_result'] ?? null,
      $filters['scanner_post'] ?? null,
      $filters['from'] ?? null,
      $filters['to'] ?? null,
    ])->filter(fn ($value) => filled($value))->count();
  @endphp

  @include('admin.users.partials.sync-badge-script')

  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-6">
    <div>
      <h4 class="mb-1">Data Attendance</h4>
      <p class="text-muted mb-0">Near realtime attendance directory for participant scans, gate activity, and issue follow-up.</p>
    </div>
    @include('admin.users.partials.sync-badge', [
      'syncStatus' => $syncStatus ?? null,
      'syncBadgeId' => 'attendance-sync-status',
      'syncBadgeClass' => 'attendance-sync-status ms-lg-auto',
    ])
  </div>

  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <span class="text-heading d-block mb-1">Total Attendance</span>
              <h3 class="card-title mb-1">{{ number_format($overview['total_attendance'] ?? 0) }}</h3>
              <small>Participants with scan records in the selected range</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-users"></i></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <span class="text-heading d-block mb-1">Checked In</span>
              <h3 class="card-title mb-1">{{ number_format($overview['checked_in'] ?? 0) }}</h3>
              <small>Unique participants with successful scans</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success"><i class="icon-base ti tabler-user-check"></i></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <span class="text-heading d-block mb-1">Repeat Scans</span>
              <h3 class="card-title mb-1">{{ number_format($overview['repeat_scans'] ?? 0) }}</h3>
              <small>The same ticket was scanned again, but it does not increase attendance.</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning"><i class="icon-base ti tabler-repeat"></i></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <span class="text-heading d-block mb-1">Needs Review</span>
              <h3 class="card-title mb-1">{{ number_format($overview['needs_review'] ?? 0) }}</h3>
              <small>There was an issue during the scan, so an admin or staff member should check it.</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger"><i class="icon-base ti tabler-alert-triangle"></i></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if (!empty($overview['gate_counts']))
    <div class="row g-4 mb-6">
      @foreach ($overview['gate_counts'] as $gate)
        <div class="col-sm-6 col-xl-3">
          <div class="card attendance-gate-card h-100">
            <div class="card-body">
              <span class="text-heading d-block mb-1">{{ $gate['label'] ?? '-' }}</span>
              <h4 class="mb-1">{{ number_format($gate['count'] ?? 0) }}</h4>
              <small class="text-muted">Total scans recorded by this gate</small>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  <div class="card mb-6">
    <form method="GET" action="{{ route('admin.attendance.index') }}">
      <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
          <h5 class="mb-1">Filters</h5>
          <small class="text-muted">Search participant by name, entry code, passport or Malaysia IC (MyKad) number, or phone number.</small>
        </div>
        <div class="d-flex flex-column align-items-lg-end gap-2">
          <div class="d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-sm btn-primary">
              <i class="icon-base ti tabler-search me-1"></i> Apply Filters
            </button>
            <a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-danger">
              <i class="icon-base ti tabler-rotate-clockwise-2 me-1"></i> Reset
            </a>
          </div>
          @if ($activeFilterCount > 0)
            <span class="badge bg-label-primary">{{ $activeFilterCount }} active filters</span>
          @endif
        </div>
      </div>

      <div class="card-body">
        <div class="row g-4">
          <div class="col-md-3">
            <label for="country" class="form-label">Country</label>
            <select class="form-select" id="country" name="country">
              <option value="">All Countries</option>
              @foreach ($filterOptions['countries'] ?? [] as $country)
                <option value="{{ $country['value'] }}" @selected(($filters['country'] ?? '') === ($country['value'] ?? ''))>
                  {{ $country['label'] ?? '-' }} ({{ number_format($country['count'] ?? 0) }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label for="identity_type" class="form-label">Document Type</label>
            <select class="form-select" id="identity_type" name="identity_type">
              <option value="">All Document Types</option>
              @foreach ($filterOptions['identity_types'] ?? [] as $identityType)
                <option value="{{ $identityType['value'] }}" @selected(($filters['identity_type'] ?? '') === ($identityType['value'] ?? ''))>
                  {{ $identityType['label'] ?? '-' }} ({{ number_format($identityType['count'] ?? 0) }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label for="attendance_status" class="form-label">Check-In Status</label>
            <select class="form-select" id="attendance_status" name="attendance_status">
              <option value="">All Check-In Statuses</option>
              @foreach ($filterOptions['attendance_statuses'] ?? [] as $status)
                <option value="{{ $status['value'] }}" @selected(($filters['attendance_status'] ?? '') === ($status['value'] ?? ''))>
                  {{ $status['label'] ?? '-' }} ({{ number_format($status['count'] ?? 0) }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label for="scan_result" class="form-label">Scan Result</label>
            <select class="form-select" id="scan_result" name="scan_result">
              <option value="">All Scan Results</option>
              @foreach ($filterOptions['scan_results'] ?? [] as $scanResult)
                <option value="{{ $scanResult['value'] }}" @selected(($filters['scan_result'] ?? '') === ($scanResult['value'] ?? ''))>
                  {{ $scanResult['label'] ?? '-' }} ({{ number_format($scanResult['count'] ?? 0) }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label for="scanner_post" class="form-label">Scan Post</label>
            <select class="form-select" id="scanner_post" name="scanner_post">
              <option value="">All Scan Posts</option>
              @foreach ($filterOptions['scan_posts'] ?? [] as $scanPost)
                <option value="{{ $scanPost['value'] }}" @selected(($filters['scanner_post'] ?? '') === ($scanPost['value'] ?? ''))>
                  {{ $scanPost['label'] ?? '-' }} ({{ number_format($scanPost['count'] ?? 0) }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label for="from" class="form-label">From Date</label>
            <input type="date" class="form-control" id="from" name="from" value="{{ $filters['from'] ?? '' }}" />
          </div>

          <div class="col-md-3">
            <label for="to" class="form-label">To Date</label>
            <input type="date" class="form-control" id="to" name="to" value="{{ $filters['to'] ?? '' }}" />
          </div>
        </div>
      </div>

      <div class="card-body border-top">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-4">
          <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
            <div style="width: 90px;">
              <select class="form-select" id="per_page" name="per_page">
                @foreach ([10, 15, 25, 50] as $perPageOption)
                  <option value="{{ $perPageOption }}" @selected((int) ($filters['per_page'] ?? config('admin.per_page', 10)) === $perPageOption)>{{ $perPageOption }}</option>
                @endforeach
              </select>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ number_format($rows->total()) }} participants found</span>
              <small class="text-muted">Showing grouped participant rows from the latest matching scan activity.</small>
            </div>
          </div>

          <div class="input-group input-group-merge" style="max-width: 420px;">
            <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
            <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
              placeholder="Search participant, entry code, passport/MyKad, phone" />
            <button type="submit" class="btn btn-outline-primary">Apply Filters</button>
          </div>
        </div>
      </div>
    </form>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover align-middle attendance-participant-table">
        <thead>
          <tr>
            <th>Participant</th>
            <th>Country</th>
            <th>Entry Code</th>
            <th>Attendance</th>
            <th>Check-In Status</th>
            <th>Scan Post</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($rows as $row)
            @php
              $attendance = $attendanceMeta($row['attendance_status'] ?? null);
              $latestScan = $scanMeta($row['latest_scan_result'] ?? null);
            @endphp
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar me-4">
                    <span class="avatar-initial rounded-circle bg-label-primary">{{ $row['initials'] ?? 'P' }}</span>
                  </div>
                  <div class="d-flex flex-column">
                    <span class="text-heading fw-medium">{{ $row['full_name'] ?: '-' }}</span>
                    <small class="text-muted">{{ $row['email'] ?: '-' }}</small>
                  </div>
                </div>
              </td>

              <td>
                <span class="fw-medium d-inline-flex align-items-center gap-2">
                  <span class="fi fis fi-{{ $countryFlagClass($row['country'] ?? null) }} attendance-country-flag"></span>
                  <span>{{ $row['country_label'] ?? ($row['country'] ?? '-') }}</span>
                </span>
              </td>

              <td>
                <span class="fw-medium">{{ $row['entry_code_display'] ?: '-' }}</span>
              </td>

              <td style="min-width: 13.5rem;">
                <div class="d-flex justify-content-between gap-3 mb-2">
                  <span class="fw-medium">{{ (int) ($row['attendance_days_count'] ?? 0) }}/{{ max(0, (int) ($row['attendance_total_days'] ?? 0)) }} days</span>
                  <span class="text-primary fw-semibold">{{ max(0, min(100, (int) ($row['attendance_progress_percent'] ?? 0))) }}%</span>
                </div>
                <div class="attendance-progress-track" role="progressbar" aria-valuenow="{{ max(0, min(100, (int) ($row['attendance_progress_percent'] ?? 0))) }}" aria-valuemin="0" aria-valuemax="100">
                  <div class="attendance-progress-fill" style="width: {{ max(0, min(100, (int) ($row['attendance_progress_percent'] ?? 0))) }}%;"></div>
                </div>
              </td>

              <td>
                <div class="d-flex flex-column gap-2">
                  <span class="badge {{ $attendance['class'] }} d-inline-flex align-self-start">
                    <i class="icon-base ti {{ $attendance['icon'] }} me-1"></i>{{ $attendance['label'] }}
                  </span>
                  @if (!empty($row['checked_in_at']))
                    <small class="text-muted">{{ $formatDateTime($row['checked_in_at']) }}</small>
                  @endif
                </div>
              </td>

              <td>
                <div class="d-flex flex-column gap-2">
                  <span class="fw-medium">{{ $row['scanner_name'] ?: '-' }}</span>
                  <small class="text-muted">{{ $formatDateTime($row['latest_scan_at'] ?? null) }}</small>
                  <span class="badge {{ $latestScan['class'] }} d-inline-flex align-self-start">{{ $latestScan['label'] }}</span>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-8">
                <div class="d-flex flex-column align-items-center gap-2 text-muted">
                  <i class="icon-base ti tabler-scan-off fs-1"></i>
                  <span class="fw-medium">No attendance data matches the current filters.</span>
                  <small>Try changing the search keyword, scan post, or date range.</small>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-body border-top">
      {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  </div>
@endsection
