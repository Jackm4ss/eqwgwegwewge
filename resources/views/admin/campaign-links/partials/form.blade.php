@php
  $formMode = $formMode ?? 'create';
  $formMethod = strtoupper($formMethod ?? 'POST');
  $formAction = $formAction ?? '#';
  $formId = $formId ?? 'campaign-link-form';
  $fieldPrefix = $fieldPrefix ?? $formId;
  $link = $link ?? [];
  $submitLabel = $submitLabel ?? 'Save link';
  $submitClass = $submitClass ?? 'btn btn-primary';
  $errorsEnabled = $errorsEnabled ?? false;
  $deleteAction = $deleteAction ?? null;
  $deleteFormId = $deleteFormId ?? null;
  $homepageUrl = rtrim((string) ($homepageUrl ?? config('app.frontend_homepage_url', config('app.url'))), '/');
  $registerUrl = rtrim((string) ($registerUrl ?? ($homepageUrl.'/register')), '/');
  $slugPrefix = $homepageUrl !== '' ? $homepageUrl.'/' : '/';
@endphp

<form method="POST" action="{{ $formAction }}" data-campaign-link-form class="d-grid gap-4">
  @csrf
  @if ($formMethod !== 'POST')
    @method($formMethod)
  @endif

  @if ($formMode === 'create')
    <input type="hidden" name="_create_campaign_link" value="1" />
  @else
    <input type="hidden" name="campaign_link_id" value="{{ data_get($link, 'id') }}" />
  @endif

  <div>
    <label class="form-label" for="name_{{ $fieldPrefix }}">Internal link name</label>
    <input
      type="text"
      class="form-control @if ($errorsEnabled) @error('name') is-invalid @enderror @endif"
      id="name_{{ $fieldPrefix }}"
      name="name"
      value="{{ data_get($link, 'name') }}"
      placeholder="Songkran Instagram Bio"
    />
    <small class="text-muted">Internal admin label only.</small>
    @if ($errorsEnabled)
      @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    @endif
  </div>

  <div>
    <label class="form-label" for="slug_{{ $fieldPrefix }}">Public slug after the slash</label>
    <div class="input-group">
      <span class="input-group-text">{{ $slugPrefix }}</span>
      <input
        type="text"
        class="form-control @if ($errorsEnabled) @error('slug') is-invalid @enderror @endif"
        id="slug_{{ $fieldPrefix }}"
        name="slug"
        value="{{ data_get($link, 'slug') }}"
        placeholder="fb"
        data-preview-slug
        autocomplete="off"
      />
    </div>
    <small class="text-muted">Mandatory public slug. Example: <code>/fb</code>, <code>/wa</code>, <code>/reg</code>.</small>
    @if ($errorsEnabled)
      @error('slug')
        <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
    @endif
  </div>

  <div>
    <label class="form-label d-block">Destination</label>
    <div class="row g-3">
      @foreach ($destinationOptions as $destinationValue => $destinationOption)
        <div class="col-12">
          <input
            class="btn-check destination-input"
            type="radio"
            name="destination"
            id="destination_{{ $fieldPrefix }}_{{ $destinationValue }}"
            value="{{ $destinationValue }}"
            data-preview-destination
            @checked(data_get($link, 'destination', 'homepage') === $destinationValue)
          />
          <label class="destination-option" for="destination_{{ $fieldPrefix }}_{{ $destinationValue }}">
            <span class="fw-semibold d-block mb-1">{{ $destinationOption['label'] }}</span>
            <small class="text-muted">{{ $destinationOption['description'] }}</small>
          </label>
        </div>
      @endforeach
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label" for="source_{{ $fieldPrefix }}">UTM source</label>
      <input
        type="text"
        class="form-control @if ($errorsEnabled) @error('source') is-invalid @enderror @endif"
        id="source_{{ $fieldPrefix }}"
        name="source"
        list="campaign-link-source-options"
        value="{{ data_get($link, 'source', 'instagram') }}"
        placeholder="instagram"
        data-preview-source
      />
      <small class="text-muted">Example: instagram, whatsapp, facebook.</small>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="medium_{{ $fieldPrefix }}">UTM medium</label>
      <input
        type="text"
        class="form-control @if ($errorsEnabled) @error('medium') is-invalid @enderror @endif"
        id="medium_{{ $fieldPrefix }}"
        name="medium"
        list="campaign-link-medium-options"
        value="{{ data_get($link, 'medium', 'bio') }}"
        placeholder="bio"
        data-preview-medium
      />
      <small class="text-muted">Example: bio, post, story, ads.</small>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="campaign_{{ $fieldPrefix }}">UTM campaign</label>
      <input
        type="text"
        class="form-control @if ($errorsEnabled) @error('campaign') is-invalid @enderror @endif"
        id="campaign_{{ $fieldPrefix }}"
        name="campaign"
        value="{{ data_get($link, 'campaign', 'songkran2026') }}"
        placeholder="songkran2026"
        data-preview-campaign
      />
      <small class="text-muted">Group campaign name.</small>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="utm_content_{{ $fieldPrefix }}">UTM content</label>
      <input
        type="text"
        class="form-control @if ($errorsEnabled) @error('utm_content') is-invalid @enderror @endif"
        id="utm_content_{{ $fieldPrefix }}"
        name="utm_content"
        value="{{ data_get($link, 'utm_content') }}"
        placeholder="creative-a"
        data-preview-content
      />
      <small class="text-muted">Optional creative/version label.</small>
    </div>
    <div class="col-12">
      <label class="form-label" for="notes_{{ $fieldPrefix }}">Internal note</label>
      <textarea
        class="form-control @if ($errorsEnabled) @error('notes') is-invalid @enderror @endif"
        id="notes_{{ $fieldPrefix }}"
        name="notes"
        rows="3"
        placeholder="Main campaign link for this push"
      >{{ data_get($link, 'notes') }}</textarea>
      <small class="text-muted">Optional internal note for the admin team.</small>
    </div>
  </div>

  <div class="form-check form-switch">
    <input
      class="form-check-input"
      type="checkbox"
      role="switch"
      id="is_active_{{ $fieldPrefix }}"
      name="is_active"
      value="1"
      @checked(data_get($link, 'is_active', true))
    />
    <label class="form-check-label" for="is_active_{{ $fieldPrefix }}">Active public shortlink</label>
  </div>

  <div
    class="preview-box p-3"
    data-campaign-link-preview
    data-short-base-url="{{ $homepageUrl }}"
    data-homepage-url="{{ $homepageUrl }}"
    data-register-url="{{ $registerUrl }}"
  >
    <small class="text-muted d-block mb-3">Preview</small>
    <div class="mb-3">
      <span class="text-muted d-block small">Public short URL</span>
      <div class="url-box p-3 mt-1">
        <code data-preview-short-url>{{ data_get($link, 'short_url', $homepageUrl) }}</code>
      </div>
    </div>
    <div>
      <span class="text-muted d-block small">Redirect destination</span>
      <div class="url-box p-3 mt-1">
        <code data-preview-final-url>{{ data_get($link, 'final_url', data_get($link, 'base_url', $homepageUrl)) }}</code>
      </div>
    </div>
  </div>

  <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
    <button type="submit" class="{{ $submitClass }}">{{ $submitLabel }}</button>

    @if ($deleteAction && $deleteFormId)
      <button
        type="submit"
        class="btn btn-label-danger"
        form="{{ $deleteFormId }}"
        onclick="return confirm('Delete this campaign link? This action cannot be undone.');"
      >
        Delete link
      </button>
    @endif
  </div>
</form>
