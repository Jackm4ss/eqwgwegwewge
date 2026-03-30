@extends('admin.layouts.app')

@php
  $title = 'Campaign Links';
  $formatDateTime = static fn ($value): string => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y, H:i') : '-';
  $libraryIcon = 'tabler-badge';
  $activeIcon = 'tabler-circle-check';
  $homepageIcon = 'tabler-layout-dashboard';
  $registerIcon = 'tabler-user-check';
  $copyIcon = 'tabler-repeat';
  $saveIcon = 'tabler-upload';
  $openIcon = 'tabler-eye';
  $destinationBaseUrls = [
    'homepage' => config('admin.future_urls.landing', config('app.url')),
    'register' => config('admin.future_urls.register') ?: route('register.form'),
  ];
  $submittedLinkId = old('campaign_link_id');
  $createSubmissionAttempted = old('_create_campaign_link') === '1';
  $createHasErrors = $createSubmissionAttempted && $errors->any();
@endphp

@push('vendor-styles')
  <style>
    .campaign-links-page .hero-card {
      background: linear-gradient(135deg, rgba(14, 116, 144, 0.12), rgba(15, 23, 42, 0.04));
      border: 1px solid rgba(14, 116, 144, 0.18);
    }

    .campaign-links-page .builder-sidebar {
      position: sticky;
      top: 5.5rem;
    }

    .campaign-links-page .icon-tile {
      width: 2.75rem;
      height: 2.75rem;
      border-radius: 0.9rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      flex-shrink: 0;
    }

    .campaign-links-page .icon-tile-primary {
      background: rgba(14, 116, 144, 0.12);
      color: #0f766e;
    }

    .campaign-links-page .icon-tile-success {
      background: rgba(22, 163, 74, 0.12);
      color: #15803d;
    }

    .campaign-links-page .icon-tile-info {
      background: rgba(37, 99, 235, 0.12);
      color: #2563eb;
    }

    .campaign-links-page .icon-tile-warning {
      background: rgba(245, 158, 11, 0.14);
      color: #d97706;
    }

    .campaign-links-page .icon-tile-danger {
      background: rgba(220, 38, 38, 0.12);
      color: #dc2626;
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

    .campaign-links-page .preview-box,
    .campaign-links-page .url-box {
      border-radius: 1rem;
      background: #f8fafc;
      border: 1px solid rgba(148, 163, 184, 0.25);
    }

    .campaign-links-page .url-box code {
      word-break: break-word;
      white-space: normal;
      color: #0f172a;
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
  </style>
@endpush

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const slugify = (value) => String(value || '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

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
          const destination = form.querySelector('[data-preview-destination]:checked')?.value || 'homepage';
          const source = slugify(form.querySelector('[data-preview-source]')?.value);
          const medium = slugify(form.querySelector('[data-preview-medium]')?.value);
          const campaign = slugify(form.querySelector('[data-preview-campaign]')?.value);
          const content = slugify(form.querySelector('[data-preview-content]')?.value);
          const baseUrl = destination === 'register' ? preview.dataset.registerUrl : preview.dataset.homepageUrl;
          const generatedUrl = buildUrl(baseUrl, {
            utm_source: source,
            utm_medium: medium,
            utm_campaign: campaign,
            utm_content: content,
          });

          const destinationLabel = destination === 'register' ? 'Register Page' : 'Homepage';

          preview.querySelector('[data-preview-destination-label]').textContent = destinationLabel;
          preview.querySelector('[data-preview-base-url]').textContent = baseUrl;
          preview.querySelector('[data-preview-generated-url]').textContent = generatedUrl;
        };

        form.querySelectorAll('input, textarea, select').forEach((input) => {
          input.addEventListener('input', syncPreview);
          input.addEventListener('change', syncPreview);
        });

        syncPreview();
      });

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
            <h3 class="mb-2">Create a Songkran share link in a few clicks</h3>
            <p class="text-muted mb-0">
              Pick where this link will be shared and where people should go after clicking it. The system will prepare the full link for you automatically.
            </p>
          </div>
          <div class="row g-3 flex-grow-1">
            <div class="col-sm-6 col-xl-3">
              <div class="preview-box h-100 p-3">
                <span class="icon-tile icon-tile-primary mb-3"><i class="icon-base ti {{ $libraryIcon }}"></i></span>
                <small class="text-muted d-block mb-1">Total links</small>
                <h4 class="mb-0">{{ number_format((int) data_get($summary, 'total', 0)) }}</h4>
              </div>
            </div>
            <div class="col-sm-6 col-xl-3">
              <div class="preview-box h-100 p-3">
                <span class="icon-tile icon-tile-success mb-3"><i class="icon-base ti {{ $activeIcon }}"></i></span>
                <small class="text-muted d-block mb-1">Active now</small>
                <h4 class="mb-0">{{ number_format((int) data_get($summary, 'active', 0)) }}</h4>
              </div>
            </div>
            <div class="col-sm-6 col-xl-3">
              <div class="preview-box h-100 p-3">
                <span class="icon-tile icon-tile-info mb-3"><i class="icon-base ti {{ $homepageIcon }}"></i></span>
                <small class="text-muted d-block mb-1">Homepage</small>
                <h4 class="mb-0">{{ number_format((int) data_get($summary, 'homepage', 0)) }}</h4>
              </div>
            </div>
            <div class="col-sm-6 col-xl-3">
              <div class="preview-box h-100 p-3">
                <span class="icon-tile icon-tile-warning mb-3"><i class="icon-base ti {{ $registerIcon }}"></i></span>
                <small class="text-muted d-block mb-1">Direct register</small>
                <h4 class="mb-0">{{ number_format((int) data_get($summary, 'register', 0)) }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-6">
      <div class="col-12 col-xl-4">
        <div class="builder-sidebar">
          <div class="card shadow-sm mb-6">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                <div>
                  <h5 class="mb-1">Create Campaign Link</h5>
                  <p class="text-muted mb-0">Fill in the simple details below, then copy the final link when it is ready.</p>
                </div>
                <span class="badge {{ $storageReady ? 'bg-label-success' : 'bg-label-warning' }}">
                  {{ $storageReady ? 'Ready' : 'Migration needed' }}
                </span>
              </div>

              @if ($storageReady)
                <form method="POST" action="{{ route('admin.campaign-links.store') }}" data-campaign-link-form class="d-grid gap-4">
                  @csrf
                  <input type="hidden" name="_create_campaign_link" value="1" />

                  <div>
                    <label class="form-label" for="name">Internal link name</label>
                    <input
                      type="text"
                      class="form-control @if ($createHasErrors) @error('name') is-invalid @enderror @endif"
                      id="name"
                      name="name"
                      value="{{ old('name') }}"
                      placeholder="Songkran Instagram Bio"
                    />
                    <small class="text-muted">This name is only for admin use, so make it easy to recognize later.</small>
                  </div>

                  <div>
                    <label class="form-label d-block">Where should people go after clicking this link?</label>
                    <div class="row g-3">
                        @foreach ($destinationOptions as $destinationValue => $destinationOption)
                          @php
                            $destinationIcon = $destinationValue === 'register' ? $registerIcon : $homepageIcon;
                            $destinationIconTone = $destinationValue === 'register' ? 'icon-tile-warning' : 'icon-tile-info';
                          @endphp
                        <div class="col-12">
                          <input
                            class="btn-check destination-input"
                            type="radio"
                            name="destination"
                            id="destination_{{ $destinationValue }}"
                            value="{{ $destinationValue }}"
                            data-preview-destination
                            @checked(old('destination', 'homepage') === $destinationValue)
                          />
                          <label class="destination-option" for="destination_{{ $destinationValue }}">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                              <div class="d-flex gap-3">
                                <span class="icon-tile {{ $destinationIconTone }}">
                                  <i class="icon-base ti {{ $destinationIcon }}"></i>
                                </span>
                                <div>
                                  <div class="fw-semibold text-heading">{{ $destinationOption['label'] }}</div>
                                  <small class="text-muted">{{ $destinationOption['description'] }}</small>
                                </div>
                              </div>
                              <div class="text-primary">
                                <i class="icon-base ti {{ $activeIcon }}"></i>
                              </div>
                            </div>
                          </label>
                        </div>
                      @endforeach
                    </div>
                  </div>

                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label" for="source">Where will this link be shared?</label>
                      <input
                        type="text"
                        class="form-control @if ($createHasErrors) @error('source') is-invalid @enderror @endif"
                        id="source"
                        name="source"
                        list="campaign-link-source-options"
                        value="{{ old('source', 'instagram') }}"
                        placeholder="instagram"
                        data-preview-source
                      />
                      <small class="text-muted">Example: instagram, tiktok, whatsapp, media-partner.</small>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="medium">Where exactly will it appear?</label>
                      <input
                        type="text"
                        class="form-control @if ($createHasErrors) @error('medium') is-invalid @enderror @endif"
                        id="medium"
                        name="medium"
                        list="campaign-link-medium-options"
                        value="{{ old('medium', 'bio') }}"
                        placeholder="bio"
                        data-preview-medium
                      />
                      <small class="text-muted">Example: bio, story, post, ads, or broadcast.</small>
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="campaign">Link group name</label>
                      <input
                        type="text"
                        class="form-control @if ($createHasErrors) @error('campaign') is-invalid @enderror @endif"
                        id="campaign"
                        name="campaign"
                        value="{{ old('campaign', 'songkran2026') }}"
                        placeholder="songkran2026"
                        data-preview-campaign
                      />
                      <small class="text-muted">Use one simple name for links that belong to the same Songkran push.</small>
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="utm_content">Optional version name</label>
                      <input
                        type="text"
                        class="form-control @if ($createHasErrors) @error('utm_content') is-invalid @enderror @endif"
                        id="utm_content"
                        name="utm_content"
                        value="{{ old('utm_content') }}"
                        placeholder="poster-a"
                        data-preview-content
                      />
                      <small class="text-muted">Optional. Use this if you want to separate one design or version from another.</small>
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="notes">Internal note</label>
                      <textarea
                        class="form-control @if ($createHasErrors) @error('notes') is-invalid @enderror @endif"
                        id="notes"
                        name="notes"
                        rows="3"
                        placeholder="Main Instagram bio link for this week"
                      >{{ old('notes') }}</textarea>
                      <small class="text-muted">Optional note for the admin team.</small>
                    </div>
                  </div>

                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', true)) />
                    <label class="form-check-label" for="is_active">Active link</label>
                  </div>

                  <div class="preview-box p-3" data-campaign-link-preview data-homepage-url="{{ $destinationBaseUrls['homepage'] }}" data-register-url="{{ $destinationBaseUrls['register'] }}">
                    <small class="text-muted d-block mb-2">Preview before saving</small>
                    <div class="mb-2">
                      <span class="text-muted d-block small">People will be sent to</span>
                      <span class="fw-semibold" data-preview-destination-label>{{ data_get($createPreview, 'destination_label') }}</span>
                    </div>
                    <div class="mb-2">
                      <span class="text-muted d-block small">Main page</span>
                      <code class="small" data-preview-base-url>{{ data_get($createPreview, 'base_url') }}</code>
                    </div>
                    <div class="url-box p-3">
                      <span class="text-muted d-block small mb-1">Final link to share</span>
                      <code data-preview-generated-url>{{ data_get($createPreview, 'generated_url') }}</code>
                    </div>
                  </div>

                  <button type="submit" class="btn btn-primary">
                    <i class="icon-base ti {{ $saveIcon }} me-2"></i>
                    Save link
                  </button>
                </form>
              @else
                <div class="alert alert-warning mb-0" role="alert">
                  Campaign Links is not ready yet because the <code>campaign_links</code> table is missing.
                  Run <code>php artisan migrate</code> first.
                </div>
              @endif
            </div>
          </div>

          <div class="card shadow-sm">
            <div class="card-body p-4">
              <h6 class="mb-3">Quick Guide</h6>
              <div class="d-grid gap-3">
                <div class="d-flex gap-3">
                  <span class="icon-tile icon-tile-info"><i class="icon-base ti {{ $homepageIcon }}"></i></span>
                  <div>
                    <span class="fw-semibold d-block mb-1">1. Choose where people should land</span>
                    <small class="text-muted">Pick Homepage if you want people to read first. Pick Register Page if you want them to go straight to the form.</small>
                  </div>
                </div>
                <div class="d-flex gap-3">
                  <span class="icon-tile icon-tile-primary"><i class="icon-base ti {{ $libraryIcon }}"></i></span>
                  <div>
                    <span class="fw-semibold d-block mb-1">2. Write where the link will be used</span>
                    <small class="text-muted">For example: <code>instagram</code> + <code>bio</code>, or <code>tiktok</code> + <code>ads</code>.</small>
                  </div>
                </div>
                <div class="d-flex gap-3">
                  <span class="icon-tile icon-tile-success"><i class="icon-base ti {{ $copyIcon }}"></i></span>
                  <div>
                    <span class="fw-semibold d-block mb-1">3. Copy the finished link</span>
                    <small class="text-muted">Share the final link with the team. No need to build the tracking part manually.</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-8">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
              <div>
                <h5 class="mb-1">Campaign Link Library</h5>
                <p class="text-muted mb-0">All saved links are listed here, so the admin team can open, copy, update, or remove them anytime.</p>
              </div>
              <div class="preview-box p-3">
                <small class="text-muted d-block">Naming tip</small>
                <span class="fw-semibold">Use simple Songkran names, for example: <code>songkran2026</code>, <code>songkran-bio</code>, or <code>songkran-register</code></span>
              </div>
            </div>

            @if ($links->isEmpty())
              <div class="preview-box p-5 text-center">
                <span class="badge bg-label-primary mb-3">Empty library</span>
                <h5 class="mb-2">No campaign links have been saved yet</h5>
                <p class="text-muted mb-0">
                  Create the first link from the left panel. After that, the admin team can copy and reuse it from this page.
                </p>
              </div>
            @else
              <div class="d-grid gap-4">
                @foreach ($links as $link)
                  @php
                    $linkId = (string) data_get($link, 'id');
                    $isSubmittedLink = !$createSubmissionAttempted && (string) $submittedLinkId === $linkId && $errors->any();
                  @endphp

                  <div class="library-card {{ $isSubmittedLink ? 'is-highlighted' : '' }}">
                    <div class="card-body p-4">
                      <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="icon-tile icon-tile-primary"><i class="icon-base ti {{ $libraryIcon }}"></i></span>
                            <h5 class="mb-0">{{ data_get($link, 'name') }}</h5>
                            <span class="badge {{ data_get($link, 'is_active') ? 'bg-label-success' : 'bg-label-secondary' }}">
                              {{ data_get($link, 'is_active') ? 'Active' : 'Archived' }}
                            </span>
                            <span class="badge bg-label-info">{{ data_get($link, 'destination_label') }}</span>
                          </div>
                          <p class="text-muted mb-0">
                            Last updated {{ $formatDateTime(data_get($link, 'updated_at')) }}
                          </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <button type="button" class="btn btn-label-primary" data-copy-text="{{ data_get($link, 'generated_url') }}">
                            <i class="icon-base ti {{ $copyIcon }} me-2"></i>
                            Copy link
                          </button>
                          <a href="{{ data_get($link, 'generated_url') }}" target="_blank" rel="noreferrer" class="btn btn-primary">
                            <i class="icon-base ti {{ $openIcon }} me-2"></i>
                            Open link
                          </a>
                        </div>
                      </div>

                      <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="tag-pill">Shared on: {{ data_get($link, 'source_label') }}</span>
                        <span class="tag-pill">Shown in: {{ data_get($link, 'medium_label') }}</span>
                        <span class="tag-pill">Group: {{ data_get($link, 'campaign_label') }}</span>
                        @if (filled(data_get($link, 'utm_content')))
                          <span class="tag-pill">Version: {{ data_get($link, 'utm_content_label') }}</span>
                        @endif
                      </div>

                      <div class="preview-box p-3 mb-3">
                        <div class="row g-3">
                          <div class="col-md-4">
                            <span class="text-muted d-block small">Main page</span>
                            <span class="fw-semibold">{{ data_get($link, 'base_url') }}</span>
                          </div>
                          <div class="col-md-8">
                            <span class="text-muted d-block small">Final link to share</span>
                            <div class="url-box p-3 mt-1">
                              <code>{{ data_get($link, 'generated_url') }}</code>
                            </div>
                          </div>
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
                          <i class="icon-base ti tabler-edit me-2"></i>
                          Edit this saved link
                        </summary>
                        <div class="pt-4">
                          <form method="POST" action="{{ route('admin.campaign-links.update', data_get($link, 'id')) }}" data-campaign-link-form class="d-grid gap-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="campaign_link_id" value="{{ data_get($link, 'id') }}" />

                            <div class="row g-3">
                              <div class="col-md-6">
                                <label class="form-label" for="name_{{ $linkId }}">Link name</label>
                                <input type="text" class="form-control" id="name_{{ $linkId }}" name="name" value="{{ $isSubmittedLink ? old('name') : data_get($link, 'name') }}" />
                              </div>
                              <div class="col-md-6">
                                <label class="form-label d-block">Destination</label>
                                <div class="d-grid gap-2">
                                  @foreach ($destinationOptions as $destinationValue => $destinationOption)
                                    @php
                                      $checkedDestination = $isSubmittedLink ? old('destination', data_get($link, 'destination')) : data_get($link, 'destination');
                                      $destinationIcon = $destinationValue === 'register' ? $registerIcon : $homepageIcon;
                                      $destinationIconTone = $destinationValue === 'register' ? 'icon-tile-warning' : 'icon-tile-info';
                                    @endphp
                                    <div>
                                      <input
                                        class="btn-check destination-input"
                                        type="radio"
                                        name="destination"
                                        id="destination_{{ $destinationValue }}_{{ $linkId }}"
                                        value="{{ $destinationValue }}"
                                        data-preview-destination
                                        @checked($checkedDestination === $destinationValue)
                                      />
                                      <label class="destination-option" for="destination_{{ $destinationValue }}_{{ $linkId }}">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                          <div class="d-flex gap-3">
                                            <span class="icon-tile {{ $destinationIconTone }}">
                                              <i class="icon-base ti {{ $destinationIcon }}"></i>
                                            </span>
                                            <div>
                                              <div class="fw-semibold text-heading">{{ $destinationOption['label'] }}</div>
                                              <small class="text-muted">{{ $destinationOption['description'] }}</small>
                                            </div>
                                          </div>
                                          <div class="text-primary">
                                            <i class="icon-base ti {{ $activeIcon }}"></i>
                                          </div>
                                        </div>
                                      </label>
                                    </div>
                                  @endforeach
                                </div>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label" for="source_{{ $linkId }}">Where will this link be shared?</label>
                                <input type="text" class="form-control" id="source_{{ $linkId }}" name="source" list="campaign-link-source-options" value="{{ $isSubmittedLink ? old('source') : data_get($link, 'source') }}" data-preview-source />
                              </div>
                              <div class="col-md-6">
                                <label class="form-label" for="medium_{{ $linkId }}">Where exactly will it appear?</label>
                                <input type="text" class="form-control" id="medium_{{ $linkId }}" name="medium" list="campaign-link-medium-options" value="{{ $isSubmittedLink ? old('medium') : data_get($link, 'medium') }}" data-preview-medium />
                              </div>
                              <div class="col-md-6">
                                <label class="form-label" for="campaign_{{ $linkId }}">Link group name</label>
                                <input type="text" class="form-control" id="campaign_{{ $linkId }}" name="campaign" value="{{ $isSubmittedLink ? old('campaign') : data_get($link, 'campaign') }}" data-preview-campaign />
                              </div>
                              <div class="col-md-6">
                                <label class="form-label" for="utm_content_{{ $linkId }}">Optional version name</label>
                                <input type="text" class="form-control" id="utm_content_{{ $linkId }}" name="utm_content" value="{{ $isSubmittedLink ? old('utm_content') : data_get($link, 'utm_content') }}" data-preview-content />
                              </div>
                              <div class="col-12">
                                <label class="form-label" for="notes_{{ $linkId }}">Internal note</label>
                                <textarea class="form-control" id="notes_{{ $linkId }}" name="notes" rows="3">{{ $isSubmittedLink ? old('notes') : data_get($link, 'notes') }}</textarea>
                              </div>
                            </div>

                            <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" role="switch" id="is_active_{{ $linkId }}" name="is_active" value="1" @checked(($isSubmittedLink ? old('is_active', data_get($link, 'is_active')) : data_get($link, 'is_active'))) />
                              <label class="form-check-label" for="is_active_{{ $linkId }}">Active link</label>
                            </div>

                            <div class="preview-box p-3" data-campaign-link-preview data-homepage-url="{{ $destinationBaseUrls['homepage'] }}" data-register-url="{{ $destinationBaseUrls['register'] }}">
                              <small class="text-muted d-block mb-2">Preview after changes</small>
                              <div class="mb-2">
                                <span class="text-muted d-block small">People will be sent to</span>
                                <span class="fw-semibold" data-preview-destination-label>{{ data_get($link, 'destination_label') }}</span>
                              </div>
                              <div class="mb-2">
                                <span class="text-muted d-block small">Main page</span>
                                <code class="small" data-preview-base-url>{{ data_get($link, 'base_url') }}</code>
                              </div>
                              <div class="url-box p-3">
                                <span class="text-muted d-block small mb-1">Final link to share</span>
                                <code data-preview-generated-url>{{ data_get($link, 'generated_url') }}</code>
                              </div>
                            </div>

                            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                              <button type="submit" class="btn btn-primary">
                                <i class="icon-base ti {{ $saveIcon }} me-2"></i>
                                Save changes
                              </button>
                              <button
                                type="submit"
                                class="btn btn-label-danger"
                                form="delete_campaign_link_{{ $linkId }}"
                                onclick="return confirm('Delete this campaign link? This action cannot be undone.');"
                              >
                                <i class="icon-base ti tabler-trash me-2"></i>
                                Delete link
                              </button>
                            </div>
                          </form>

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

    <datalist id="campaign-link-source-options">
      @foreach ($sourceSuggestions as $sourceSuggestion)
        <option value="{{ $sourceSuggestion }}"></option>
      @endforeach
    </datalist>

    <datalist id="campaign-link-medium-options">
      @foreach ($mediumSuggestions as $mediumSuggestion)
        <option value="{{ $mediumSuggestion }}"></option>
      @endforeach
    </datalist>
  </div>
@endsection
