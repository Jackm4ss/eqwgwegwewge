@extends('admin.layouts.app')

@php
  $title = 'User Management';
@endphp

@push('vendor-styles')
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/fonts/flag-icons.css') }}" />
  <style>
    .user-management-search {
      min-width: min(100%, 320px);
    }

    .user-country-flag {
      width: 1.15rem;
      height: 0.85rem;
      border-radius: 0.2rem;
      flex: 0 0 auto;
      box-shadow: inset 0 0 0 1px rgba(67, 89, 113, 0.14);
    }

    .user-management-table .avatar {
      --bs-avatar-size: 2.75rem;
    }

    .user-management-table .btn.btn-icon {
      width: 2.75rem;
      height: 2.75rem;
      min-width: 2.75rem;
    }

    .user-management-table .btn.btn-icon .icon-base,
    .user-management-table .btn.btn-icon .ti {
      font-size: 2.75rem;
      line-height: 1;
    }

    .user-management-action button.dropdown-item {
      border: 0;
      background: transparent;
      width: 100%;
      text-align: left;
    }

    .user-management-table .user-name-trigger {
      border: 0;
      background: transparent;
      padding: 0;
      text-align: left;
    }

    .event-progress-cell {
      min-width: 13.5rem;
    }

    .event-progress-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.45rem;
    }

    .event-progress-title {
      font-weight: 600;
      color: var(--bs-heading-color);
      line-height: 1.2;
    }

    .event-progress-caption {
      display: block;
      color: var(--bs-secondary-color);
      font-size: 0.75rem;
      margin-top: 0.15rem;
    }

    .event-progress-percent {
      font-size: 0.8125rem;
      font-weight: 700;
      color: var(--bs-primary);
      white-space: nowrap;
    }

    .event-progress-track {
      position: relative;
      width: 100%;
      height: 0.55rem;
      border-radius: 999px;
      overflow: hidden;
      background: rgba(var(--bs-primary-rgb), 0.12);
    }

    .event-progress-fill {
      position: absolute;
      inset: 0 auto 0 0;
      border-radius: inherit;
      background: linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.75), rgba(var(--bs-primary-rgb), 1));
    }

    .event-progress-meta {
      display: flex;
      justify-content: space-between;
      gap: 0.75rem;
      margin-top: 0.45rem;
      color: var(--bs-secondary-color);
      font-size: 0.75rem;
    }

    .user-qr-preview-link {
      display: inline-flex;
      flex-direction: column;
      gap: 0.6rem;
      margin-top: 0.85rem;
      text-decoration: none;
    }

    .user-qr-preview-link.is-disabled {
      pointer-events: none;
      opacity: 0.65;
    }

    .user-qr-preview-thumb {
      position: relative;
      width: 6rem;
      height: 6rem;
      border-radius: 1rem;
      overflow: hidden;
      border: 1px solid rgba(67, 89, 113, 0.14);
      background:
        linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.16), rgba(var(--bs-primary-rgb), 0.05)),
        repeating-linear-gradient(0deg, rgba(43, 52, 69, 0.85) 0 0.42rem, rgba(255, 255, 255, 0.92) 0.42rem 0.82rem),
        repeating-linear-gradient(90deg, rgba(43, 52, 69, 0.85) 0 0.42rem, rgba(255, 255, 255, 0.92) 0.42rem 0.82rem);
      filter: blur(2.8px) saturate(0.9);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
    }

    .user-qr-preview-thumb::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.28), transparent 55%);
    }

    .user-qr-preview-text {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--bs-primary);
    }

    .user-overview-modal .modal-dialog {
      max-width: 760px;
    }

    .user-overview-shell {
      border-radius: 1.5rem;
      border: 1px solid rgba(67, 89, 113, 0.12);
      background:
        radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.14), transparent 34%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 247, 251, 0.98));
      box-shadow: 0 1.25rem 3rem rgba(67, 89, 113, 0.12);
    }

    .user-overview-header {
      padding: 1.5rem 1.5rem 0;
    }

    .user-overview-body {
      padding: 0 1.5rem 1.5rem;
    }

    .user-overview-profile {
      border: 1px solid rgba(67, 89, 113, 0.12);
      border-radius: 1.25rem;
      background: rgba(255, 255, 255, 0.88);
      padding: 1.25rem;
    }

    .user-overview-profile .avatar {
      --bs-avatar-size: 4rem;
    }

    .user-overview-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1rem;
    }

    .user-overview-item {
      border: 1px solid rgba(67, 89, 113, 0.12);
      border-radius: 1rem;
      background: rgba(255, 255, 255, 0.88);
      padding: 1rem;
      min-height: 100%;
    }

    .user-overview-item-label {
      display: block;
      margin-bottom: 0.3rem;
      color: var(--bs-secondary-color);
      font-size: 0.8125rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .user-overview-item-value {
      color: var(--bs-heading-color);
      font-weight: 600;
      word-break: break-word;
    }

    @media (max-width: 767.98px) {
      .user-overview-grid {
        grid-template-columns: 1fr;
      }

      .user-overview-header,
      .user-overview-body {
        padding-left: 1rem;
        padding-right: 1rem;
      }
    }
  </style>
@endpush

@section('content')
  @php
    $adminEventTimezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));
    $initials = static function (?string $name): string {
      $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
      $letters = collect($parts)
        ->take(2)
        ->map(fn(string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

      return $letters !== '' ? $letters : 'U';
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

    $countryFlagClass = static function (?string $countryCode): string {
      $countryCode = strtolower(trim((string) $countryCode));

      if (!preg_match('/^[a-z]{2}$/', $countryCode)) {
        return 'xx';
      }

      return $countryCode;
    };

    $qrMeta = static function (array $user) use ($formatDateTime): string {
      if (blank($user['ticket_code'] ?? null)) {
        return 'QR not generated';
      }

      $qrTimestamp = $user['ticket_regenerated_at'] ?? $user['ticket_created_at'] ?? $user['ticket_updated_at'] ?? null;

      if (blank($qrTimestamp)) {
        return 'QR ready to use';
      }

      $label = filled($user['ticket_regenerated_at'] ?? null) ? 'QR Regenerated' : 'QR Created';

      return $label . ', ' . $formatDateTime($qrTimestamp);
    };

    $attendanceMeta = static function (?string $status): array {
      return match ($status ?: 'not_checked_in') {
        'checked_in' => ['label' => 'Checked In', 'class' => 'bg-label-success', 'icon' => 'tabler-user-check'],
        'cancelled', 'invalid' => ['label' => ucwords(str_replace('_', ' ', (string) $status)), 'class' => 'bg-label-danger', 'icon' => 'tabler-alert-circle'],
        default => ['label' => 'Not Checked In', 'class' => 'bg-label-warning', 'icon' => 'tabler-clock-hour-4'],
      };
    };

    $verificationMeta = static function (?string $status): array {
      return match ($status ?: 'unverified') {
        'verified' => ['label' => 'Verified', 'class' => 'bg-label-success'],
        default => ['label' => 'Unverified', 'class' => 'bg-label-secondary'],
      };
    };

    $accountMeta = static function (?string $status): array {
      return match ($status ?: 'pending_verification') {
        'active' => ['label' => 'Active', 'class' => 'bg-label-primary'],
        'blocked' => ['label' => 'Blocked', 'class' => 'bg-label-danger'],
        default => ['label' => 'Pending', 'class' => 'bg-label-warning'],
      };
    };

    $activeFilterCount = collect([
      $filters['country'] ?? null,
      $filters['verification_status'] ?? null,
      $filters['attendance_status'] ?? null,
      $filters['q'] ?? null,
    ])->filter(fn($value) => filled($value))->count();

    $eventStartDate = \Carbon\CarbonImmutable::parse(
      config('admin.event.start_date', '2026-04-09'),
      $adminEventTimezone
    )->startOfDay();
    $eventEndDate = \Carbon\CarbonImmutable::parse(
      config('admin.event.end_date', '2026-04-19'),
      $adminEventTimezone
    )->startOfDay();
    $eventTotalDays = max(1, $eventStartDate->diffInDays($eventEndDate) + 1);
    $exportQuery = request()->except('page');
  @endphp

  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <span class="text-heading d-block mb-1">Total Participants</span>
              <h3 class="card-title mb-1">{{ number_format($overview['total_users'] ?? 0) }}</h3>
              <small>Total registrations</small>
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
              <span class="text-heading d-block mb-1">Verified Participants</span>
              <h3 class="card-title mb-1">{{ number_format($overview['verified_users'] ?? 0) }}</h3>
              <small class="text-muted">Ready to sign in and fully verified</small>
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
              <span class="text-heading d-block mb-1">Checked In</span>
              <h3 class="card-title mb-1">{{ number_format($overview['checked_in_users'] ?? 0) }}</h3>
              <small class="text-muted">Participants with recorded attendance</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info"><i class="icon-base ti tabler-scan"></i></span>
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
              <span class="text-heading d-block mb-1">Needs Follow-up</span>
              <h3 class="card-title mb-1">{{ number_format($overview['follow_up_users'] ?? 0) }}</h3>
              <small class="text-muted">Pending verification or inactive status</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning"><i
                  class="icon-base ti tabler-alert-triangle"></i></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <form method="GET" action="{{ route('admin.users.index') }}" id="userFiltersForm">
      <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
          <h5 class="mb-1">Filters</h5>
          <small class="text-muted">Filter participants by country, verification, or check-in status.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          @if ($activeFilterCount > 0)
            <span class="badge bg-label-primary">{{ $activeFilterCount }} active filters</span>
          @endif
          <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-label-secondary">
            <i class="icon-base ti tabler-rotate-clockwise-2 me-1"></i> Reset
          </a>
        </div>
      </div>

      <div class="card-body">
        <div class="row g-4">
          <div class="col-md-4">
            <label for="country" class="form-label">Country</label>
            <select class="form-select js-submit-on-change" id="country" name="country">
              <option value="">All Countries</option>
              @foreach ($filterOptions['countries'] ?? [] as $country)
                <option value="{{ $country['value'] }}" data-flag="{{ $countryFlagClass($country['value']) }}" @selected(($filters['country'] ?? '') === $country['value'])>
                  {{ $country['label'] }} ({{ number_format($country['count']) }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label for="verification_status" class="form-label">Verification</label>
            <select class="form-select js-submit-on-change" id="verification_status" name="verification_status">
              <option value="">All Verification Statuses</option>
              @foreach ($filterOptions['verification_statuses'] ?? [] as $status)
                <option value="{{ $status['value'] }}" @selected(($filters['verification_status'] ?? '') === $status['value'])>
                  {{ $status['label'] }} ({{ number_format($status['count']) }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label for="attendance_status" class="form-label">Check-In Status</label>
            <select class="form-select js-submit-on-change" id="attendance_status" name="attendance_status">
              <option value="">All Check-In Statuses</option>
              @foreach ($filterOptions['attendance_statuses'] ?? [] as $status)
                <option value="{{ $status['value'] }}" @selected(($filters['attendance_status'] ?? '') === $status['value'])>
                  {{ $status['label'] }} ({{ number_format($status['count']) }})
                </option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      <div class="card-body border-top">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-4">
          <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
            <div style="width: 90px;">
              <select class="form-select js-submit-on-change" id="per_page" name="per_page">
                @foreach ([10, 15, 25, 50] as $perPageOption)
                  <option value="{{ $perPageOption }}" @selected((int) ($filters['per_page'] ?? config('admin.per_page', 10)) === $perPageOption)>{{ $perPageOption }}</option>
                @endforeach
              </select>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ number_format($users->total()) }} participants found</span>
              <small class="text-muted">Search includes name, email, identity number, ticket code, country, and traffic source.</small>
            </div>
          </div>

            <div class="d-flex flex-column flex-lg-row align-items-stretch gap-3">
              <div class="input-group input-group-merge user-management-search">
                <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                placeholder="Search participants" />
                <button type="submit" class="btn btn-outline-primary">Search</button>
            </div>

            <div class="btn-group">
              <button type="button" class="btn btn-label-secondary dropdown-toggle" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="icon-base ti tabler-upload me-1"></i> Export
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <a class="dropdown-item"
                    href="{{ route('admin.exports.download', array_merge($exportQuery, ['type' => 'users', 'format' => 'csv'])) }}">
                    <i class="icon-base ti tabler-file-type-csv me-2"></i> Export CSV
                  </a>
                </li>
                <li>
                  <a class="dropdown-item"
                    href="{{ route('admin.exports.download', array_merge($exportQuery, ['type' => 'users', 'format' => 'xlsx'])) }}">
                    <i class="icon-base ti tabler-file-spreadsheet me-2"></i> Export Excel
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </form>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover align-middle user-management-table">
        <thead>
          <tr>
            <th>Participant</th>
            <th>Country</th>
            <th>Registrant Source</th>
            <th>Attendance</th>
            <th>Check-In Status</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($users as $user)
            @php
              $attendance = $attendanceMeta($user['attendance_status'] ?? null);
              $verification = $verificationMeta($user['verification_status'] ?? null);
              $account = $accountMeta($user['account_status'] ?? null);
              $attendanceDays = is_array($user['attendance_days'] ?? null) ? $user['attendance_days'] : [];
              $attendanceDaysCount = (int) ($user['attendance_days_count'] ?? 0);
              $attendanceTotalDays = max(1, (int) ($user['attendance_total_days'] ?? $eventTotalDays));
              $attendanceProgressPercent = max(0, min(100, (int) ($user['attendance_progress_percent'] ?? 0)));
              $ticketViewUrl = filled($user['ticket_id'] ?? null)
                ? \Illuminate\Support\Facades\URL::signedRoute('ticket.show', ['ticketId' => (string) $user['ticket_id']])
                : null;
              $userDomId = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($user['user_id'] ?? 'user'));
              $detailSourceId = 'userOverviewData-' . $userDomId;
              $detailPayload = [
                'user_id' => (string) ($user['user_id'] ?? '-'),
                'full_name' => (string) ($user['full_name'] ?? '-'),
                'initials' => $initials($user['full_name'] ?? null),
                'email' => (string) ($user['email'] ?? '-'),
                'phone_number' => filled($user['phone_number'] ?? null) ? (string) $user['phone_number'] : '-',
                'country_label' => (string) ($user['country_label'] ?? ($user['country'] ?? '-')),
                'country_flag' => $countryFlagClass($user['country'] ?? null),
                'identity_label' => ($user['identity_type'] ?? 'passport') === 'national_id' ? 'Malaysia IC (MyKad)' : 'Passport',
                'identity_number' => filled($user['identity_number'] ?? null) ? (string) $user['identity_number'] : '-',
                'ticket_code' => filled($user['ticket_code'] ?? null) ? (string) $user['ticket_code'] : 'No ticket yet',
                'qr_meta' => $qrMeta($user),
                'checked_in_at' => filled($user['checked_in_at'] ?? null) ? $formatDateTime($user['checked_in_at']) : '-',
                'attendance_count_label' => $attendanceDaysCount . ' / ' . $attendanceTotalDays . ' Days',
                'traffic_source_label' => (string) ($user['traffic_source_label'] ?? 'Not Captured'),
                'traffic_source_caption' => (string) ($user['traffic_source_caption'] ?? 'Registrant source has not been captured yet'),
                'traffic_medium_label' => (string) ($user['traffic_medium_label'] ?? '-'),
                'traffic_campaign' => filled($user['traffic_campaign'] ?? null) ? (string) $user['traffic_campaign'] : '-',
                'traffic_referrer_host' => filled($user['traffic_referrer_host'] ?? null) ? (string) $user['traffic_referrer_host'] : '-',
                'traffic_landing_path' => filled($user['traffic_landing_path'] ?? null) ? (string) $user['traffic_landing_path'] : '-',
                'traffic_captured_at' => filled($user['traffic_captured_at'] ?? null) ? $formatDateTime($user['traffic_captured_at']) : '-',
                'attendance' => $attendance,
                'account' => $account,
                'verification' => $verification,
                'edit_url' => route('admin.users.edit', $user['user_id']),
                'ticket_view_url' => $ticketViewUrl,
              ];
            @endphp
            <tr>
              <td>
                <script type="application/json" id="{{ $detailSourceId }}">@json($detailPayload)</script>
                <div class="d-flex align-items-center">
                  <div class="avatar me-4">
                    <span
                      class="avatar-initial rounded-circle bg-label-primary">{{ $initials($user['full_name'] ?? null) }}</span>
                  </div>
                  <div class="d-flex flex-column">
                    <button type="button" class="user-name-trigger text-heading fw-medium js-user-overview-trigger"
                      data-bs-toggle="modal" data-bs-target="#userOverviewModal"
                      data-user-detail-source="{{ $detailSourceId }}">
                      {{ $user['full_name'] ?? '-' }}
                    </button>
                    <small class="text-muted">{{ $user['email'] ?? '-' }}</small>
                  </div>
                </div>
              </td>

              <td>
                <div class="d-flex flex-column">
                  <span class="fw-medium d-inline-flex align-items-center gap-2">
                    <span class="fi fis fi-{{ $countryFlagClass($user['country'] ?? null) }} user-country-flag"></span>
                    <span>{{ $user['country_label'] ?? ($user['country'] ?? '-') }}</span>
                  </span>
                </div>
              </td>

              <td>
                <div class="d-flex flex-column">
                  <span class="fw-medium">{{ $user['traffic_source_label'] ?? 'Not Captured' }}</span>
                  <small class="text-muted">{{ $user['traffic_source_caption'] ?? 'Registrant source has not been captured yet' }}</small>
                </div>
              </td>

              <td>
                <div class="event-progress-cell">
                  <div class="event-progress-header">
                    <div>
                      <span class="event-progress-title">{{ $attendanceDaysCount }}/{{ $attendanceTotalDays }} days</span>
                    </div>
                    <span class="event-progress-percent">{{ $attendanceProgressPercent }}%</span>
                  </div>
                  <div class="event-progress-track" role="progressbar" aria-label="Participant attendance progress"
                    aria-valuenow="{{ $attendanceProgressPercent }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="event-progress-fill" style="width: {{ $attendanceProgressPercent }}%;"></div>
                  </div>
                </div>
              </td>

              <td>
                <div class="d-flex flex-column gap-2">
                  <span class="badge {{ $attendance['class'] }} d-inline-flex align-self-start">
                    <i class="icon-base ti {{ $attendance['icon'] }} me-1"></i>{{ $attendance['label'] }}
                  </span>
                  @if (!empty($user['checked_in_at']))
                    <small class="text-muted">
                      {{ $formatDateTime($user['checked_in_at']) }}
                    </small>
                  @endif
                </div>
              </td>

              <td>
                <div class="d-flex flex-column gap-2">
                  <span class="badge {{ $account['class'] }} d-inline-flex align-self-start">{{ $account['label'] }}</span>
                  <span
                    class="badge {{ $verification['class'] }} d-inline-flex align-self-start">{{ $verification['label'] }}</span>
                </div>
              </td>

              <td class="text-end">
                <div class="d-inline-flex align-items-center gap-1">
                  <form method="POST" action="{{ route('admin.users.destroy', $user['user_id']) }}"
                    class="d-inline js-delete-user-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-icon btn-text-danger rounded-pill js-delete-user-trigger"
                      title="Delete Participant" data-user-name="{{ $user['full_name'] ?? 'this participant' }}"
                      data-user-email="{{ $user['email'] ?? '' }}">
                      <i class="icon-base ti tabler-trash"></i>
                    </button>
                  </form>

                  <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill js-user-overview-trigger"
                    title="View Details" data-bs-toggle="modal" data-bs-target="#userOverviewModal"
                    data-user-detail-source="{{ $detailSourceId }}">
                    <i class="icon-base ti tabler-eye"></i>
                  </button>

                  <div class="dropdown user-management-action">
                    <button type="button"
                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                      data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="icon-base ti tabler-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                      <a href="{{ route('admin.users.edit', $user['user_id']) }}" class="dropdown-item">
                        <i class="icon-base ti tabler-edit me-2"></i> Edit Participant
                      </a>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-8">
                <div class="d-flex flex-column align-items-center gap-2 text-muted">
                  <i class="icon-base ti tabler-users-minus fs-1"></i>
                  <span class="fw-medium">No participants match the current filters.</span>
                  <small>Try changing the search keyword or reset filters to view other data.</small>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-body border-top">
      {{ $users->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  </div>

  <div class="modal fade user-overview-modal js-user-overview-modal" id="userOverviewModal" tabindex="-1"
    aria-labelledby="userOverviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content user-overview-shell border-0">
        <div class="user-overview-header">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <span class="badge rounded-pill bg-label-primary px-3 py-2 mb-3">Participant Overview</span>
              <h4 class="mb-1" id="userOverviewModalLabel">Participant Details</h4>
              <p class="text-muted mb-0">Summary of key participant information</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
        </div>

        <div class="modal-body user-overview-body">
          <div class="user-overview-profile mb-4">
            <div class="d-flex flex-column flex-sm-row align-items-sm-start gap-3">
              <div class="avatar">
                <span class="avatar-initial rounded-circle bg-label-primary" data-detail-initials>U</span>
              </div>
              <div class="flex-grow-1">
                <h5 class="mb-1" data-detail-name>-</h5>
                <p class="text-muted mb-3" data-detail-email>-</p>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge bg-label-secondary" data-detail-account-badge>
                    <span data-detail-account-label>-</span>
                  </span>
                  <span class="badge bg-label-secondary" data-detail-verification-badge>
                    <span data-detail-verification-label>-</span>
                  </span>
                  <span class="badge bg-label-secondary" data-detail-attendance-badge>
                    <i class="icon-base ti me-1" data-detail-attendance-icon style="display:none;"></i>
                    <span data-detail-attendance-label>-</span>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="user-overview-grid">
            <div class="user-overview-item">
              <span class="user-overview-item-label">User ID</span>
              <div class="user-overview-item-value" data-detail-user-id>-</div>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">Country</span>
              <div class="user-overview-item-value d-inline-flex align-items-center gap-2">
                <span class="fi fis fi-xx user-country-flag" data-detail-country-flag></span>
                <span data-detail-country-label>-</span>
              </div>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">Phone Number / WhatsApp</span>
              <div class="user-overview-item-value" data-detail-phone>-</div>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">Identity Document</span>
              <div class="user-overview-item-value">
                <span data-detail-identity-label>-</span>
                <span class="text-muted"> / </span>
                <span data-detail-identity-number>-</span>
              </div>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">Ticket Code</span>
              <div class="user-overview-item-value" data-detail-ticket-code>-</div>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">Registrant Source</span>
              <div class="user-overview-item-value" data-detail-traffic-source>-</div>
              <small class="text-muted d-block mt-1" data-detail-traffic-caption>-</small>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">QR Info</span>
              <div class="user-overview-item-value" data-detail-qr-meta>-</div>
              <a href="#" target="_blank" rel="noopener" class="user-qr-preview-link is-disabled" data-detail-qr-link
                aria-disabled="true">
                <span class="user-qr-preview-thumb" aria-hidden="true"></span>
                <span class="user-qr-preview-text">Click to open the full QR</span>
              </a>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">Check-In Time</span>
              <div class="user-overview-item-value" data-detail-checked-in-at>-</div>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">Source Details</span>
              <div class="user-overview-item-value">
                <div><span class="text-muted">Promo link:</span> <span data-detail-traffic-campaign>-</span></div>
                <div><span class="text-muted">Source website / app:</span> <span data-detail-traffic-referrer>-</span></div>
                <div><span class="text-muted">Entry method:</span> <span data-detail-traffic-medium>-</span></div>
              </div>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">Attendance Count</span>
              <div class="user-overview-item-value" data-detail-attendance-count>-</div>
            </div>

            <div class="user-overview-item">
              <span class="user-overview-item-label">First Page</span>
              <div class="user-overview-item-value" data-detail-traffic-landing>-</div>
              <small class="text-muted d-block mt-1">Captured at: <span data-detail-traffic-captured-at>-</span></small>
            </div>
          </div>

          <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-primary px-4" data-detail-edit-link>
              Edit Participant
            </a>
            <button type="button" class="btn btn-label-secondary px-4" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.querySelector('#userFiltersForm');
      const detailModal = document.querySelector('#userOverviewModal');

      if (!form) {
        return;
      }

      document.querySelectorAll('.js-submit-on-change').forEach(function (element) {
        element.addEventListener('change', function () {
          form.submit();
        });
      });

      document.querySelectorAll('.js-delete-user-form').forEach(function (deleteForm) {
        deleteForm.addEventListener('submit', function (event) {
          event.preventDefault();

          const trigger = deleteForm.querySelector('.js-delete-user-trigger');
          const userName = trigger?.dataset.userName || 'this participant';
          const userEmail = trigger?.dataset.userEmail || '';
          const message = userEmail
            ? `${userName} (${userEmail}) will be deleted from the participant records.`
            : `${userName} will be deleted from the participant records.`;

          const submitDelete = function () {
            deleteForm.submit();
          };

          if (window.confirm(`${message}\n\nContinue deleting this participant?`)) {
            submitDelete();
          }
        });
      });

      const setText = function (selector, value) {
        const element = detailModal?.querySelector(selector);

        if (element) {
          element.textContent = value && String(value).trim() !== '' ? value : '-';
        }
      };

      const setSimpleBadge = function (badgeSelector, labelSelector, meta) {
        const badge = detailModal?.querySelector(badgeSelector);
        const label = detailModal?.querySelector(labelSelector);

        if (badge) {
          badge.className = `badge ${meta?.class || 'bg-label-secondary'}`;
        }

        if (label) {
          label.textContent = meta?.label || '-';
        }
      };

      const readDetailPayload = function (sourceId) {
        const source = sourceId ? document.getElementById(sourceId) : null;

        if (!source) {
          return null;
        }

        try {
          return JSON.parse(source.textContent || '{}');
        } catch (error) {
          console.error('Failed to parse user overview payload.', error);
          return null;
        }
      };

      if (detailModal) {
        detailModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const payload = readDetailPayload(trigger?.dataset.userDetailSource);

          if (!payload) {
            return;
          }

          setText('[data-detail-initials]', payload.initials);
          setText('[data-detail-name]', payload.full_name);
          setText('[data-detail-email]', payload.email);
          setText('[data-detail-user-id]', payload.user_id);
          setText('[data-detail-phone]', payload.phone_number);
          setText('[data-detail-country-label]', payload.country_label);
          setText('[data-detail-identity-label]', payload.identity_label);
          setText('[data-detail-identity-number]', payload.identity_number);
          setText('[data-detail-ticket-code]', payload.ticket_code);
          setText('[data-detail-qr-meta]', payload.qr_meta);
          setText('[data-detail-checked-in-at]', payload.checked_in_at);
          setText('[data-detail-attendance-count]', payload.attendance_count_label);
          setText('[data-detail-traffic-source]', payload.traffic_source_label);
          setText('[data-detail-traffic-caption]', payload.traffic_source_caption);
          setText('[data-detail-traffic-campaign]', payload.traffic_campaign);
          setText('[data-detail-traffic-referrer]', payload.traffic_referrer_host);
          setText('[data-detail-traffic-medium]', payload.traffic_medium_label);
          setText('[data-detail-traffic-landing]', payload.traffic_landing_path);
          setText('[data-detail-traffic-captured-at]', payload.traffic_captured_at);

          const countryFlag = detailModal.querySelector('[data-detail-country-flag]');

          if (countryFlag) {
            countryFlag.className = `fi fis fi-${payload.country_flag || 'xx'} user-country-flag`;
          }

          setSimpleBadge('[data-detail-account-badge]', '[data-detail-account-label]', payload.account);
          setSimpleBadge('[data-detail-verification-badge]', '[data-detail-verification-label]', payload.verification);
          setSimpleBadge('[data-detail-attendance-badge]', '[data-detail-attendance-label]', payload.attendance);

          const attendanceIcon = detailModal.querySelector('[data-detail-attendance-icon]');

          if (attendanceIcon) {
            if (payload.attendance?.icon) {
              attendanceIcon.className = `icon-base ti ${payload.attendance.icon} me-1`;
              attendanceIcon.style.display = '';
            } else {
              attendanceIcon.className = 'icon-base ti me-1';
              attendanceIcon.style.display = 'none';
            }
          }

          const editLink = detailModal.querySelector('[data-detail-edit-link]');
          const qrLink = detailModal.querySelector('[data-detail-qr-link]');

          if (editLink) {
            editLink.setAttribute('href', payload.edit_url || '{{ route('admin.users.index') }}');
          }

          if (qrLink) {
            if (payload.ticket_view_url) {
              qrLink.setAttribute('href', payload.ticket_view_url);
              qrLink.classList.remove('is-disabled');
              qrLink.setAttribute('aria-disabled', 'false');
            } else {
              qrLink.setAttribute('href', '#');
              qrLink.classList.add('is-disabled');
              qrLink.setAttribute('aria-disabled', 'true');
            }
          }
        });
      }
    });
  </script>
@endpush
