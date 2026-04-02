@extends('admin.layouts.app')

@php
  $title = 'Public Report';
@endphp

@section('content')
  @php
    $publicFormReference = $publicFormReference ?? [];
    $reportTypeOptions = $publicReportTypeOptions ?? [];
  @endphp

  <div class="card mb-6">
    <div class="card-body">
      <div class="d-flex flex-column gap-4">
        <div>
          <span class="badge bg-label-warning mb-2">Report Management</span>
          <h5 class="mb-1">{{ $publicFormReference['title'] ?? 'Public Report' }}</h5>
          <p class="text-muted mb-2">
            {{ $publicFormReference['description'] ?? 'Review submissions that come from the public report form.' }}
          </p>
          <p class="text-muted mb-0">
            {{ $publicFormReference['note'] ?? 'Every submitted report is stored in the admin dashboard for review.' }}
          </p>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-2">Report Types From Public Form</small>
            <div class="d-flex flex-wrap gap-2">
              @foreach ($reportTypeOptions as $option)
                <span class="badge bg-label-primary">{{ $option['label'] }}</span>
              @endforeach
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-2">Required Fields</small>
            <div class="d-flex flex-wrap gap-2">
              @foreach (($publicFormReference['required_fields'] ?? []) as $field)
                <span class="badge bg-label-success">{{ $field }}</span>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      @if (filled($publicFormReference['disclaimer'] ?? ''))
        <div class="alert alert-warning mb-0 mt-4" role="alert">
          <div class="fw-semibold mb-1">Public Form Disclaimer</div>
          <div>{{ $publicFormReference['disclaimer'] }}</div>
        </div>
      @endif
    </div>
  </div>

  @include('admin.reports.partials.public-reports-panel', [
    'publicReportIndexUrl' => route('admin.public-reports.index'),
    'publicReportPanelTitle' => 'Public Report Inbox',
    'publicReportPanelDescription' => 'Saved submissions from the public form, aligned with the fields used in ReportPage.tsx.',
  ])
@endsection
