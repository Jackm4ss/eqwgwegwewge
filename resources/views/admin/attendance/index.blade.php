@extends('admin.layouts.app')

@php
  $title = 'Attendance Monitoring';
@endphp

@push('vendor-styles')
  <style>
    .attendance-hero {
      position: relative;
      overflow: hidden;
      border: 0;
      border-radius: 1.75rem;
      background:
        radial-gradient(circle at top right, rgba(255, 255, 255, 0.22), transparent 30%),
        linear-gradient(135deg, #0f5f8f 0%, #0f88b8 58%, #2bb4d6 100%);
      color: #fff;
      box-shadow: 0 1.4rem 3rem rgba(15, 95, 143, 0.22);
    }

    .attendance-hero::after {
      content: '';
      position: absolute;
      inset: auto -6% -36% auto;
      width: 18rem;
      height: 18rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
    }

    .attendance-summary-card {
      border: 1px solid rgba(67, 89, 113, 0.12);
      border-radius: 1.25rem;
      box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.05);
    }

    .attendance-summary-card .avatar {
      --bs-avatar-size: 3rem;
    }

    .attendance-legend-chip {
      display: inline-flex;
      align-items: flex-start;
      gap: 0.75rem;
      width: 100%;
      padding: 1rem 1rem 0.95rem;
      border-radius: 1rem;
      border: 1px solid rgba(67, 89, 113, 0.1);
      background: rgba(248, 250, 252, 0.92);
    }

    .attendance-legend-dot {
      width: 0.75rem;
      height: 0.75rem;
      border-radius: 999px;
      margin-top: 0.3rem;
      flex: 0 0 auto;
    }

    .attendance-legend-dot.is-success {
      background: #28c76f;
    }

    .attendance-legend-dot.is-warning {
      background: #ff9f43;
    }

    .attendance-legend-dot.is-danger {
      background: #ea5455;
    }

    .attendance-filter-card,
    .attendance-data-card {
      border: 1px solid rgba(67, 89, 113, 0.12);
      border-radius: 1.25rem;
      box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.04);
    }

    .attendance-status-note {
      display: block;
      margin-top: 0.35rem;
      font-size: 0.75rem;
      color: #6b7280;
      line-height: 1.45;
    }

    .attendance-table td {
      vertical-align: middle;
    }

    .attendance-soft-block {
      border-radius: 1rem;
      background: rgba(67, 89, 113, 0.04);
      padding: 0.85rem 1rem;
    }
  </style>
@endpush

