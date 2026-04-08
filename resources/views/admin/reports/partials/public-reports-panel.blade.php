@php
  $publicReports = $publicReports ?? collect();
  $publicReportSummary = $publicReportSummary ?? [];
  $publicReportTypeOptions = $publicReportTypeOptions ?? [];
  $publicReportTypeLabel = $publicReportTypeLabel ?? fn(string $value): string => $value;
  $publicReportStatusOptions = $publicReportStatusOptions ?? [];
  $publicReportStatusLabel = $publicReportStatusLabel ?? static fn(?string $value): string => match ((string) $value) {
    'in_progress' => 'In Progress',
    'resolved' => 'Resolved',
    default => 'No Action Yet',
  };
  $publicReportIdentityTypeLabel = $publicReportIdentityTypeLabel ?? static function (?string $value): string {
    return match ((string) $value) {
      'national_id' => 'Malaysia IC (MyKad)',
      'passport' => 'Passport',
      default => '-',
    };
  };
  $publicReportIndexUrl = $publicReportIndexUrl ?? route('admin.public-reports.index');
  $publicReportPanelTitle = $publicReportPanelTitle ?? 'Public Reports';
  $publicReportPanelDescription = $publicReportPanelDescription ?? 'Review submitted cases here so the team does not rely on email only.';
  $publicReportStatusBadgeClass = static function (?string $value): string {
    return match ((string) $value) {
      'in_progress' => 'bg-label-info',
      'resolved' => 'bg-label-success',
      default => 'bg-label-secondary',
    };
  };
  $exportQuery = request()->except('page');
  $reviewReportId = old('public_report_id');
  $reviewHasErrors = filled($reviewReportId) && ($errors->has('public_report_id') || $errors->has('action_status') || $errors->has('admin_note'));

  $formatDate = static function (mixed $value): string {
    if (blank($value)) {
      return '-';
    }

    try {
      return \Carbon\CarbonImmutable::parse((string) $value)->format('d M Y');
    } catch (\Throwable) {
      return (string) $value;
    }
  };

  $formatTime = static function (mixed $value): string {
    if (blank($value)) {
      return '-';
    }

    try {
      return \Carbon\CarbonImmutable::parse((string) $value)->format('H:i');
    } catch (\Throwable) {
      return substr((string) $value, 0, 5) ?: (string) $value;
    }
  };

  $formatDateTime = static function (mixed $value): string {
    if (blank($value)) {
      return '-';
    }

    try {
      return \Carbon\CarbonImmutable::parse((string) $value)->format('d M Y, H:i');
    } catch (\Throwable) {
      return (string) $value;
    }
  };

  $formatFilterDate = static function (mixed $value): string {
    $date = trim((string) $value);

    if ($date === '') {
      return '';
    }

    foreach (['d/m/Y', 'Y-m-d'] as $format) {
      try {
        $parsed = \Carbon\CarbonImmutable::createFromFormat($format, $date);

        if ($parsed !== false) {
          return $parsed->format('Y-m-d');
        }
      } catch (\Throwable) {
        continue;
      }
    }

    return $date;
  };

  $reporterInitials = static function (?string $value): string {
    $name = trim((string) $value);

    if ($name === '') {
      return '?';
    }

    $segments = array_values(array_filter(preg_split('/\s+/', $name) ?: []));
    $initials = '';

    foreach (array_slice($segments, 0, 2) as $segment) {
      $initials .= function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($segment, 0, 1))
        : strtoupper(substr($segment, 0, 1));
    }

    if ($initials !== '') {
      return $initials;
    }

    return function_exists('mb_substr')
      ? mb_strtoupper(mb_substr($name, 0, 1))
      : strtoupper(substr($name, 0, 1));
  };
@endphp

