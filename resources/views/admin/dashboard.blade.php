@extends('admin.layouts.app')

@php
  $title = 'Dashboard';
@endphp

@push('vendor-styles')
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/apex-charts/apex-charts.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/flatpickr/flatpickr.css') }}" />
  <style>
    .dashboard-filter-date.flatpickr-input[readonly] {
      background-color: var(--bs-body-bg);
    }
  </style>
@endpush

@push('vendor-scripts')
  <script src="{{ asset('assets-vuexy/vendor/libs/apex-charts/apexcharts.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/libs/flatpickr/flatpickr.js') }}"></script>
@endpush

@section('content')
  @php
    $hasDateFilters = filled($filters['from'] ?? null) || filled($filters['to'] ?? null);
    $dateRangeLabel = data_get($dashboard, 'date_range.label', '-');
    $dateRangeBadge = data_get($dashboard, 'date_range.badge', 'Last 7 Days');
    $registrationBadge = data_get($dashboard, 'date_range.registration_badge', 'All Time');
    $rangeDays = (int) data_get($dashboard, 'date_range.days', 7);
    $isFilteredRange = (bool) data_get($dashboard, 'date_range.is_filtered', false);
    $campaignLinkSummary = $campaignLinkSummary ?? ['storage_ready' => false, 'total' => 0, 'active' => 0, 'homepage' => 0, 'register' => 0];
    $publicReportSummary = $publicReportSummary ?? [];
    $trafficVisitSummary = $trafficVisitSummary ?? ['storage_ready' => false, 'total_unique_ips' => 0, 'google_search_unique_ips' => 0, 'direct_unique_ips' => 0, 'social_media_unique_ips' => 0];
  @endphp

  <div class="card mb-6">
    <div class="card-body">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4">
        <div>
          <span class="badge bg-label-info mb-2">Analytics Dashboard</span>
          <h5 class="mb-1">Visitor Statistics</h5>
          <p class="text-muted mb-0">
            Unique IP summary for public visitors, grouped by Google Search, Direct, and Social Media based on captured traffic attribution.
          </p>
        </div>
      </div>

      @unless (data_get($trafficVisitSummary, 'storage_ready', false))
        <div class="alert alert-warning d-flex align-items-start mt-4 mb-0" role="alert">
          <span class="alert-icon me-2"><i class="icon-base ti tabler-alert-circle"></i></span>
          <div class="lh-sm">
            Visitor analytics storage is not ready yet. Run the latest migration so the dashboard can start counting unique IP traffic.
          </div>
        </div>
      @endunless

      <div class="row g-3 mt-1">
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Unique IP visitors</small>
            <h4 class="mb-0">{{ number_format((int) data_get($trafficVisitSummary, 'total_unique_ips', 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Google Search</small>
            <h4 class="mb-0">{{ number_format((int) data_get($trafficVisitSummary, 'google_search_unique_ips', 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Direct</small>
            <h4 class="mb-0">{{ number_format((int) data_get($trafficVisitSummary, 'direct_unique_ips', 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Social Media</small>
            <h4 class="mb-0">{{ number_format((int) data_get($trafficVisitSummary, 'social_media_unique_ips', 0)) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-6">
    <div class="card-body">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4">
        <div>
          <span class="badge bg-label-primary mb-2">Share Links</span>
          <h5 class="mb-1">Campaign Links</h5>
          <p class="text-muted mb-0">
            Create trackable links for Songkran promotions and copy them when the team needs to share a homepage or registration link.
          </p>
        </div>
        <a href="{{ route('admin.campaign-links.index') }}" class="btn btn-primary">
          Open Link Builder
        </a>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Total links</small>
            <h4 class="mb-0">{{ number_format((int) data_get($campaignLinkSummary, 'total', 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Active links</small>
            <h4 class="mb-0">{{ number_format((int) data_get($campaignLinkSummary, 'active', 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Homepage links</small>
            <h4 class="mb-0">{{ number_format((int) data_get($campaignLinkSummary, 'homepage', 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Register page links</small>
            <h4 class="mb-0">{{ number_format((int) data_get($campaignLinkSummary, 'register', 0)) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-6">
    <div class="card-body">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4">
        <div>
          <span class="badge bg-label-warning mb-2">Help Desk</span>
          <h5 class="mb-1">Public Reports</h5>
          <p class="text-muted mb-0">
            View incoming help desk reports from the public form, including case IDs, incident time, chronology, and IP address.
          </p>
        </div>
        <a href="{{ route('admin.public-reports.index') }}" class="btn btn-primary">
          Open Public Reports
        </a>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Total reports</small>
            <h4 class="mb-0">{{ number_format((int) data_get($publicReportSummary, 'total', 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Incident / Security</small>
            <h4 class="mb-0">{{ number_format((int) data_get($publicReportSummary, 'incident_security', 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Lost Item + Locker</small>
            <h4 class="mb-0">{{ number_format((int) data_get($publicReportSummary, 'lost_item', 0) + (int) data_get($publicReportSummary, 'lost_locker_card', 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block mb-1">Medical Attention</small>
            <h4 class="mb-0">{{ number_format((int) data_get($publicReportSummary, 'medical_attention', 0)) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-6">
    <div class="card-body">
      <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
        <div>
          <h5 class="mb-1">Dashboard Filters</h5>
          <small class="text-muted">Use a date range to update the dashboard metrics and visitor trend.</small>
        </div>
        <div class="small text-muted">
          Showing scan data for <span class="fw-semibold text-heading">{{ $dateRangeLabel }}</span>
        </div>
      </div>

      <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-3 align-items-end">
        <div class="col-sm-6 col-lg-3">
          <label class="form-label" for="from">From date</label>
          <input type="text" class="form-control dashboard-filter-date" id="from" name="from" value="{{ $filters['from'] ?? '' }}"
            placeholder="Select date" autocomplete="off" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label" for="to">To date</label>
          <input type="text" class="form-control dashboard-filter-date" id="to" name="to" value="{{ $filters['to'] ?? '' }}"
            placeholder="Select date" autocomplete="off" />
        </div>
        <div class="col-sm-6 col-lg-2 d-grid">
          <button type="submit" class="btn btn-primary">Apply</button>
        </div>
        @if ($hasDateFilters)
          <div class="col-sm-6 col-lg-2 d-grid">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-label-secondary">Reset</a>
          </div>
        @endif
      </form>
    </div>
  </div>

  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-users"></i></span>
            </div>
            <span class="badge bg-label-primary">{{ $registrationBadge }}</span>
          </div>
          <span class="fw-medium d-block mb-1">Total Registrations</span>
          <h3 class="card-title mb-0">{{ number_format($dashboard['total_registrations'] ?? 0) }}</h3>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded bg-label-success"><i class="icon-base ti tabler-qrcode"></i></span>
            </div>
            <span class="badge bg-label-success">{{ $dateRangeBadge }}</span>
          </div>
          <span class="fw-medium d-block mb-1">Total Scans</span>
          <h3 class="card-title mb-0">{{ number_format(data_get($dashboard, 'daily_scan_statistics.total_scans', 0)) }}</h3>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded bg-label-info"><i class="icon-base ti tabler-user-check"></i></span>
            </div>
            <span class="badge bg-label-info">Valid</span>
          </div>
          <span class="fw-medium d-block mb-1">Unique Attendees</span>
          <h3 class="card-title mb-0">{{ number_format(data_get($dashboard, 'daily_scan_statistics.unique_visitors', 0)) }}</h3>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded bg-label-warning"><i class="icon-base ti tabler-alert-triangle"></i></span>
            </div>
            <span class="badge bg-label-warning">Duplicate</span>
          </div>
          <span class="fw-medium d-block mb-1">Duplicate Scans</span>
          <h3 class="card-title mb-0">{{ number_format(data_get($dashboard, 'daily_scan_statistics.duplicate_scans', 0)) }}</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-6">
    <div class="col-12 col-xl-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-1">{{ $isFilteredRange ? 'Visitor Trend' : 'Visitor Trend, Last '.$rangeDays.' Days' }}</h5>
            <small class="text-muted">Based on unique successful scans per day for {{ $dateRangeLabel }}</small>
          </div>
        </div>
        <div class="card-body">
          <div id="visitorChart" style="min-height: 320px;"></div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1">Scan Statistics</h5>
          <small class="text-muted">Showing data for {{ $dateRangeLabel }}</small>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <span class="text-heading">Successful Scans</span>
            <span class="badge bg-label-success">{{ number_format(data_get($dashboard, 'daily_scan_statistics.successful_scans', 0)) }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-4">
            <span class="text-heading">Unique Visitors</span>
            <span class="badge bg-label-info">{{ number_format(data_get($dashboard, 'daily_scan_statistics.unique_visitors', 0)) }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-4">
            <span class="text-heading">Duplicate</span>
            <span class="badge bg-label-warning">{{ number_format(data_get($dashboard, 'daily_scan_statistics.duplicate_scans', 0)) }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-heading">Invalid / Other</span>
            <span class="badge bg-label-danger">{{ number_format(data_get($dashboard, 'daily_scan_statistics.invalid_scans', 0)) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof window.flatpickr === 'function') {
        const fromInput = document.querySelector('#from.dashboard-filter-date');
        const toInput = document.querySelector('#to.dashboard-filter-date');

        const destroyPicker = function (input) {
          if (input?._flatpickr) {
            input._flatpickr.destroy();
          }
        };

        const applyAltInputAttributes = function (instance, label) {
          const altInput = instance?.altInput;

          if (!altInput) {
            return;
          }

          altInput.classList.add('dashboard-filter-date');
          altInput.setAttribute('aria-label', label);
          altInput.setAttribute('placeholder', 'Select date');
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
              applyAltInputAttributes(instance, 'From date');
            },
            onChange: function (selectedDates) {
              if (toInput?._flatpickr) {
                toInput._flatpickr.set('minDate', selectedDates[0] ?? null);
              }
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
              applyAltInputAttributes(instance, 'To date');
            },
            onChange: function (selectedDates) {
              if (fromInput?._flatpickr) {
                fromInput._flatpickr.set('maxDate', selectedDates[0] ?? null);
              }
            },
          });
        }
      }

      const chartEl = document.querySelector('#visitorChart');

      if (!chartEl || typeof ApexCharts === 'undefined') {
        return;
      }

      const options = {
        chart: {
          type: 'area',
          height: 320,
          toolbar: { show: false }
        },
        series: [{
          name: 'Visitors',
          data: @json(data_get($dashboard, 'visitor_chart.series', []))
        }],
        xaxis: {
          categories: @json(data_get($dashboard, 'visitor_chart.labels', []))
        },
        stroke: {
          curve: 'smooth',
          width: 3
        },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05
          }
        },
        colors: ['#0284c7'],
        dataLabels: { enabled: false },
        yaxis: {
          min: 0,
          forceNiceScale: true
        }
      };

      new ApexCharts(chartEl, options).render();
    });
  </script>
@endpush
