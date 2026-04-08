@extends('admin.layouts.app')

@php
  $title = 'Campaign Links';
  $formatDateTime = static fn ($value): string => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y, H:i') : '-';
  $submittedLinkId = old('campaign_link_id');
  $createSubmissionAttempted = old('_create_campaign_link') === '1';
  $createHasErrors = $createSubmissionAttempted && $errors->any();
  $oldInput = session()->getOldInput();
  $exportQuery = array_filter([
    'q' => data_get($filters, 'q'),
    'status' => data_get($filters, 'status') !== 'all' ? data_get($filters, 'status') : null,
    'destination' => data_get($filters, 'destination') !== 'all' ? data_get($filters, 'destination') : null,
    'source' => data_get($filters, 'source') !== 'all' ? data_get($filters, 'source') : null,
    'export' => 'csv',
  ], static fn ($value) => filled($value));
  $sourceAnalyticsRows = collect(data_get($sourceAnalytics ?? [], 'rows', []));
@endphp

@push('vendor-styles')
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/select2/select2.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/apex-charts/apex-charts.css') }}" />
  <style>
    .campaign-links-page .hero-card {
      background: linear-gradient(135deg, rgba(14, 116, 144, 0.12), rgba(15, 23, 42, 0.04));
      border: 1px solid rgba(14, 116, 144, 0.18);
    }

    .campaign-links-page .builder-sidebar {
      position: sticky;
      top: 5.5rem;
    }

    .campaign-links-page .preview-box,
    .campaign-links-page .url-box,
    .campaign-links-page .analytics-box {
      border-radius: 1rem;
      background: #f8fafc;
      border: 1px solid rgba(148, 163, 184, 0.25);
    }

    .campaign-links-page .url-box code {
      word-break: break-word;
      white-space: normal;
      color: #0f172a;
    }

    .campaign-links-page .destination-option {
      border: 1px solid rgba(148, 163, 184, 0.35);
      border-radius: 1rem;
      padding: 1rem;
      height: 100%;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
      cursor: pointer;
      display: block;
    }

    .campaign-links-page .destination-input:checked + .destination-option {
      border-color: rgba(14, 116, 144, 0.9);
      box-shadow: 0 0.75rem 1.5rem rgba(14, 116, 144, 0.12);
      transform: translateY(-1px);
    }

    .campaign-links-page .library-card {
      border: 1px solid rgba(148, 163, 184, 0.28);
      border-radius: 1.25rem;
      overflow: hidden;
    }

    .campaign-links-page .library-card.is-highlighted {
      border-color: rgba(245, 158, 11, 0.85);
      box-shadow: 0 1rem 2rem rgba(245, 158, 11, 0.12);
    }

    .campaign-links-page .tag-pill {
      border-radius: 999px;
      padding: 0.4rem 0.7rem;
      background: #eef2ff;
      color: #3730a3;
      font-size: 0.8125rem;
      font-weight: 600;
    }

    .campaign-links-page .source-chart-shell {
      min-height: 320px;
    }

    .campaign-links-page .source-breakdown-row + .source-breakdown-row {
      border-top: 1px solid rgba(148, 163, 184, 0.2);
      padding-top: 0.9rem;
      margin-top: 0.9rem;
    }

    .campaign-links-page .source-breakdown-bar {
      height: 0.4rem;
      border-radius: 999px;
      background: rgba(148, 163, 184, 0.18);
      overflow: hidden;
    }

    .campaign-links-page .source-breakdown-bar span {
      display: block;
      height: 100%;
      border-radius: inherit;
      background: linear-gradient(90deg, #0284c7, #0f766e);
    }

    .campaign-links-page .select2-container {
      width: 100% !important;
    }

    .campaign-links-page .select2-container .select2-selection--single {
      min-height: calc(2.25rem + 2px);
      border-color: #d9dee3;
      display: flex;
      align-items: center;
    }

    .campaign-links-page .select2-container .select2-selection__rendered {
      line-height: 1.5 !important;
      padding-left: 0.875rem !important;
      padding-right: 2rem !important;
      color: #0f172a;
    }

    .campaign-links-page .select2-container .select2-selection__arrow {
      height: 100% !important;
      right: 0.5rem !important;
    }

    .select2-dropdown .select2-results__options {
      max-height: 240px;
      overflow-y: auto;
    }
  </style>
@endpush

@push('vendor-scripts')
  <script src="{{ asset('assets-vuexy/vendor/libs/select2/select2.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/libs/apex-charts/apexcharts.js') }}"></script>
@endpush

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const initSourceSelects = () => {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.select2) {
          return;
        }

        window.jQuery('[data-campaign-source-select]').each(function () {
          const $select = window.jQuery(this);

          if (!$select.parent().hasClass('position-relative')) {
            $select.wrap('<div class="position-relative"></div>');
          }

          if ($select.data('select2')) {
            $select.trigger('change.select2');
            return;
          }

          $select.select2({
            width: '100%',
            minimumResultsForSearch: 8,
            dropdownParent: $select.parent(),
          });
        });
      };

      const slugify = (value) => String(value || '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

      const trimTrailingSlash = (value) => String(value || '').replace(/\/+$/, '');

      const buildUrl = (baseUrl, params) => {
        const query = Object.entries(params)
          .filter(([, value]) => value)
          .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
          .join('&');

        if (!query) {
          return baseUrl;
        }

        return `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}${query}`;
      };

      document.querySelectorAll('[data-campaign-link-form]').forEach((form) => {
        const preview = form.querySelector('[data-campaign-link-preview]');

        if (!preview) {
          return;
        }

        const syncPreview = () => {
          const slug = slugify(form.querySelector('[data-preview-slug]')?.value);
          const destination = form.querySelector('[data-preview-destination]:checked')?.value || 'homepage';
          const source = slugify(form.querySelector('[data-preview-source]')?.value);
          const medium = slugify(form.querySelector('[data-preview-medium]')?.value);
          const campaign = slugify(form.querySelector('[data-preview-campaign]')?.value);
          const content = slugify(form.querySelector('[data-preview-content]')?.value);
          const shortBaseUrl = trimTrailingSlash(preview.dataset.shortBaseUrl || '');
          const finalBaseUrl = trimTrailingSlash(destination === 'register' ? preview.dataset.registerUrl : preview.dataset.homepageUrl);
          const shortUrl = `${shortBaseUrl}/${slug || 'your-slug'}`;
          const finalUrl = buildUrl(finalBaseUrl, {
            utm_source: source,
            utm_medium: medium,
            utm_campaign: campaign,
            utm_content: content,
          });

          preview.querySelector('[data-preview-short-url]').textContent = shortUrl;
          preview.querySelector('[data-preview-final-url]').textContent = finalUrl;
        };

        form.querySelectorAll('input, textarea, select').forEach((input) => {
          input.addEventListener('input', syncPreview);
          input.addEventListener('change', syncPreview);
        });

        syncPreview();
      });

      @if (data_get($sourceAnalytics, 'has_data'))
        const sourceChartEl = document.querySelector('#sourceTrafficChart');

        if (sourceChartEl && typeof ApexCharts !== 'undefined') {
          const sourceChartOptions = {
            chart: {
              type: 'pie',
              height: 320,
              toolbar: { show: false }
            },
            labels: @json(data_get($sourceAnalytics, 'labels', [])),
            series: @json(data_get($sourceAnalytics, 'series', [])),
            legend: { show: false },
            dataLabels: {
              enabled: true,
              formatter: function (value) {
                return `${Math.round(value)}%`;
              }
            },
            stroke: {
              width: 2,
              colors: ['#ffffff']
            },
            colors: ['#0284c7', '#0f766e', '#7c3aed', '#ea580c', '#db2777', '#16a34a', '#ca8a04', '#475569'],
            tooltip: {
              y: {
                formatter: function (value) {
                  return `${value} clicks`;
                }
              }
            },
            responsive: [{
              breakpoint: 992,
              options: {
                chart: {
                  height: 280
                }
              }
            }]
          };

          new ApexCharts(sourceChartEl, sourceChartOptions).render();
        }
      @endif

      document.querySelectorAll('[data-copy-text]').forEach((button) => {
        button.addEventListener('click', async function () {
          const originalLabel = this.textContent;

          try {
            await navigator.clipboard.writeText(this.dataset.copyText || '');
            this.textContent = 'Copied';
          } catch (error) {
            this.textContent = 'Copy failed';
          }

          window.setTimeout(() => {
            this.textContent = originalLabel;
          }, 1600);
        });
      });

      initSourceSelects();
    });
  </script>
