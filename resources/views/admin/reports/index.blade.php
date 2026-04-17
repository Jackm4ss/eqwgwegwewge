@extends('admin.layouts.app')

@php
  $title = 'Reporting & Export';
@endphp

@section('content')
  @include('admin.reports.partials.public-reports-panel', [
    'publicReportIndexUrl' => route('admin.reports.index'),
    'publicReportPanelTitle' => 'Public Reports',
    'publicReportPanelDescription' => 'Review submitted cases here so the team does not rely on email only.',
  ])

  @unless ($scanLogReportingEnabled ?? false)
    <div class="alert alert-warning mt-4" role="alert">
      Scan-based reports and exports are temporarily disabled to protect production stability and Firebase usage.
    </div>
  @endunless

  <div class="row g-6">
    <div class="col-12 col-xl-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-1">Daily Report</h5>
            <small class="text-muted">Scan totals, visitors, and attendance statistics</small>
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
@endsection
