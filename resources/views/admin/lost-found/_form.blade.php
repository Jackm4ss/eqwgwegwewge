@php
  $item = $item ?? null;
  $statusOptions = $statusOptions ?? [];
  $storageReady = $storageReady ?? true;
  $submitLabel = $submitLabel ?? 'Save Item';
  $cancelRoute = $cancelRoute ?? route('admin.lost-found.index');
  $imageUrl = $imageUrl ?? static fn (?string $path): ?string => null;
  $contactValue = $contactValue ?? static fn (?string $value): string => (string) $value;
  $contactCountryCode = $contactCountryCode ?? static fn (?string $value): string => '+60';
  $contactPhoneNumber = $contactPhoneNumber ?? static fn (?string $value): string => '';
  $previewImage = old('image')
    ? null
    : ($item ? $imageUrl($item->image_path) : null);
@endphp

@once
  @push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/flatpickr/flatpickr.css') }}" />
    <style>
      .lost-found-found-date.flatpickr-input[readonly] {
        background-color: var(--bs-body-bg);
      }
    </style>
  @endpush

  @push('vendor-scripts')
    <script src="{{ asset('assets-vuexy/vendor/libs/flatpickr/flatpickr.js') }}"></script>
  @endpush

  @push('page-scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const foundDateInput = document.querySelector('[data-lost-found-found-date]');

        if (typeof window.flatpickr === 'function' && foundDateInput && !foundDateInput.disabled) {
          if (foundDateInput._flatpickr) {
            foundDateInput._flatpickr.destroy();
          }

          window.flatpickr(foundDateInput, {
            altInput: true,
            altFormat: 'd M Y',
            allowInput: true,
            clickOpens: true,
            dateFormat: 'Y-m-d',
            disableMobile: true,
            onReady: function (_, __, instance) {
              const altInput = instance?.altInput;

              if (!altInput) {
                return;
              }

              altInput.classList.add('lost-found-found-date');
              altInput.setAttribute('aria-label', 'Found Date');
              altInput.setAttribute('placeholder', 'Select found date');
            },
          });
        }

        document.querySelectorAll('[data-lost-found-image-input]').forEach(function (input) {
          input.addEventListener('change', function (event) {
            const target = event.currentTarget;
            const preview = document.querySelector('[data-lost-found-image-preview]');
            const helper = document.querySelector('[data-lost-found-image-helper]');

            if (!preview || !(target instanceof HTMLInputElement)) {
              return;
            }

            const file = target.files?.[0];

            if (!file) {
              return;
            }

            const objectUrl = URL.createObjectURL(file);

            preview.src = objectUrl;
            preview.classList.remove('d-none');

            if (helper) {
              helper.textContent = 'Preview is using the newly selected file. The final image will be compressed automatically when saved.';
            }
          });
        });
      });
    </script>
  @endpush
@endonce

<div class="row g-4">
  <div class="col-md-6">
    <label for="title" class="form-label">Title</label>
    <input
      type="text"
      id="title"
      name="title"
      class="form-control @error('title') is-invalid @enderror"
      value="{{ old('title', $item?->title) }}"
      placeholder="Example: Black Leather Wallet"
      maxlength="160"
      required
      @disabled(! $storageReady) />
    @error('title')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label for="status" class="form-label">Status</label>
    <select
      id="status"
      name="status"
      class="form-select @error('status') is-invalid @enderror"
      required
      @disabled(! $storageReady)>
      @foreach ($statusOptions as $option)
        <option value="{{ $option['value'] }}" @selected(old('status', $item?->status ?? 'available') === $option['value'])>
          {{ $option['label'] }}
        </option>
      @endforeach
    </select>
    @error('status')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label for="location_found" class="form-label">Location Found</label>
    <input
      type="text"
      id="location_found"
      name="location_found"
      class="form-control @error('location_found') is-invalid @enderror"
      value="{{ old('location_found', $item?->location_found) }}"
      placeholder="Example: Main stage seating area, row B12"
      maxlength="255"
      required
      @disabled(! $storageReady) />
    @error('location_found')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label for="found_date" class="form-label">Found Date</label>
    <input
      type="text"
      id="found_date"
      name="found_date"
      class="form-control @error('found_date') is-invalid @enderror"
      value="{{ old('found_date', optional($item?->found_date)->format('Y-m-d')) }}"
      placeholder="Select found date"
      autocomplete="off"
      data-lost-found-found-date
      required
      @disabled(! $storageReady) />
    @error('found_date')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-12">
    <label for="description" class="form-label">Description</label>
    <textarea
      id="description"
      name="description"
      class="form-control @error('description') is-invalid @enderror"
      rows="5"
      placeholder="Example: Black wallet containing several bank cards and receipts, found near the main stage seating area."
      maxlength="5000"
      required
      @disabled(! $storageReady)>{{ old('description', $item?->description) }}</textarea>
    @error('description')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-12">
    <label class="form-label">WhatsApp / Phone Number</label>
    <div class="row g-3">
      <div class="col-md-3 col-lg-2">
        <label for="contact_country_code" class="form-label">Country Code</label>
        <select
          id="contact_country_code"
          class="form-select @error('contact_country_code') is-invalid @enderror"
          disabled
          @disabled(! $storageReady)>
          <option value="+60" selected>+60</option>
        </select>
        <input
          type="hidden"
          name="contact_country_code"
          value="{{ old('contact_country_code', $contactCountryCode($item?->contact_info)) }}" />
        @error('contact_country_code')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>
      <div class="col-md-9 col-lg-10">
        <label for="contact_phone_number" class="form-label">Phone Number</label>
        <input
          type="tel"
          id="contact_phone_number"
          name="contact_phone_number"
          class="form-control @error('contact_phone_number') is-invalid @enderror"
          value="{{ old('contact_phone_number', $contactPhoneNumber($item?->contact_info)) }}"
          maxlength="20"
          placeholder="Example: 123456789"
          inputmode="numeric"
          required
          @disabled(! $storageReady) />
        @error('contact_phone_number')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
    <input
      type="hidden"
      name="contact_info"
      value="{{ old('contact_info', $contactValue($item?->contact_info)) }}" />
    @error('contact_info')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-12">
    <label for="image" class="form-label">Upload Image</label>
    <input
      type="file"
      id="image"
      name="image"
      accept=".jpg,.jpeg,.png,image/jpeg,image/png"
      class="form-control @error('image') is-invalid @enderror"
      data-lost-found-image-input
      @required(! $item)
      @disabled(! $storageReady) />
    @error('image')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small class="text-muted d-block mt-2" data-lost-found-image-helper>
      Upload a JPG or PNG file.
    </small>
  </div>

  <div class="col-12">
    <div class="border rounded-3 p-3 bg-body-tertiary">
      <div class="fw-semibold mb-2">Image Preview</div>
      <img
        src="{{ $previewImage }}"
        alt="Lost and found preview"
        class="img-fluid rounded-3 {{ $previewImage ? '' : 'd-none' }}"
        style="max-height: 280px; object-fit: cover;"
        data-lost-found-image-preview />
      @if (! $previewImage)
        <div class="text-muted small" data-lost-found-image-empty-state>No image selected yet.</div>
      @endif
    </div>
  </div>

  <div class="col-12 d-flex flex-wrap gap-2 pt-2">
    <button type="submit" class="btn btn-primary" @disabled(! $storageReady)>{{ $submitLabel }}</button>
    <a href="{{ $cancelRoute }}" class="btn btn-label-secondary">Cancel</a>
  </div>
</div>
