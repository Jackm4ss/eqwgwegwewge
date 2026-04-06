@extends('admin.layouts.app')

@php
  $title = 'QR Management';
@endphp

@push('vendor-styles')
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/fonts/flag-icons.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/select2/select2.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/sweetalert2/sweetalert2.css') }}" />
  <style>
    .qr-management-hero {
      border: 1px solid rgba(67, 89, 113, 0.14);
      border-radius: 1.5rem;
      background:
        radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.18), transparent 34%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 247, 251, 0.98));
      box-shadow: 0 1.25rem 3rem rgba(67, 89, 113, 0.08);
    }

    .qr-management-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.35rem 0.75rem;
      border-radius: 999px;
      font-size: 0.8125rem;
      font-weight: 600;
      background: rgba(var(--bs-primary-rgb), 0.12);
      color: var(--bs-primary);
    }

    .qr-management-card {
      border: 1px solid rgba(67, 89, 113, 0.14);
      border-radius: 1.25rem;
      background: rgba(255, 255, 255, 0.98);
      box-shadow: 0 1rem 2.5rem rgba(67, 89, 113, 0.06);
    }

    .qr-management-mode {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.75rem;
    }

    .qr-management-mode label {
      cursor: pointer;
    }

    .qr-management-mode input {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .qr-management-mode-card {
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
      padding: 0.9rem 1rem;
      border: 1px solid rgba(67, 89, 113, 0.14);
      border-radius: 1rem;
      background: rgba(245, 247, 250, 0.75);
      transition: all 0.2s ease;
      min-height: 100%;
    }

    .qr-management-mode input:checked+.qr-management-mode-card {
      border-color: rgba(var(--bs-primary-rgb), 0.45);
      background: rgba(var(--bs-primary-rgb), 0.08);
      box-shadow: 0 0.85rem 1.8rem rgba(var(--bs-primary-rgb), 0.12);
    }

    .qr-management-mode-title {
      font-weight: 700;
      color: var(--bs-heading-color);
    }

    .qr-management-mode-copy {
      font-size: 0.8125rem;
      color: var(--bs-secondary-color);
      margin: 0;
    }

    .qr-management-fields [data-search-panel] {
      display: none;
    }

    .qr-management-fields [data-search-panel].is-active {
      display: block;
    }

    .qr-management-summary {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.9rem;
    }

    .qr-management-item {
      border: 1px solid rgba(67, 89, 113, 0.12);
      border-radius: 1rem;
      padding: 0.9rem 1rem;
      background: rgba(248, 249, 252, 0.88);
      min-height: 100%;
    }

    .qr-management-item--wide {
      grid-column: 1 / -1;
    }

    .qr-management-label {
      display: block;
      margin-bottom: 0.3rem;
      color: var(--bs-secondary-color);
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .qr-management-value {
      color: var(--bs-heading-color);
      font-weight: 600;
      word-break: break-word;
    }

    .qr-management-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .qr-management-note {
      border-left: 4px solid rgba(var(--bs-warning-rgb), 0.7);
      background: rgba(var(--bs-warning-rgb), 0.1);
      border-radius: 0.9rem;
      padding: 0.95rem 1rem;
      color: var(--bs-heading-color);
    }

    .qr-management-result-shell {
      display: grid;
      gap: 1rem;
    }

    .event-progress-cell {
      width: 100%;
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

    .qr-management-flag {
      width: 1.15rem;
      height: 0.85rem;
      border-radius: 0.2rem;
      flex: 0 0 auto;
      box-shadow: inset 0 0 0 1px rgba(67, 89, 113, 0.14);
    }

    .qr-management-page .select2-container {
      width: 100% !important;
    }

    .qr-management-page .select2-container .select2-selection--single {
      min-height: calc(2.75rem + 2px);
      border-color: var(--bs-border-color);
      border-radius: var(--bs-border-radius);
      display: flex;
      align-items: center;
      box-shadow: none;
    }

    .qr-management-page .select2-container .select2-selection__rendered {
      width: 100%;
      padding-left: 0.875rem;
      padding-right: 2.5rem;
      line-height: 1.2;
      color: var(--bs-body-color);
    }

    .qr-management-page .select2-container .select2-selection__arrow {
      height: 100%;
      right: 0.5rem;
    }

    .qr-management-page .select2-dropdown {
      border-color: rgba(67, 89, 113, 0.14);
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 1rem 2rem rgba(67, 89, 113, 0.14);
    }

    .qr-management-page .select2-search--dropdown {
      padding: 0.75rem;
      border-bottom: 1px solid rgba(67, 89, 113, 0.12);
    }

    .qr-management-page .select2-search__field {
      border-radius: 0.75rem !important;
      border-color: rgba(67, 89, 113, 0.16) !important;
      min-height: 2.5rem;
      padding: 0.65rem 0.85rem !important;
    }

    .qr-management-page .select2-results__group {
      padding: 0.6rem 0.9rem;
      font-size: 0.72rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--bs-secondary-color);
      border-top: 1px solid rgba(67, 89, 113, 0.08);
      background: rgba(245, 247, 250, 0.92);
    }

    .qr-management-page .select2-results__option--group:first-child .select2-results__group {
      border-top: 0;
    }

    .qr-management-page .select2-results__option {
      padding: 0.7rem 0.9rem;
    }

    .qr-management-select-option {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      min-width: 0;
    }

    .qr-management-select-text {
      min-width: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      width: 100%;
    }

    .qr-management-select-primary {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .qr-management-select-secondary {
      color: var(--bs-secondary-color);
      white-space: nowrap;
      font-weight: 500;
    }

    @media (max-width: 991.98px) {

      .qr-management-mode,
      .qr-management-summary {
        grid-template-columns: 1fr;
      }
    }
  </style>
@endpush

@push('vendor-scripts')
  <script src="{{ asset('assets-vuexy/vendor/libs/select2/select2.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endpush

@section('content')
  @php
    $formatDateTime = static function (?string $value): string {
      if (!filled($value)) {
        return '-';
      }

      try {
        return \Carbon\CarbonImmutable::parse($value)
          ->timezone((string) config('admin.event.timezone', config('app.timezone', 'UTC')))
          ->format('d M Y, h:i A');
      } catch (\Throwable) {
        return (string) $value;
      }
    };

    $identityLabel = static function (?string $identityType): string {
      return match (strtolower(trim((string) $identityType))) {
        'national_id' => 'Malaysia IC (MyKad)',
        'passport' => 'Passport',
        default => '-',
      };
    };

    $attendanceLabel = static function (?string $attendanceStatus): string {
      return match (strtolower(trim((string) $attendanceStatus))) {
        'checked_in' => 'Checked In',
        'not_checked_in' => 'Not Checked In',
        default => filled($attendanceStatus) ? ucwords(str_replace('_', ' ', (string) $attendanceStatus)) : '-',
      };
    };

    $countryFlagClass = static function (?string $countryCode): string {
      $countryCode = strtolower(trim((string) $countryCode));

      if (!preg_match('/^[a-z]{2}$/', $countryCode)) {
        return 'xx';
      }

      return $countryCode;
    };

    $formatEntryCodeDisplay = static function (?string $entryCode): string {
      $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $entryCode) ?? '');

      if ($normalized === '') {
        return '';
      }

      if (strlen($normalized) <= 4) {
        return $normalized;
      }

      return substr($normalized, 0, 4) . '-' . substr($normalized, 4, 4);
    };

    $participant = is_array($lookupResult['participant'] ?? null) ? $lookupResult['participant'] : null;
    $ticket = is_array($lookupResult['ticket'] ?? null) ? $lookupResult['ticket'] : null;
    $selectedPhoneCountryCode = old('phone_country_code', $lookupInput['phone_country_code']);
    $selectedPhoneCountryCodeResolved = false;
    $eventStartDate = \Carbon\CarbonImmutable::parse(
      (string) config('admin.event.start_date', '2026-04-09'),
      (string) config('admin.event.timezone', config('app.timezone', 'UTC'))
    )->startOfDay();
    $eventEndDate = \Carbon\CarbonImmutable::parse(
      (string) config('admin.event.end_date', '2026-04-19'),
      (string) config('admin.event.timezone', config('app.timezone', 'UTC'))
    )->startOfDay();

    if ($eventEndDate->lt($eventStartDate)) {
      [$eventStartDate, $eventEndDate] = [$eventEndDate, $eventStartDate];
    }

    $defaultAttendanceTotalDays = max(1, $eventStartDate->diffInDays($eventEndDate) + 1);
    $attendanceDaysCount = max(0, (int) ($participant['attendance_days_count'] ?? 0));
    $attendanceTotalDays = max(1, (int) ($participant['attendance_total_days'] ?? $defaultAttendanceTotalDays));
    $attendanceProgressPercent = $participant['attendance_progress_percent'] ?? null;

    if ($attendanceProgressPercent === null && $attendanceTotalDays > 0) {
      $attendanceProgressPercent = (int) round(($attendanceDaysCount / $attendanceTotalDays) * 100);
    }

    $attendanceProgressPercent = max(0, min(100, (int) ($attendanceProgressPercent ?? 0)));
    $entryCodeDisplay = trim((string) ($ticket['entry_code_display'] ?? ''));

    if ($entryCodeDisplay === '') {
      $entryCodeDisplay = $formatEntryCodeDisplay($ticket['entry_code'] ?? null);
    }
  @endphp

  <div class="qr-management-page row g-4">
    <div class="col-12">
      <div class="qr-management-hero p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row gap-4 justify-content-between align-items-lg-center">
          <div class="pe-lg-4">
            <span class="qr-management-chip mb-3">
              Event-day participant support
            </span>
            <h4 class="mb-2">QR Management</h4>
            <p class="text-body mb-0">
              Lightweight lookup for operational support only. Use this page to find one participant fast, then
              reset attendance or regenerate a fresh QR when something goes wrong at the venue.
            </p>
          </div>
          <!-- <div class="qr-management-note">
                  <strong>Fast lane for staff:</strong>
                  Search by the same identifiers used on registration and Forgot QR. This page does not load the full user
                  directory, so it stays quick during event-day traffic.
                </div> -->
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-5">
      <div class="qr-management-card p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
          <div>
            <h5 class="mb-1">Participant Lookup</h5>
            <p class="text-body-secondary mb-0">Search by email, phone, passport, or Malaysia IC.</p>
          </div>
        </div>

        <form method="GET" action="{{ route('admin.qr-management.index') }}" id="qrManagementLookupForm">
          <div class="qr-management-mode mb-4">
            <label>
              <input type="radio" name="search_type" value="email" {{ $searchType === 'email' ? 'checked' : '' }} />
              <span class="qr-management-mode-card">
                <span class="qr-management-mode-title">Email</span>
                <span class="qr-management-mode-copy">Fastest when the participant knows the registered email.</span>
              </span>
            </label>
            <label>
              <input type="radio" name="search_type" value="phone" {{ $searchType === 'phone' ? 'checked' : '' }} />
              <span class="qr-management-mode-card">
                <span class="qr-management-mode-title">Phone Number</span>
                <span class="qr-management-mode-copy">Search by country code and the registered mobile number.</span>
              </span>
            </label>
            <label>
              <input type="radio" name="search_type" value="passport" {{ $searchType === 'passport' ? 'checked' : '' }} />
              <span class="qr-management-mode-card">
                <span class="qr-management-mode-title">Passport</span>
                <span class="qr-management-mode-copy">Find international participants by country and passport
                  number.</span>
              </span>
            </label>
            <label>
              <input type="radio" name="search_type" value="ic" {{ $searchType === 'ic' ? 'checked' : '' }} />
              <span class="qr-management-mode-card">
                <span class="qr-management-mode-title">Malaysia IC</span>
                <span class="qr-management-mode-copy">Use the registered Malaysia IC / MyKad number.</span>
              </span>
            </label>
          </div>

          <div class="qr-management-fields">
            <div data-search-panel="email" class="{{ $searchType === 'email' ? 'is-active' : '' }}">
              <div class="mb-3">
                <label for="qrLookupEmail" class="form-label">Email Address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="qrLookupEmail"
                  name="email" value="{{ old('email', $lookupInput['email']) }}" placeholder="participant@example.com" />
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div data-search-panel="phone" class="{{ $searchType === 'phone' ? 'is-active' : '' }}">
              <div class="row g-3">
                <div class="col-md-5">
                  <label for="qrLookupPhoneCountryCode" class="form-label">Country Code</label>
                  <select class="form-select @error('phone_country_code') is-invalid @enderror"
                    id="qrLookupPhoneCountryCode" name="phone_country_code" data-qr-select-kind="phone"
                    data-placeholder="Search country or dial code...">
                    @foreach ($phoneOptionGroups as $group => $groupOptions)
                      <optgroup label="{{ $group === 'priority' ? 'Priority Countries' : 'All Other Countries' }}">
                        @foreach ($groupOptions as $option)
                          @php
                            $isSelectedPhoneOption = !$selectedPhoneCountryCodeResolved && $selectedPhoneCountryCode === $option['dial_code'];
                            $selectedPhoneCountryCodeResolved = $selectedPhoneCountryCodeResolved || $isSelectedPhoneOption;
                          @endphp
                          <option value="{{ $option['dial_code'] }}" data-flag="{{ $option['flag'] }}"
                            data-label="{{ $option['name'] }}" data-secondary="{{ $option['dial_code'] }}"
                            data-search="{{ implode(' ', array_filter([$option['name'], $option['dial_code'], $option['country_code'], $option['alpha3']])) }}"
                            @selected($isSelectedPhoneOption)>
                            {{ $option['name'] }} {{ $option['dial_code'] }}
                          </option>
                        @endforeach
                      </optgroup>
                    @endforeach
                  </select>

                  @error('phone_country_code')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-7">
                  <label for="qrLookupPhoneNumber" class="form-label">Phone Number</label>
                  <input type="text" class="form-control @error('phone_national_number') is-invalid @enderror"
                    id="qrLookupPhoneNumber" name="phone_national_number"
                    value="{{ old('phone_national_number', $lookupInput['phone_national_number']) }}"
                    placeholder="8123456789" inputmode="numeric" />
                  @error('phone_national_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <div data-search-panel="passport" class="{{ $searchType === 'passport' ? 'is-active' : '' }}">
              <div class="row g-3">
                <div class="col-md-5">
                  <label for="qrLookupCountry" class="form-label">Country</label>
                  <select class="form-select @error('country') is-invalid @enderror" id="qrLookupCountry" name="country"
                    data-qr-select-kind="country" data-placeholder="Search nationality...">
                    @foreach ($countryOptionGroups as $group => $groupCountries)
                      <optgroup label="{{ $group === 'priority' ? 'Priority Countries' : 'All Other Countries' }}">
                        @foreach ($groupCountries as $country)
                          <option value="{{ $country['code'] }}" data-flag="{{ $country['flag'] }}"
                            data-label="{{ $country['name'] }}"
                            data-search="{{ implode(' ', array_filter([$country['name'], $country['code'], $country['alpha3']])) }}"
                            @selected(old('country', $lookupInput['country']) === $country['code'])>
                            {{ $country['name'] }}
                          </option>
                        @endforeach
                      </optgroup>
                    @endforeach
                  </select>

                  @error('country')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-7">
                  <label for="qrLookupPassport" class="form-label">Passport Number</label>
                  <input type="text" class="form-control @error('identity_number') is-invalid @enderror"
                    id="qrLookupPassport" name="identity_number"
                    value="{{ old('identity_number', $lookupInput['identity_number']) }}" placeholder="A1234567" />
                  @error('identity_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <div data-search-panel="ic" class="{{ $searchType === 'ic' ? 'is-active' : '' }}">
              <div class="mb-3">
                <label for="qrLookupIc" class="form-label">Malaysia IC (MyKad) Number</label>
                <input type="text" class="form-control @error('identity_number') is-invalid @enderror" id="qrLookupIc"
                  name="identity_number" value="{{ old('identity_number', $lookupInput['identity_number']) }}"
                  placeholder="010203100011" />
                @error('identity_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <input type="hidden" name="country" value="MY" />
            </div>
          </div>

          @error('search_type')
            <div class="text-danger small mb-3">{{ $message }}</div>
          @enderror

          <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base ti tabler-search me-1"></i>
              Search Participant
            </button>
            <a href="{{ route('admin.qr-management.index') }}" class="btn btn-label-secondary">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <div class="col-12 col-xl-7">
      <div class="qr-management-card p-4 h-100">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
          <div>
            <h5 class="mb-1">Support Result</h5>
            <p class="text-body-secondary mb-0">One participant result with direct operational actions.</p>
          </div>
        </div>

        @if (!$lookupAttempted)
          <div class="alert alert-info mb-0">
            Start with a search on the left. This page is intentionally focused on fast QR support, not the full user list.
          </div>
        @elseif (($lookupResult['found'] ?? false) !== true)
          <div class="alert alert-warning mb-0">
            {{ $lookupResult['message'] ?? 'Participant data was not found.' }}
          </div>
        @elseif ($participant && $ticket)
          <div class="qr-management-result-shell">
            <div class="qr-management-summary">
              <div class="qr-management-item">
                <span class="qr-management-label">Participant Name</span>
                <div class="qr-management-value">{{ $participant['full_name'] ?: '-' }}</div>
              </div>
              <div class="qr-management-item">
                <span class="qr-management-label">Email</span>
                <div class="qr-management-value">{{ $participant['email'] ?: '-' }}</div>
              </div>
              <div class="qr-management-item">
                <span class="qr-management-label">Phone Number</span>
                <div class="qr-management-value">
                  {{ trim(($participant['phone_country_code'] ?? '') . ' ' . ($participant['phone_national_number'] ?? '')) ?: '-' }}
                </div>
              </div>
              <div class="qr-management-item">
                <span class="qr-management-label">Country</span>
                <div class="qr-management-value d-inline-flex align-items-center gap-2">
                  <span
                    class="fi fis fi-{{ $countryFlagClass($participant['country'] ?? null) }} qr-management-flag"></span>
                  <span>{{ $participant['country_label'] ?: ($participant['country'] ?: '-') }}</span>
                </div>
              </div>
              <div class="qr-management-item">
                <span class="qr-management-label">Document Type</span>
                <div class="qr-management-value">{{ $identityLabel($participant['identity_type'] ?? null) }}</div>
              </div>
              <div class="qr-management-item">
                <span class="qr-management-label">Document Number</span>
                <div class="qr-management-value">{{ $participant['identity_number'] ?: '-' }}</div>
              </div>
              <div class="qr-management-item qr-management-item--wide">
                <span class="qr-management-label">Attendance</span>
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
                  <div class="text-body-secondary small mt-2">
                    Status: {{ $attendanceLabel($ticket['attendance_status'] ?? null) }}
                  </div>
                </div>
              </div>
              <div class="qr-management-item">
                <span class="qr-management-label">Entry Code</span>
                <div class="qr-management-value">{{ $entryCodeDisplay !== '' ? $entryCodeDisplay : '-' }}</div>
              </div>
              <div class="qr-management-item">
                <span class="qr-management-label">QR Created</span>
                <div class="qr-management-value">{{ $formatDateTime($ticket['created_at'] ?? null) }}</div>
              </div>
              <div class="qr-management-item">
                <span class="qr-management-label">QR Regenerated</span>
                <div class="qr-management-value">
                  {{ $formatDateTime($ticket['regenerated_at'] ?? ($ticket['updated_at'] ?? null)) }}
                </div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              @if (filled($lookupResult['ticket_url'] ?? null))
                <a href="{{ $lookupResult['ticket_url'] }}" target="_blank" rel="noopener" class="btn btn-label-primary">
                  <i class="icon-base ti tabler-eye me-1"></i>
                  Open Ticket
                </a>
              @endif
            </div>

            <div class="qr-management-actions">
              <form method="POST" action="{{ route('admin.users.qr.reset', $participant['user_id']) }}"
                data-qr-confirm-action="reset-attendance"
                data-participant-name="{{ $participant['full_name'] ?: 'this participant' }}">
                @csrf
                <button type="submit" class="btn btn-warning">
                  <i class="icon-base ti tabler-rotate-clockwise-2 me-1"></i>
                  Reset Attendance
                </button>
              </form>
              <form method="POST" action="{{ route('admin.users.qr.regenerate', $participant['user_id']) }}"
                data-qr-confirm-action="regenerate-qr"
                data-participant-name="{{ $participant['full_name'] ?: 'this participant' }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                  <i class="icon-base ti tabler-qrcode me-1"></i>
                  Regenerate QR
                </button>
              </form>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
@endsection

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const root = document.querySelector('.qr-management-page');
      if (!root) {
        return;
      }

      const escapeHtml = function (value) {
        return String(value ?? '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      };

      const renderSelectMarkup = function (option, mode) {
        if (!option.id) {
          return escapeHtml(option.text);
        }

        const element = option.element;
        const flag = element?.dataset.flag || 'xx';
        const label = element?.dataset.label || option.text || '';
        const secondary = element?.dataset.secondary || '';

        if (mode === 'phone-selection') {
          return `
                  <span class="qr-management-select-option">
                    <span class="fi fis fi-${escapeHtml(flag)} qr-management-flag"></span>
                    <span class="qr-management-select-secondary">${escapeHtml(secondary || label)}</span>
                  </span>
                `;
        }

        return `
                <span class="qr-management-select-option">
                  <span class="fi fis fi-${escapeHtml(flag)} qr-management-flag"></span>
                  <span class="qr-management-select-text">
                    <span class="qr-management-select-primary">${escapeHtml(label)}</span>
                    ${secondary ? `<span class="qr-management-select-secondary">${escapeHtml(secondary)}</span>` : ''}
                  </span>
                </span>
              `;
      };

      const matcher = function (params, data) {
        const term = window.jQuery.trim(params.term || '').toLowerCase();

        if (term === '') {
          return data;
        }

        if (!data.element) {
          return data;
        }

        const haystack = String(data.element.dataset.search || data.text || '').toLowerCase();

        return haystack.includes(term) ? data : null;
      };

      const initSelect2 = function (element) {
        if (!element || typeof window.jQuery === 'undefined' || !window.jQuery.fn.select2) {
          return;
        }

        const $select = window.jQuery(element);

        if (!$select.parent().hasClass('position-relative')) {
          $select.wrap('<div class="position-relative"></div>');
        }

        if ($select.data('select2')) {
          $select.trigger('change.select2');
          return;
        }

        const kind = element.dataset.qrSelectKind || 'country';
        const isPhone = kind === 'phone';

        $select.select2({
          dropdownParent: $select.parent(),
          width: '100%',
          matcher,
          minimumResultsForSearch: 0,
          templateResult: function (option) {
            return renderSelectMarkup(option, isPhone ? 'phone-option' : 'country-option');
          },
          templateSelection: function (option) {
            return renderSelectMarkup(option, isPhone ? 'phone-selection' : 'country-selection');
          },
          escapeMarkup: function (markup) {
            return markup;
          },
        });
      };

      const radios = Array.from(root.querySelectorAll('input[name="search_type"]'));
      const panels = Array.from(root.querySelectorAll('[data-search-panel]'));

      const syncPanels = function () {
        const activeRadio = radios.find((radio) => radio.checked);
        const activeType = activeRadio ? activeRadio.value : 'email';

        panels.forEach(function (panel) {
          const isActive = panel.getAttribute('data-search-panel') === activeType;
          panel.classList.toggle('is-active', isActive);

          Array.from(panel.querySelectorAll('input, select, textarea')).forEach(function (field) {
            field.disabled = !isActive;

            if (typeof window.jQuery !== 'undefined' && field.tagName === 'SELECT') {
              window.jQuery(field).prop('disabled', !isActive).trigger('change.select2');
            }
          });
        });
      };

      Array.from(root.querySelectorAll('[data-qr-select-kind]')).forEach(initSelect2);
      syncPanels();
      radios.forEach(function (radio) {
        radio.addEventListener('change', syncPanels);
      });

      const confirmConfigs = {
        'reset-attendance': {
          title: 'Reset attendance for this participant?',
          icon: 'warning',
          confirmButtonText: 'Yes, reset attendance',
          cancelButtonText: 'No, keep current status',
          confirmButtonClass: 'btn btn-warning me-2',
          buildHtml: function (participantName) {
            return `
              <p class="mb-2 text-start">You are about to clear the current attendance status for <strong>${participantName}</strong>.</p>
              <p class="mb-0 text-start text-muted">Use this only when the participant was checked in by mistake or needs a fresh check-in on event day.</p>
            `;
          },
          fallbackMessage: function (participantName) {
            return `You are about to reset attendance for ${participantName}. Continue?`;
          }
        },
        'regenerate-qr': {
          title: 'Generate a new QR code?',
          icon: 'warning',
          confirmButtonText: 'Yes, regenerate QR',
          cancelButtonText: 'No, keep current QR',
          confirmButtonClass: 'btn btn-primary me-2',
          buildHtml: function (participantName) {
            return `
              <p class="mb-2 text-start">You are about to generate a new QR code for <strong>${participantName}</strong>.</p>
              <p class="mb-0 text-start text-muted">Share the new QR only after confirmation. The participant should stop using the previous code.</p>
            `;
          },
          fallbackMessage: function (participantName) {
            return `You are about to generate a new QR code for ${participantName}. Continue?`;
          }
        }
      };

      Array.from(root.querySelectorAll('form[data-qr-confirm-action]')).forEach(function (form) {
        form.addEventListener('submit', async function (event) {
          if (form.dataset.confirmed === 'true') {
            return;
          }

          const action = form.dataset.qrConfirmAction || '';
          const config = confirmConfigs[action];

          if (!config) {
            return;
          }

          const participantName = form.dataset.participantName || 'this participant';
          const safeParticipantName = escapeHtml(participantName);
          const submitButton = form.querySelector('button[type="submit"]');

          event.preventDefault();

          let shouldContinue = false;

          if (window.Swal?.fire) {
            const result = await window.Swal.fire({
              title: config.title,
              html: config.buildHtml(safeParticipantName),
              icon: config.icon,
              showCancelButton: true,
              confirmButtonText: config.confirmButtonText,
              cancelButtonText: config.cancelButtonText,
              reverseButtons: true,
              focusCancel: true,
              buttonsStyling: false,
              customClass: {
                confirmButton: config.confirmButtonClass,
                cancelButton: 'btn btn-label-secondary'
              }
            });

            shouldContinue = Boolean(result.isConfirmed);
          } else {
            shouldContinue = window.confirm(config.fallbackMessage(participantName));
          }

          if (!shouldContinue) {
            return;
          }

          form.dataset.confirmed = 'true';

          if (submitButton) {
            submitButton.disabled = true;
          }

          form.submit();
        });
      });
    });
  </script>
@endpush