@once
  @push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/flatpickr/flatpickr.css') }}" />
    <style>
      .report-case-trigger {
        border: 0;
        background: transparent;
        padding: 0;
        text-align: left;
      }

      .public-report-table .avatar {
        --bs-avatar-size: 2.75rem;
      }

      .public-report-table .reporter-name-trigger {
        border: 0;
        background: transparent;
        padding: 0;
        text-align: left;
      }

      .public-report-table .reporter-meta {
        min-width: 0;
      }

      .public-report-table .reporter-meta small {
        line-height: 1.35;
      }

      .public-report-filter-date {
        min-width: 9.5rem;
      }

      .public-report-filter-date.flatpickr-input[readonly] {
        background-color: var(--bs-body-bg);
      }

      .report-detail-modal .modal-dialog {
        max-width: 860px;
      }

      .report-detail-shell {
        border-radius: 1.5rem;
        border: 1px solid rgba(67, 89, 113, 0.12);
        background:
          radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.14), transparent 34%),
          linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 247, 251, 0.98));
        box-shadow: 0 1.25rem 3rem rgba(67, 89, 113, 0.12);
      }

      .report-detail-header {
        padding: 1.5rem 1.5rem 0;
      }

      .report-detail-body {
        padding: 0 1.5rem 1.5rem;
      }

      .report-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
      }

      .report-detail-item {
        border: 1px solid rgba(67, 89, 113, 0.12);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.88);
        padding: 1rem;
        min-height: 100%;
      }

      .report-detail-item--wide {
        grid-column: 1 / -1;
      }

      .report-detail-label {
        display: block;
        margin-bottom: 0.3rem;
        color: var(--bs-secondary-color);
        font-size: 0.8125rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }

      .report-detail-value {
        color: var(--bs-heading-color);
        font-weight: 600;
        word-break: break-word;
        white-space: pre-line;
      }

      .report-review-form textarea {
        min-height: 132px;
        resize: vertical;
      }

      .report-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border-radius: 999px;
        padding: 0.45rem 0.9rem;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.01em;
      }

      .report-review-panel {
        border: 1px solid rgba(67, 89, 113, 0.12);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.92);
        padding: 1.25rem;
      }

      @media (max-width: 767.98px) {
        .report-detail-grid {
          grid-template-columns: 1fr;
        }

        .report-detail-header,
        .report-detail-body {
          padding-left: 1rem;
          padding-right: 1rem;
        }
      }
    </style>
  @endpush
@endonce

@once
  @push('vendor-scripts')
    <script src="{{ asset('assets-vuexy/vendor/libs/flatpickr/flatpickr.js') }}"></script>
  @endpush
@endonce

<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <span class="text-heading d-block mb-1">Total Public Reports</span>
        <h3 class="card-title mb-1">{{ number_format((int) ($publicReportSummary['total'] ?? 0)) }}</h3>
        <small class="text-muted">All submitted reports saved in panel</small>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <span class="text-heading d-block mb-1">Incident / Security</span>
        <h3 class="card-title mb-1">{{ number_format((int) ($publicReportSummary['incident_security'] ?? 0)) }}</h3>
        <small class="text-muted">Safety and incident cases</small>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <span class="text-heading d-block mb-1">Lost Item + Locker</span>
        <h3 class="card-title mb-1">
          {{ number_format((int) (($publicReportSummary['lost_item'] ?? 0) + ($publicReportSummary['lost_locker_card'] ?? 0))) }}
        </h3>
        <small class="text-muted">Belongings and locker issues</small>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <span class="text-heading d-block mb-1">Medical Attention</span>
        <h3 class="card-title mb-1">{{ number_format((int) ($publicReportSummary['medical_attention'] ?? 0)) }}</h3>
        <small class="text-muted">Medical-related reports</small>
      </div>
    </div>
  </div>
</div>