@section('content')
  @php
    $history = $attendance['history'];
    $dailyAttendance = $attendance['daily_attendance'] ?? [];
    $scannerActivity = $attendance['scanner_activity'] ?? [];
    $hasFilters = filled($filters['q'] ?? null) || filled($filters['from'] ?? null) || filled($filters['to'] ?? null);
    $latestLog = collect(method_exists($history, 'items') ? $history->items() : [])->first();

    $successfulAttendance = collect($dailyAttendance)->sum(fn (array $day): int => (int) ($day['successful_attendance'] ?? 0));
    $duplicateScans = collect($dailyAttendance)->sum(fn (array $day): int => (int) ($day['duplicate_scans'] ?? 0));
    $invalidScans = collect($dailyAttendance)->sum(fn (array $day): int => (int) ($day['invalid_scans'] ?? 0));
    $totalScans = method_exists($history, 'total')
      ? (int) $history->total()
      : collect(method_exists($history, 'items') ? $history->items() : [])->count();
    $activeScannerCount = count($scannerActivity);

    $formatDateTime = static function (mixed $value): string {
      if (blank($value)) {
        return '-';
      }

      try {
        return \Carbon\CarbonImmutable::parse((string) $value)
          ->setTimezone(config('app.timezone'))
          ->format('d M Y, h:i A');
      } catch (\Throwable) {
        return (string) $value;
      }
    };

    $formatDate = static function (mixed $value): string {
      if (blank($value)) {
        return '-';
      }

      try {
        return \Carbon\CarbonImmutable::parse((string) $value)
          ->setTimezone(config('app.timezone'))
          ->format('d M Y');
      } catch (\Throwable) {
        return (string) $value;
      }
    };

    $statusMeta = static function (mixed $value): array {
      return match (strtolower(trim((string) $value))) {
        'success' => [
          'label' => 'Checked In',
          'class' => 'bg-label-success',
          'note' => 'The QR is valid and attendance was recorded successfully.',
        ],
        'duplicate' => [
          'label' => 'Already Scanned',
          'class' => 'bg-label-warning',
          'note' => 'This ticket was already used, so it is not counted twice.',
        ],
        default => [
          'label' => 'Needs Review',
          'class' => 'bg-label-danger',
          'note' => 'The scan failed or the QR needs a manual recheck.',
        ],
      };
    };

    $periodSummary = match (true) {
      filled($filters['from'] ?? null) && filled($filters['to'] ?? null) => $formatDate($filters['from']).' - '.$formatDate($filters['to']),
      filled($filters['from'] ?? null) => 'Since '.$formatDate($filters['from']),
      filled($filters['to'] ?? null) => 'Until '.$formatDate($filters['to']),
      default => 'All dates',
    };
  @endphp

  <div class="card attendance-filter-card mb-6">
    <div class="card-body p-4">
      <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
          <h5 class="mb-1">Find the data you need</h5>
          <p class="text-muted mb-0">Use a simple search so admins do not need to review the full scan history.</p>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('admin.exports.download', ['type' => 'attendance', 'format' => 'csv'] + request()->query()) }}" class="btn btn-label-success">
            Download CSV
          </a>
          <a href="{{ route('admin.exports.download', ['type' => 'attendance', 'format' => 'xlsx'] + request()->query()) }}" class="btn btn-label-info">
            Download Excel
          </a>
        </div>
      </div>

      <form method="GET" action="{{ route('admin.attendance.index') }}" class="row g-4 align-items-end">
        <div class="col-md-5">
          <label class="form-label" for="q">Search participant, ticket code, or scan post name</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
            placeholder="example: ticket code, user ID, Gate A" />
        </div>
        <div class="col-md-3">
          <label class="form-label" for="from">From date</label>
          <input type="date" class="form-control" id="from" name="from" value="{{ $filters['from'] ?? '' }}" />
        </div>
        <div class="col-md-3">
          <label class="form-label" for="to">To date</label>
          <input type="date" class="form-control" id="to" name="to" value="{{ $filters['to'] ?? '' }}" />
        </div>
        <div class="col-md-1 d-grid">
          <button type="submit" class="btn btn-primary">Apply</button>
        </div>
      </form>

      @if ($hasFilters)
        <div class="mt-3">
          <a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-label-secondary">Reset filters</a>
        </div>
      @endif
    </div>
  </div>

  <div class="row g-4 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card attendance-summary-card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">
              <span class="avatar-initial rounded-circle bg-label-success"><i class="icon-base ti tabler-user-check"></i></span>
            </div>
            <div>
              <div class="text-muted small mb-1">Successfully checked in</div>
              <h3 class="mb-0">{{ number_format($successfulAttendance) }}</h3>
            </div>
          </div>
          <div class="small text-muted mt-3">These are the participants who were checked in successfully.</div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card attendance-summary-card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">
              <span class="avatar-initial rounded-circle bg-label-warning"><i class="icon-base ti tabler-repeat"></i></span>
            </div>
            <div>
              <div class="text-muted small mb-1">Repeat scans</div>
              <h3 class="mb-0">{{ number_format($duplicateScans) }}</h3>
            </div>
          </div>
          <div class="small text-muted mt-3">Tickets scanned again after a previous successful check-in.</div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card attendance-summary-card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">
              <span class="avatar-initial rounded-circle bg-label-danger"><i class="icon-base ti tabler-alert-triangle"></i></span>
            </div>
            <div>
              <div class="text-muted small mb-1">Needs review</div>
              <h3 class="mb-0">{{ number_format($invalidScans) }}</h3>
            </div>
          </div>
          <div class="small text-muted mt-3">These usually need help checking the QR, ticket, or scan process.</div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card attendance-summary-card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">
              <span class="avatar-initial rounded-circle bg-label-primary"><i class="icon-base ti tabler-scan"></i></span>
            </div>
            <div>
              <div class="text-muted small mb-1">Active scan posts</div>
              <h3 class="mb-0">{{ number_format($activeScannerCount) }}</h3>
            </div>
          </div>
          <div class="small text-muted mt-3">{{ number_format($totalScans) }} scans shown for the current filters.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card attendance-data-card mb-6">
    <div class="card-body p-4">
      <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
          <h5 class="mb-1">How to read the statuses</h5>
          <p class="text-muted mb-0">To make this easier for non-technical admins, each status uses plain operational wording.</p>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-4">
          <div class="attendance-legend-chip">
            <span class="attendance-legend-dot is-success"></span>
            <div>
              <div class="fw-semibold mb-1">Checked In</div>
              <div class="text-muted small">The participant was scanned successfully and attendance was recorded.</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-4">
          <div class="attendance-legend-chip">
            <span class="attendance-legend-dot is-warning"></span>
            <div>
              <div class="fw-semibold mb-1">Already Scanned</div>
              <div class="text-muted small">The same ticket was scanned again, but it does not increase attendance.</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-4">
          <div class="attendance-legend-chip">
            <span class="attendance-legend-dot is-danger"></span>
            <div>
              <div class="fw-semibold mb-1">Needs Review</div>
              <div class="text-muted small">There was an issue during the scan, so an admin or staff member should check it.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-6">
    <div class="col-12 col-xl-7">
      <div class="card attendance-data-card h-100">
        <div class="card-header border-0 pb-0">
          <h5 class="mb-1">Latest Scan Activity</h5>
          <small class="text-muted">Newest records appear first. Useful for checking participant issues on-site.</small>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table attendance-table">
            <thead>
              <tr>
                <th>Scan Time</th>
                <th>Participant / Ticket</th>
                <th>Scan Post / Staff</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($history as $log)
                @php($status = $statusMeta($log['result'] ?? null))
                <tr>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-medium">{{ $formatDateTime($log['scanned_at'] ?? null) }}</span>
                      <small class="text-muted">Recorded date: {{ $formatDate($log['scan_date'] ?? null) }}</small>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-medium">{{ $log['ticket_code'] ?? '-' }}</span>
                      <small class="text-muted">Participant ID: {{ $log['user_id'] ?? '-' }}</small>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-medium">{{ $log['scanner_name'] ?? '-' }}</span>
                      <small class="text-muted">
                        {{ $log['scanner_role'] ?? 'Staff' }}
                        @if (filled($log['scanner_id'] ?? null))
                          &middot; {{ $log['scanner_id'] }}
                        @endif
                      </small>
                    </div>
                  </td>
                  <td>
                    <span class="badge rounded-pill {{ $status['class'] }}">{{ $status['label'] }}</span>
                    <small class="attendance-status-note">{{ $status['note'] }}</small>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-6 text-muted">No scan activity is available for this date range.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-body border-top">
          {{ $history->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-5">
      <div class="card attendance-data-card mb-6">
        <div class="card-header border-0 pb-0">
          <h5 class="mb-1">Daily Summary</h5>
          <small class="text-muted">This helps admins see which days were busiest and which had the most issues.</small>
        </div>
        <div class="table-responsive">
          <table class="table attendance-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Total Scans</th>
                <th>Successful</th>
                <th>Needs Attention</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($dailyAttendance as $day)
                <tr>
                  <td class="fw-medium">{{ $formatDate($day['scan_date'] ?? null) }}</td>
                  <td>{{ number_format($day['total_scans'] ?? 0) }}</td>
                  <td>{{ number_format($day['successful_attendance'] ?? 0) }}</td>
                  <td>
                    <div class="d-flex flex-column">
                      <span>Repeat scans: {{ number_format($day['duplicate_scans'] ?? 0) }}</span>
                      <small class="text-muted">Needs review: {{ number_format($day['invalid_scans'] ?? 0) }}</small>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted">No daily summary is available for this data yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card attendance-data-card">
        <div class="card-header border-0 pb-0">
          <h5 class="mb-1">Scan Posts / Staff</h5>
          <small class="text-muted">See which scan posts are most active and which need the most help.</small>
        </div>
        <div class="table-responsive">
          <table class="table attendance-table">
            <thead>
              <tr>
                <th>Scan Post</th>
                <th>Summary</th>
                <th>Last Active</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($scannerActivity as $scanner)
                <tr>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-medium">{{ $scanner['scanner_name'] ?: '-' }}</span>
                      <small class="text-muted">
                        {{ $scanner['scanner_role'] ?: 'Staff' }}
                        @if (filled($scanner['scanner_id'] ?? null))
                          &middot; {{ $scanner['scanner_id'] }}
                        @endif
                      </small>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <span>Total scans: {{ number_format($scanner['total_scans'] ?? 0) }}</span>
                      <small class="text-muted">
                        Successful {{ number_format($scanner['successful_scans'] ?? 0) }},
                        repeat scans {{ number_format($scanner['duplicate_scans'] ?? 0) }},
                        needs review {{ number_format($scanner['invalid_scans'] ?? 0) }}
                      </small>
                    </div>
                  </td>
                  <td>{{ $formatDateTime($scanner['last_scanned_at'] ?? null) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center py-4 text-muted">No scan post activity has been recorded yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
