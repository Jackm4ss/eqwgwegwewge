@extends('admin.layouts.app')

@php
  $title = 'Admin Activity Log';
@endphp

@section('content')
  <div class="card mb-6">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.logs.index') }}" class="row g-4 align-items-end">
        <div class="col-md-4">
          <label class="form-label" for="q">Search activity</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="email admin, action, target id" />
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
          <button type="submit" class="btn btn-primary">Filter</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-1">Admin Activity Records</h5>
        <small class="text-muted">{{ number_format($logs->total()) }} logs found</small>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.exports.download', ['type' => 'admin-logs', 'format' => 'csv'] + request()->query()) }}" class="btn btn-sm btn-label-success">CSV</a>
        <a href="{{ route('admin.exports.download', ['type' => 'admin-logs', 'format' => 'xlsx'] + request()->query()) }}" class="btn btn-sm btn-label-info">Excel</a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Admin</th>
            <th>Action</th>
            <th>Target</th>
            <th>Metadata</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td>{{ $log['created_at'] ?? '-' }}</td>
              <td>
                <div class="d-flex flex-column">
                  <span>{{ $log['admin_email'] ?? '-' }}</span>
                  <small class="text-muted">ID: {{ $log['admin_id'] ?? '-' }}</small>
                </div>
              </td>
              <td><span class="badge bg-label-primary">{{ $log['action_type'] ?? '-' }}</span></td>
              <td>
                <div class="d-flex flex-column">
                  <span>{{ $log['target_type'] ?? '-' }}</span>
                  <small class="text-muted">{{ $log['target_id'] ?? '-' }}</small>
                </div>
              </td>
              <td><small class="text-muted">{{ json_encode($log['metadata'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</small></td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center py-6 text-muted">No admin activity logs found yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body border-top">
      {{ $logs->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  </div>
@endsection
