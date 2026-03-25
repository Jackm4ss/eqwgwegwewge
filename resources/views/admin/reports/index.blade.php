@extends('admin.layouts.app')

@php
  $title = 'Reporting & Export';
@endphp

@section('content')
  <div class="card mb-6">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-4 align-items-end">
        <div class="col-md-4">
          <label class="form-label" for="q">Filter user data</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="name, email, ticket code, identity number" />
        </div>
        <div class="col-md-3">
          <label class="form-label" for="from">Start date</label>
          <input type="date" class="form-control" id="from" name="from" value="{{ $filters['from'] ?? '' }}" />
        </div>
        <div class="col-md-3">
          <label class="form-label" for="to">End date</label>
          <input type="date" class="form-control" id="to" name="to" value="{{ $filters['to'] ?? '' }}" />
        </div>
        <div class="col-md-2 d-grid">
          <button type="submit" class="btn btn-primary">Apply</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-6 mb-6">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <span class="fw-medium d-block mb-1">Total Scans</span>
          <h3 class="mb-0">{{ number_format(data_get($reports, 'daily.jumlah_scan', 0)) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <span class="fw-medium d-block mb-1">Total Visitors</span>
          <h3 class="mb-0">{{ number_format(data_get($reports, 'daily.jumlah_pengunjung', 0)) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <span class="fw-medium d-block mb-1">Total Participants</span>
          <h3 class="mb-0">{{ number_format(data_get($reports, 'overall.total_peserta', 0)) }}</h3>
        </div>
      </div>
    </div>
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
              @forelse (data_get($reports, 'daily.statistik_kehadiran', []) as $day)
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
              @forelse (data_get($reports, 'overall.statistik_kunjungan', []) as $visit)
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