@endpush

@section('content')
  <div class="campaign-links-page">
    <div class="card hero-card border-0 shadow-sm mb-6">
      <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
          <div>
            <span class="badge bg-label-primary mb-3">Simple Link Builder</span>
            <h3 class="mb-2">Campaign Links</h3>
            <p class="text-muted mb-0">
              Create public shortlinks like <code>{{ $homepageUrl }}/fb</code>, keep slug ownership inside admin, and monitor visit traffic from one page.
            </p>
          </div>
          <div class="row g-3 flex-grow-1">
            <div class="col-sm-6 col-xl-3">
              <div class="analytics-box h-100 p-3">
                <small class="text-muted d-block mb-1">Total links</small>
                <h4 class="mb-0">{{ number_format((int) data_get($summary, 'total', 0)) }}</h4>
              </div>
            </div>
            <div class="col-sm-6 col-xl-3">
              <div class="analytics-box h-100 p-3">
                <small class="text-muted d-block mb-1">Active now</small>
                <h4 class="mb-0">{{ number_format((int) data_get($summary, 'active', 0)) }}</h4>
              </div>
            </div>
            <div class="col-sm-6 col-xl-3">
              <div class="analytics-box h-100 p-3">
                <small class="text-muted d-block mb-1">Register destination</small>
                <h4 class="mb-0">{{ number_format((int) data_get($summary, 'register', 0)) }}</h4>
              </div>
            </div>
            <div class="col-sm-6 col-xl-3">
              <div class="analytics-box h-100 p-3">
                <small class="text-muted d-block mb-1">Tracked visits</small>
                <h4 class="mb-0">{{ number_format((int) data_get($summary, 'visits', 0)) }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if (session('status'))
      <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->has('error'))
      <div class="alert alert-danger" role="alert">{{ $errors->first('error') }}</div>
    @endif

    <div class="row g-6">
      <div class="col-12 col-xl-4">
        <div class="builder-sidebar">
          <div class="card shadow-sm">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                <div>
                  <h5 class="mb-1">Create Campaign Link</h5>
                  <p class="text-muted mb-0">Create a public root slug manually. Slug is mandatory and managed one by one from this panel.</p>
                </div>
                <span class="badge {{ $storageReady ? 'bg-label-success' : 'bg-label-warning' }}">
                  {{ $storageReady ? 'Ready' : 'Migration needed' }}
                </span>
              </div>

              @if ($storageReady)
                @include('admin.campaign-links.partials.form', [
                  'formMode' => 'create',
                  'formMethod' => 'POST',
                  'formAction' => route('admin.campaign-links.store'),
                  'formId' => 'campaign-link-create',
                  'fieldPrefix' => 'create',
                  'link' => $createForm,
                  'submitLabel' => 'Save link',
                  'submitClass' => 'btn btn-primary',
                  'errorsEnabled' => $createHasErrors,
                  'destinationOptions' => $destinationOptions,
                  'homepageUrl' => $homepageUrl,
                  'registerUrl' => $registerUrl,
                ])
              @else
                <div class="alert alert-warning mb-0" role="alert">
                  Campaign Links is not ready yet because the <code>campaign_links</code> table is missing or outdated.
                  Run <code>php artisan migrate</code> first.
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-8">
        <div class="card shadow-sm mb-6">
          <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
              <div>
                <h5 class="mb-1">Analytics & Filters</h5>
                <p class="text-muted mb-0">Filter the shortlink library by slug, source, destination, and status. Traffic is tracked automatically for active slugs.</p>
              </div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-sm-6 col-xl-3">
                <div class="analytics-box p-3 h-100">
                  <small class="text-muted d-block mb-1">Filtered links</small>
                  <h4 class="mb-0">{{ number_format((int) data_get($analyticsSummary, 'filtered_total', 0)) }}</h4>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="analytics-box p-3 h-100">
                  <small class="text-muted d-block mb-1">Filtered active</small>
                  <h4 class="mb-0">{{ number_format((int) data_get($analyticsSummary, 'filtered_active', 0)) }}</h4>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="analytics-box p-3 h-100">
                  <small class="text-muted d-block mb-1">Filtered inactive</small>
                  <h4 class="mb-0">{{ number_format((int) data_get($analyticsSummary, 'filtered_inactive', 0)) }}</h4>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="analytics-box p-3 h-100">
                  <small class="text-muted d-block mb-1">Filtered visits</small>
                  <h4 class="mb-0">{{ number_format((int) data_get($analyticsSummary, 'filtered_visits', 0)) }}</h4>
                </div>
              </div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-lg-7">
                <div class="analytics-box p-3 h-100">
                  <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                      <h6 class="mb-1">Source Click Breakdown</h6>
                      <p class="text-muted mb-0">Pie chart from total tracked clicks per source after the current filters are applied.</p>
                    </div>
                    <span class="badge bg-label-info">{{ number_format((int) data_get($sourceAnalytics, 'total_visits', 0)) }} clicks</span>
                  </div>

                  @if (data_get($sourceAnalytics, 'has_data'))
                    <div id="sourceTrafficChart" class="source-chart-shell"></div>
                  @else
                    <div class="preview-box p-4 text-center">
                      <span class="badge bg-label-secondary mb-2">No click data yet</span>
                      <p class="text-muted mb-0">The pie chart will appear after campaign links receive tracked visits.</p>
                    </div>
                  @endif
                </div>
              </div>

              <div class="col-lg-5">
                <div class="analytics-box p-3 h-100">
                  <div class="row g-3 mb-3">
                    <div class="col-6">
                      <small class="text-muted d-block mb-1">Top source</small>
                      <h5 class="mb-0">{{ data_get($sourceAnalytics, 'top_source_label', '-') }}</h5>
                      <small class="text-muted">{{ number_format((int) data_get($sourceAnalytics, 'top_source_visits', 0)) }} clicks</small>
                    </div>
                    <div class="col-6">
                      <small class="text-muted d-block mb-1">Sources with clicks</small>
                      <h5 class="mb-0">{{ number_format((int) data_get($sourceAnalytics, 'source_count', 0)) }}</h5>
                      <small class="text-muted">unique sources</small>
                    </div>
                  </div>

                  @if ($sourceAnalyticsRows->isNotEmpty())
                    <div class="d-grid gap-0">
                      @foreach ($sourceAnalyticsRows as $sourceRow)
                        <div class="source-breakdown-row">
                          <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                            <div>
                              <span class="fw-semibold d-block">{{ data_get($sourceRow, 'label') }}</span>
                              <small class="text-muted">{{ number_format((int) data_get($sourceRow, 'links', 0)) }} link(s)</small>
                            </div>
                            <div class="text-end">
                              <span class="fw-semibold d-block">{{ number_format((int) data_get($sourceRow, 'visits', 0)) }}</span>
                              <small class="text-muted">{{ number_format((float) data_get($sourceRow, 'percentage', 0), 1) }}%</small>
                            </div>
                          </div>
                          <div class="source-breakdown-bar">
                            <span style="width: {{ min(100, (float) data_get($sourceRow, 'percentage', 0)) }}%"></span>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  @else
                    <div class="preview-box p-4 text-center">
                      <p class="text-muted mb-0">No sources have recorded clicks yet.</p>
                    </div>
                  @endif
                </div>
              </div>
            </div>

            <form method="GET" action="{{ route('admin.campaign-links.index') }}" class="row g-3 align-items-end">
              <div class="col-md-5">
                <label class="form-label" for="filter_q">Search</label>
                <input type="text" class="form-control" id="filter_q" name="q" value="{{ data_get($filters, 'q') }}" placeholder="name, slug, source, campaign" />
              </div>
              <div class="col-md-2">
                <label class="form-label" for="filter_status">Status</label>
                <select class="form-select" id="filter_status" name="status">
                  <option value="all" @selected(data_get($filters, 'status') === 'all')>All</option>
                  <option value="active" @selected(data_get($filters, 'status') === 'active')>Active</option>
                  <option value="inactive" @selected(data_get($filters, 'status') === 'inactive')>Inactive</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label" for="filter_destination">Destination</label>
                <select class="form-select" id="filter_destination" name="destination">
                  <option value="all" @selected(data_get($filters, 'destination') === 'all')>All</option>
                  @foreach ($destinationOptions as $destinationValue => $destinationOption)
                    <option value="{{ $destinationValue }}" @selected(data_get($filters, 'destination') === $destinationValue)>{{ $destinationOption['label'] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="filter_source">Source</label>
                <select class="form-select" id="filter_source" name="source">
                  <option value="all" @selected(data_get($filters, 'source') === 'all')>All</option>
                  @foreach ($sourceFilterOptions as $sourceOption)
                    <option value="{{ $sourceOption }}" @selected(data_get($filters, 'source') === $sourceOption)>{{ ucfirst(str_replace('-', ' ', $sourceOption)) }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Apply filters</button>
                <a href="{{ route('admin.campaign-links.index') }}" class="btn btn-label-secondary">Reset filters</a>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
              <div>
                <h5 class="mb-1">Campaign Link Library</h5>
                <p class="text-muted mb-0">Share the short URL publicly, keep the final redirect destination and visit counts visible here.</p>
              </div>
              <div class="preview-box p-3">
                <small class="text-muted d-block">Public domain prefix</small>
                <span class="fw-semibold">{{ $homepageUrl }}/<span class="text-muted">slug</span></span>
              </div>
            </div>

            @if ($links->isEmpty())
              <div class="preview-box p-5 text-center">
                <span class="badge bg-label-primary mb-3">Empty library</span>
                <h5 class="mb-2">No campaign links have been saved yet</h5>
                <p class="text-muted mb-0">Create the first manual slug from the left panel.</p>
              </div>
            @else
              <div class="d-grid gap-4">
                @foreach ($links as $link)
                  @php
                    $linkId = (string) data_get($link, 'id');
                    $isSubmittedLink = !$createSubmissionAttempted && (string) $submittedLinkId === $linkId && $errors->any();
                    $editForm = $link;

                    if ($isSubmittedLink) {
                      $editForm = array_merge($editForm, [
                        'name' => old('name', data_get($link, 'name')),
                        'slug' => old('slug', data_get($link, 'slug')),
                        'destination' => old('destination', data_get($link, 'destination')),
                        'source' => old('source', data_get($link, 'source')),
                        'medium' => old('medium', data_get($link, 'medium')),
                        'campaign' => old('campaign', data_get($link, 'campaign')),
                        'utm_content' => old('utm_content', data_get($link, 'utm_content')),
                        'notes' => old('notes', data_get($link, 'notes')),
                        'is_active' => array_key_exists('is_active', $oldInput)
                          ? filled($oldInput['is_active'])
                          : (bool) data_get($link, 'is_active'),
                      ]);
                    }
                  @endphp

                  <div class="library-card {{ $isSubmittedLink ? 'is-highlighted' : '' }}">
                    <div class="card-body p-4">
                      <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <h5 class="mb-0">{{ data_get($link, 'name') }}</h5>
                            <span class="badge {{ data_get($link, 'is_active') ? 'bg-label-success' : 'bg-label-secondary' }}">
                              {{ data_get($link, 'is_active') ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="badge bg-label-info">{{ data_get($link, 'destination_label') }}</span>
                            <span class="badge bg-label-dark">{{ data_get($link, 'slug_with_prefix') }}</span>
                          </div>
                          <p class="text-muted mb-0">
                            Last updated {{ $formatDateTime(data_get($link, 'updated_at')) }}
                          </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <button type="button" class="btn btn-label-primary" data-copy-text="{{ data_get($link, 'short_url') }}">
                            Copy shortlink
                          </button>
                          <a href="{{ data_get($link, 'short_url') }}" target="_blank" rel="noreferrer" class="btn btn-primary">
                            Open shortlink
                          </a>
                        </div>
                      </div>

                      <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="tag-pill">Source: {{ data_get($link, 'source_label') }}</span>
                        <span class="tag-pill">Medium: {{ data_get($link, 'medium_label') }}</span>
                        <span class="tag-pill">Campaign: {{ data_get($link, 'campaign_label') }}</span>
                        <span class="tag-pill">Visits: {{ number_format((int) data_get($link, 'visit_count', 0)) }}</span>
                      </div>

                      <div class="row g-3 mb-3">
                        <div class="col-md-6">
                          <span class="text-muted d-block small">Public short URL</span>
                          <div class="url-box p-3 mt-1">
                            <code>{{ data_get($link, 'short_url') }}</code>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <span class="text-muted d-block small">Redirect destination</span>
                          <div class="url-box p-3 mt-1">
                            <code>{{ data_get($link, 'final_url') }}</code>
                          </div>
                        </div>
                      </div>

                      <div class="row g-3 mb-3">
                        <div class="col-md-4">
                          <span class="text-muted d-block small">Visits</span>
                          <span class="fw-semibold">{{ number_format((int) data_get($link, 'visit_count', 0)) }}</span>
                        </div>
                        <div class="col-md-4">
                          <span class="text-muted d-block small">Last visit</span>
                          <span class="fw-semibold">{{ $formatDateTime(data_get($link, 'last_visited_at')) }}</span>
                        </div>
                        <div class="col-md-4">
                          <span class="text-muted d-block small">Slug</span>
                          <span class="fw-semibold">{{ data_get($link, 'slug_with_prefix') }}</span>
                        </div>
                      </div>

                      @if (filled(data_get($link, 'notes')))
                        <div class="alert alert-secondary mb-3" role="alert">
                          <span class="fw-semibold d-block mb-1">Internal note</span>
                          <span>{{ data_get($link, 'notes') }}</span>
                        </div>
                      @endif

                      <details @if ($isSubmittedLink) open @endif>
                        <summary class="fw-semibold text-primary" style="cursor: pointer;">
                          Edit this saved link
                        </summary>
                        <div class="pt-4">
                          @include('admin.campaign-links.partials.form', [
                            'formMode' => 'edit',
                            'formMethod' => 'PUT',
                            'formAction' => route('admin.campaign-links.update', data_get($link, 'id')),
                            'formId' => 'campaign-link-edit-'.$linkId,
                            'fieldPrefix' => 'edit_'.$linkId,
                            'link' => $editForm,
                            'submitLabel' => 'Save changes',
                            'submitClass' => 'btn btn-primary',
                            'errorsEnabled' => $isSubmittedLink,
                            'deleteAction' => route('admin.campaign-links.destroy', data_get($link, 'id')),
                            'deleteFormId' => 'delete_campaign_link_'.$linkId,
                            'destinationOptions' => $destinationOptions,
                            'homepageUrl' => $homepageUrl,
                            'registerUrl' => $registerUrl,
                          ])

                          <form method="POST" action="{{ route('admin.campaign-links.destroy', data_get($link, 'id')) }}" id="delete_campaign_link_{{ $linkId }}">
                            @csrf
                            @method('DELETE')
                          </form>
                        </div>
                      </details>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <datalist id="campaign-link-medium-options">
      @foreach ($mediumSuggestions as $mediumSuggestion)
        <option value="{{ $mediumSuggestion }}"></option>
      @endforeach
    </datalist>
  </div>
@endsection
