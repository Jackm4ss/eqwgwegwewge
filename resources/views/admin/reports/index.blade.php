@extends('admin.layouts.app')

@php
  $title = 'Reporting & Export';
@endphp

@push('vendor-styles')
  <style>
    .report-case-trigger {
      border: 0;
      background: transparent;
      padding: 0;
      text-align: left;
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

@section('content')
  @php
    $publicReports = $publicReports ?? collect();
    $publicReportSummary = $publicReportSummary ?? [];
    $publicReportTypeOptions = $publicReportTypeOptions ?? [];
    $publicReportTypeLabel = $publicReportTypeLabel ?? fn (string $value): string => $value;
    $exportQuery = request()->except('page');

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
  @endphp

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
          <h3 class="card-title mb-1">{{ number_format((int) (($publicReportSummary['lost_item'] ?? 0) + ($publicReportSummary['lost_locker_card'] ?? 0))) }}</h3>
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
        <h5 class="mb-1">Public Reports</h5>
        <small class="text-muted">Review submitted cases here so the team does not rely on email only.</small>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('report.form') }}" target="_blank" rel="noreferrer" class="btn btn-label-primary">Open Public Form</a>
        <a href="{{ route('admin.exports.download', array_merge($exportQuery, ['type' => 'public-reports', 'format' => 'csv'])) }}" class="btn btn-label-success">Export CSV</a>
      </div>
    </div>
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-4 align-items-end">
        <div class="col-md-4">
          <label class="form-label" for="q">Search</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="case id, name, email, phone, identity number" />
        </div>
        <div class="col-md-3">
          <label class="form-label" for="report_type">Type of Report</label>
          <select class="form-select" id="report_type" name="report_type">
            <option value="">All report types</option>
            @foreach ($publicReportTypeOptions as $option)
              <option value="{{ $option['value'] }}" @selected(($filters['report_type'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label" for="from">Reported from</label>
          <input type="date" class="form-control" id="from" name="from" value="{{ $filters['from'] ?? '' }}" />
        </div>
        <div class="col-md-2">
          <label class="form-label" for="to">Reported to</label>
          <input type="date" class="form-control" id="to" name="to" value="{{ $filters['to'] ?? '' }}" />
        </div>
        <div class="col-md-1 d-grid">
          <button type="submit" class="btn btn-primary">Apply</button>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Case ID</th>
            <th>Type</th>
            <th>Reporter</th>
            <th>Incident Date</th>
            <th>Incident Time</th>
            <th>Reported Date</th>
            <th>Reported Time</th>
            <th>IP Address</th>
            <th>Summary</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($publicReports as $report)
            @php
              $reportDomId = 'publicReportData-' . $report->id;
              $reportPayload = [
                'case_id' => (string) $report->case_id,
                'report_type' => $publicReportTypeLabel((string) $report->report_type),
                'name' => (string) $report->name,
                'email' => (string) ($report->email ?: '-'),
                'phone' => (string) $report->phone,
                'identity_number' => (string) ($report->identity_number ?: '-'),
                'incident_date' => $formatDate($report->incident_date),
                'incident_time' => $formatTime($report->incident_time),
                'reported_date' => $formatDate($report->reported_at),
                'reported_time' => $formatTime($report->reported_at),
                'reported_at' => $formatDateTime($report->reported_at),
                'staff_name' => (string) ($report->staff_name ?: '-'),
                'ip_address' => (string) ($report->ip_address ?: '-'),
                'chronology' => (string) $report->chronology,
              ];
            @endphp
            <tr>
              <td>
                <script type="application/json" id="{{ $reportDomId }}">@json($reportPayload)</script>
                <button
                  type="button"
                  class="report-case-trigger fw-semibold text-primary js-report-detail-trigger"
                  data-bs-toggle="modal"
                  data-bs-target="#reportDetailModal"
                  data-report-detail-source="{{ $reportDomId }}"
                >
                  {{ $report->case_id }}
                </button>
              </td>
              <td>{{ $publicReportTypeLabel((string) $report->report_type) }}</td>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-medium">{{ $report->name }}</span>
                  <small class="text-muted">{{ $report->phone }}</small>
                  @if (filled($report->email))
                    <small class="text-muted">{{ $report->email }}</small>
                  @endif
                </div>
              </td>
              <td>{{ $formatDate($report->incident_date) }}</td>
              <td>{{ $formatTime($report->incident_time) }}</td>
              <td>{{ $formatDate($report->reported_at) }}</td>
              <td>{{ $formatTime($report->reported_at) }}</td>
              <td>{{ $report->ip_address ?: '-' }}</td>
              <td style="min-width: 320px;">
                <div class="d-flex flex-column">
                  <span>{{ \Illuminate\Support\Str::limit((string) $report->chronology, 120) }}</span>
                  <small class="text-muted mt-1">
                    IC / ID: {{ $report->identity_number ?: '-' }}
                    @if (filled($report->staff_name))
                      &middot; Staff: {{ $report->staff_name }}
                    @endif
                  </small>
                  <button
                    type="button"
                    class="btn btn-sm btn-text-secondary px-0 mt-2 align-self-start js-report-detail-trigger"
                    data-bs-toggle="modal"
                    data-bs-target="#reportDetailModal"
                    data-report-detail-source="{{ $reportDomId }}"
                  >
                    <i class="icon-base ti tabler-eye me-1"></i> View full details
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-6 text-muted">No public reports are available yet.</td>
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

  <div class="row g-6">
    <div class="col-12 col-xl-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-1">Daily Report</h5>
            <small class="text-muted">Scan totals, visitors, and attendance statistics</small>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('admin.exports.download', ['type' => 'daily-report', 'format' => 'csv'] + request()->query()) }}" class="btn btn-sm btn-label-success">CSV</a>
            <a href="{{ route('admin.exports.download', ['type' => 'daily-report', 'format' => 'xlsx'] + request()->query()) }}" class="btn btn-sm btn-label-info">Excel</a>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Total Scans</th>
                <th>Attended</th>
                <th>Duplicate</th>
              </tr>
            </thead>
            <tbody>
              @forelse (data_get($reports, 'daily.attendance_statistics', []) as $day)
                <tr>
                  <td>{{ $day['scan_date'] }}</td>
                  <td>{{ number_format($day['total_scans']) }}</td>
                  <td>{{ number_format($day['successful_attendance']) }}</td>
                  <td>{{ number_format($day['duplicate_scans']) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center py-6 text-muted">No daily report data is available yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-1">Overall Report</h5>
            <small class="text-muted">Aggregate visitor statistics</small>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('admin.exports.download', ['type' => 'overall-report', 'format' => 'csv'] + request()->query()) }}" class="btn btn-sm btn-label-success">CSV</a>
            <a href="{{ route('admin.exports.download', ['type' => 'overall-report', 'format' => 'xlsx'] + request()->query()) }}" class="btn btn-sm btn-label-info">Excel</a>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Unique Visitors</th>
              </tr>
            </thead>
            <tbody>
              @forelse (data_get($reports, 'overall.visitor_statistics', []) as $visit)
                <tr>
                  <td>{{ $visit['scan_date'] }}</td>
                  <td>{{ number_format($visit['unique_visitors']) }}</td>
                </tr>
              @empty
                <tr><td colspan="2" class="text-center py-6 text-muted">No visitor statistics are available yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade report-detail-modal" id="reportDetailModal" tabindex="-1" aria-labelledby="reportDetailModalLabel" aria-hidden="true">
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
              <span class="report-detail-label">IC / ID</span>
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

          <div class="d-flex justify-content-end gap-2 mt-4">
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
      const detailModal = document.querySelector('#reportDetailModal');

      if (!detailModal) {
        return;
      }

      const setText = function (selector, value) {
        const element = detailModal.querySelector(selector);

        if (element) {
          element.textContent = value && String(value).trim() !== '' ? value : '-';
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
        setText('[data-report-identity-number]', payload.identity_number);
        setText('[data-report-incident-date]', payload.incident_date);
        setText('[data-report-incident-time]', payload.incident_time);
        setText('[data-report-reported-date]', payload.reported_date);
        setText('[data-report-reported-time]', payload.reported_time);
        setText('[data-report-staff-name]', payload.staff_name);
        setText('[data-report-ip-address]', payload.ip_address);
        setText('[data-report-chronology]', payload.chronology);
      });
    });
  </script>
@endpush