<div class="card mb-6">
  <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-3">
    <div>
      <h5 class="mb-1">{{ $publicReportPanelTitle }}</h5>
      <small class="text-muted">{{ $publicReportPanelDescription }}</small>
    </div>
  </div>
  <div class="card-body">
    <form method="GET" action="{{ $publicReportIndexUrl }}" class="row g-4 align-items-end">
      <div class="col-md-3">
        <label class="form-label" for="q">Search</label>
        <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
          placeholder="case id, name, email, phone, identity number" />
      </div>
      <div class="col-md-2">
        <label class="form-label" for="report_type">Type of Report</label>
        <select class="form-select" id="report_type" name="report_type">
          <option value="">All report types</option>
          @foreach ($publicReportTypeOptions as $option)
            <option value="{{ $option['value'] }}" @selected(($filters['report_type'] ?? '') === $option['value'])>
              {{ $option['label'] }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label" for="action_status">Action Status</label>
        <select class="form-select" id="action_status" name="action_status">
          <option value="">All statuses</option>
          @foreach ($publicReportStatusOptions as $option)
            <option value="{{ $option['value'] }}" @selected(($filters['action_status'] ?? '') === $option['value'])>
              {{ $option['label'] }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label" for="from">Reported from</label>
        <input type="date" class="form-control public-report-filter-date" id="from" name="from"
          value="{{ $formatFilterDate($filters['from'] ?? '') }}" />
      </div>
      <div class="col-md-2">
        <label class="form-label" for="to">Reported to</label>
        <input type="date" class="form-control public-report-filter-date" id="to" name="to"
          value="{{ $formatFilterDate($filters['to'] ?? '') }}" />
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill">Apply</button>
        <a href="{{ $publicReportIndexUrl }}" class="btn btn-danger btn-icon" title="Reset Filters" aria-label="Reset Filters">
          <i class="icon-base ti tabler-rotate-clockwise-2"></i>
        </a>
      </div>
    </form>
  </div>

  <div class="table-responsive text-nowrap">
    <table class="table table-hover align-middle public-report-table">
      <thead>
        <tr>
          <th>Reporter</th>
          <th>Type</th>
          <th>Status</th>
          <th>Case ID</th>
          <th>Incident Date</th>
          <th>Reported Date</th>
          <th>Reported Time</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($publicReports as $report)
          @php
            $reportDomId = 'publicReportData-' . $report->id;
            $reportPayload = [
              'id' => (string) $report->getKey(),
              'case_id' => (string) $report->case_id,
              'report_type' => $publicReportTypeLabel((string) $report->report_type),
              'action_status' => (string) ($report->action_status ?? 'pending'),
              'action_status_label' => $publicReportStatusLabel((string) ($report->action_status ?? 'pending')),
              'action_status_class' => $publicReportStatusBadgeClass((string) ($report->action_status ?? 'pending')),
              'admin_note' => (string) ($report->admin_note ?: ''),
              'name' => (string) $report->name,
              'email' => (string) ($report->email ?: '-'),
              'phone' => (string) $report->phone,
              'identity_type' => $publicReportIdentityTypeLabel((string) ($report->identity_type ?? '')),
              'identity_number' => (string) ($report->identity_number ?: '-'),
              'incident_date' => $formatDate($report->incident_date),
              'incident_time' => $formatTime($report->incident_time),
              'reported_date' => $formatDate($report->reported_at),
              'reported_time' => $formatTime($report->reported_at),
              'reported_at' => $formatDateTime($report->reported_at),
              'staff_name' => (string) ($report->staff_name ?: '-'),
              'ip_address' => (string) ($report->ip_address ?: '-'),
              'chronology' => (string) $report->chronology,
              'update_url' => route('admin.public-reports.update', $report),
              'delete_url' => route('admin.public-reports.destroy', $report),
            ];
          @endphp
          <tr>
            <td>
              <script type="application/json" id="{{ $reportDomId }}">@json($reportPayload)</script>
              <div class="d-flex align-items-center">
                <div class="avatar me-4">
                  <span
                    class="avatar-initial rounded-circle bg-label-primary">{{ $reporterInitials($report->name) }}</span>
                </div>
                <div class="d-flex flex-column reporter-meta">
                  <button type="button" class="reporter-name-trigger text-heading fw-medium js-report-detail-trigger"
                    data-bs-toggle="modal" data-bs-target="#reportDetailModal" data-report-id="{{ $report->getKey() }}"
                    data-report-detail-source="{{ $reportDomId }}">
                    {{ $report->name }}
                  </button>
                  @if (filled($report->email))
                    <small class="text-muted">{{ $report->email }}</small>
                  @endif
                  <small class="text-muted">{{ $report->phone ?: '-' }}</small>
                </div>
              </div>
            </td>
            <td>{{ $publicReportTypeLabel((string) $report->report_type) }}</td>
            <td>
              <div class="d-flex flex-column gap-1">
                <span
                  class="badge rounded-pill report-status-chip {{ $publicReportStatusBadgeClass((string) ($report->action_status ?? 'pending')) }}">
                  {{ $publicReportStatusLabel((string) ($report->action_status ?? 'pending')) }}
                </span>
                <small
                  class="text-muted">{{ filled($report->admin_note) ? 'Admin note added' : 'No admin note yet' }}</small>
              </div>
            </td>
            <td>
              <button type="button" class="report-case-trigger fw-semibold text-primary js-report-detail-trigger"
                data-bs-toggle="modal" data-bs-target="#reportDetailModal" data-report-id="{{ $report->getKey() }}"
                data-report-detail-source="{{ $reportDomId }}">
                {{ $report->case_id }}
              </button>
            </td>
            <td>{{ $formatDate($report->incident_date) }}</td>
            <td>{{ $formatDate($report->reported_at) }}</td>
            <td>{{ $formatTime($report->reported_at) }}</td>
            <td class="text-end">
              <div class="d-inline-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill js-report-detail-trigger"
                  data-bs-toggle="modal" data-bs-target="#reportDetailModal" data-report-id="{{ $report->getKey() }}"
                  data-report-detail-source="{{ $reportDomId }}" title="View Details">
                  <i class="icon-base ti tabler-eye"></i>
                </button>

                <form method="POST" action="{{ route('admin.public-reports.destroy', $report) }}"
                  class="d-inline js-delete-public-report-form">
                  @csrf
                  @method('DELETE')
                  <button type="submit"
                    class="btn btn-sm btn-icon btn-text-danger rounded-pill js-delete-public-report-trigger"
                    title="Delete Report" data-report-case-id="{{ $report->case_id }}"
                    data-report-name="{{ $report->name }}">
                    <i class="icon-base ti tabler-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center py-6 text-muted">No public reports are available yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if (method_exists($publicReports, 'links'))
    <div class="card-body border-top">
      {{ $publicReports->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  @endif
</div>

<div class="modal fade report-detail-modal" id="reportDetailModal" tabindex="-1"
  aria-labelledby="reportDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content report-detail-shell border-0">
      <div class="report-detail-header">
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div>
            <span class="badge rounded-pill bg-label-primary px-3 py-2 mb-3">Public Report</span>
            <h4 class="mb-1" id="reportDetailModalLabel">Case Details</h4>
            <p class="text-muted mb-0">Full chronology and metadata for the selected public report.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>

      <div class="modal-body report-detail-body">
        <div class="report-detail-grid">
          <div class="report-detail-item">
            <span class="report-detail-label">Case ID</span>
            <div class="report-detail-value" data-report-case-id>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Type of Report</span>
            <div class="report-detail-value" data-report-type>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Name</span>
            <div class="report-detail-value" data-report-name>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Phone</span>
            <div class="report-detail-value" data-report-phone>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Email</span>
            <div class="report-detail-value" data-report-email>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Document Type</span>
            <div class="report-detail-value" data-report-identity-type>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Document Number</span>
            <div class="report-detail-value" data-report-identity-number>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Incident Date</span>
            <div class="report-detail-value" data-report-incident-date>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Incident Time</span>
            <div class="report-detail-value" data-report-incident-time>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Reported Date</span>
            <div class="report-detail-value" data-report-reported-date>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Reported Time</span>
            <div class="report-detail-value" data-report-reported-time>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">Staff Name</span>
            <div class="report-detail-value" data-report-staff-name>-</div>
          </div>
          <div class="report-detail-item">
            <span class="report-detail-label">IP Address</span>
            <div class="report-detail-value" data-report-ip-address>-</div>
          </div>
          <div class="report-detail-item report-detail-item--wide">
            <span class="report-detail-label">Chronology</span>
            <div class="report-detail-value" data-report-chronology>-</div>
          </div>
        </div>

        <div class="report-review-panel mt-4">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
            <div>
              <span class="report-detail-label mb-2">Admin Follow-up</span>
              <div class="fw-semibold text-heading">Update handling progress and internal notes for this report.</div>
              <small class="text-muted">This note is only visible inside the admin panel.</small>
            </div>
            <span class="badge rounded-pill report-status-chip bg-label-secondary" data-report-action-status>No Action
              Yet</span>
          </div>

          @if ($reviewHasErrors)
            <div class="alert alert-danger mb-4" role="alert">
              Please review the follow-up form below before saving this report.
            </div>
          @endif

          <form id="reportReviewForm" method="POST" action="{{ $publicReportIndexUrl }}" class="report-review-form"
            data-report-review-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="public_report_id" value="{{ $reviewReportId }}" />

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label" for="reportActionStatus">Action Status</label>
                <select class="form-select @error('action_status') is-invalid @enderror" id="reportActionStatus"
                  name="action_status">
                  @foreach ($publicReportStatusOptions as $option)
                    <option value="{{ $option['value'] }}" @selected(old('action_status') === $option['value'])>
                      {{ $option['label'] }}</option>
                  @endforeach
                </select>
                @error('action_status')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-8">
                <label class="form-label" for="reportAdminNote">Admin Note </label>
                <textarea class="form-control @error('admin_note') is-invalid @enderror" id="reportAdminNote"
                  name="admin_note"
                  placeholder="Add handling notes, follow-up context, or completion details for the team.">{{ old('admin_note') }}</textarea>
                @error('admin_note')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            @error('public_report_id')
              <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
          </form>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <button type="button" class="btn btn-label-secondary px-4" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary px-4" form="reportReviewForm">Save Follow-up</button>
        </div>
      </div>
    </div>
  </div>
</div>

@once
  @push('page-scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const initializePublicReportDatePickers = function () {
          if (typeof window.flatpickr !== 'function') {
            return;
          }

          const fromInput = document.querySelector('#from.public-report-filter-date');
          const toInput = document.querySelector('#to.public-report-filter-date');

          const applyAltInputAttributes = function (instance, label) {
            const altInput = instance?.altInput;

            if (!altInput) {
              return;
            }

            altInput.classList.add('public-report-filter-date');
            altInput.setAttribute('aria-label', label);
            altInput.setAttribute('placeholder', 'Select date');
          };

          const destroyPicker = function (input) {
            if (input?._flatpickr) {
              input._flatpickr.destroy();
            }
          };

          destroyPicker(fromInput);
          destroyPicker(toInput);

          if (fromInput) {
            window.flatpickr(fromInput, {
              altInput: true,
              altFormat: 'd M Y',
              allowInput: true,
              clickOpens: true,
              dateFormat: 'Y-m-d',
              disableMobile: true,
              maxDate: toInput?.value || null,
              onReady: function (_, __, instance) {
                applyAltInputAttributes(instance, 'Reported from');
              },
              onChange: function (selectedDates) {
                if (!toInput?._flatpickr) {
                  return;
                }

                toInput._flatpickr.set('minDate', selectedDates[0] ?? null);
              },
            });
          }

          if (toInput) {
            window.flatpickr(toInput, {
              altInput: true,
              altFormat: 'd M Y',
              allowInput: true,
              clickOpens: true,
              dateFormat: 'Y-m-d',
              disableMobile: true,
              minDate: fromInput?.value || null,
              onReady: function (_, __, instance) {
                applyAltInputAttributes(instance, 'Reported to');
              },
              onChange: function (selectedDates) {
                if (!fromInput?._flatpickr) {
                  return;
                }

                fromInput._flatpickr.set('maxDate', selectedDates[0] ?? null);
              },
            });
          }
        };

        initializePublicReportDatePickers();

        document.querySelectorAll('.js-delete-public-report-form').forEach(function (deleteForm) {
          deleteForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const trigger = deleteForm.querySelector('.js-delete-public-report-trigger');
            const caseId = trigger?.dataset.reportCaseId || 'this public report';
            const reporterName = trigger?.dataset.reportName || '';
            const summary = reporterName
              ? `${caseId} from ${reporterName} will be deleted permanently.`
              : `${caseId} will be deleted permanently.`;

            if (window.confirm(`${summary}\n\nContinue deleting this public report?`)) {
              deleteForm.submit();
            }
          });
        });

        const detailModal = document.querySelector('#reportDetailModal');

        if (!detailModal) {
          return;
        }

        const reviewForm = detailModal.querySelector('[data-report-review-form]');
        const reviewState = {
          reportId: @json(filled($reviewReportId) ? (string) $reviewReportId : ''),
          actionStatus: @json(old('action_status')),
          adminNote: @json(old('admin_note')),
        };

        const setText = function (selector, value) {
          const element = detailModal.querySelector(selector);

          if (element) {
            element.textContent = value && String(value).trim() !== '' ? value : '-';
          }
        };

        const setValue = function (selector, value) {
          const element = detailModal.querySelector(selector);

          if (element) {
            element.value = value ?? '';
          }
        };

        const setStatusChip = function (selector, label, badgeClass) {
          const element = detailModal.querySelector(selector);

          if (!element) {
            return;
          }

          element.className = 'badge rounded-pill report-status-chip ' + (badgeClass && String(badgeClass).trim() !== '' ? badgeClass : 'bg-label-secondary');
          element.textContent = label && String(label).trim() !== '' ? label : 'No Action Yet';
        };

        const readDetailPayload = function (sourceId) {
          const source = sourceId ? document.getElementById(sourceId) : null;

          if (!source) {
            return null;
          }

          try {
            return JSON.parse(source.textContent || '{}');
          } catch (error) {
            console.error('Failed to parse public report payload.', error);
            return null;
          }
        };

        detailModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const payload = readDetailPayload(trigger?.dataset.reportDetailSource);

          if (!payload) {
            return;
          }

          setText('[data-report-case-id]', payload.case_id);
          setText('[data-report-type]', payload.report_type);
          setText('[data-report-name]', payload.name);
          setText('[data-report-phone]', payload.phone);
          setText('[data-report-email]', payload.email);
          setText('[data-report-identity-type]', payload.identity_type);
          setText('[data-report-identity-number]', payload.identity_number);
          setText('[data-report-incident-date]', payload.incident_date);
          setText('[data-report-incident-time]', payload.incident_time);
          setText('[data-report-reported-date]', payload.reported_date);
          setText('[data-report-reported-time]', payload.reported_time);
          setText('[data-report-staff-name]', payload.staff_name);
          setText('[data-report-ip-address]', payload.ip_address);
          setText('[data-report-chronology]', payload.chronology);
          setStatusChip('[data-report-action-status]', payload.action_status_label, payload.action_status_class);

          if (reviewForm) {
            const shouldUseOldInput = reviewState.reportId !== '' && String(reviewState.reportId) === String(payload.id);

            reviewForm.action = payload.update_url || reviewForm.action;
            setValue('input[name="public_report_id"]', payload.id);
            setValue('select[name="action_status"]', shouldUseOldInput ? reviewState.actionStatus : (payload.action_status || 'pending'));
            setValue('textarea[name="admin_note"]', shouldUseOldInput ? (reviewState.adminNote ?? '') : (payload.admin_note || ''));
          }
        });

        if (reviewState.reportId !== '') {
          const autoOpenTrigger = document.querySelector('.js-report-detail-trigger[data-report-id="' + String(reviewState.reportId) + '"]');

          if (autoOpenTrigger) {
            const modalInstance = window.bootstrap?.Modal.getOrCreateInstance(detailModal);

            if (modalInstance) {
              modalInstance.show(autoOpenTrigger);
            } else {
              autoOpenTrigger.click();
            }
          }
        }
      });
    </script>
  @endpush
@endonce
