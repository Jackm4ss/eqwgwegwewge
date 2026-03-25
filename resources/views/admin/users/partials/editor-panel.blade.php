@once
  @push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/fonts/flag-icons.css') }}" />
    <style>
      .user-editor-shell {
        position: relative;
        background: var(--bs-paper-bg, #fff);
        border: 1px solid rgba(67, 89, 113, 0.14);
        border-radius: 1.75rem;
        padding: clamp(1.5rem, 2vw, 2.5rem);
        box-shadow: 0 1.25rem 3rem rgba(67, 89, 113, 0.12);
      }

      .user-editor-heading {
        max-width: 44rem;
        margin: 0 auto 2rem;
        text-align: center;
      }

      .user-editor-section,
      .user-editor-sidebar-card,
      .user-editor-profile {
        border: 1px solid rgba(67, 89, 113, 0.12);
        border-radius: 1.25rem;
      }

      .user-editor-section,
      .user-editor-sidebar-card {
        background: var(--bs-paper-bg, #fff);
        padding: 1.35rem;
      }

      .user-editor-section-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
      }

      .user-editor-section-head p {
        max-width: 34rem;
      }

      .user-editor-profile {
        background:
          radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.18), transparent 42%),
          linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08), rgba(255, 255, 255, 0.94));
        padding: 1.5rem;
      }

      .user-editor-profile .avatar {
        --bs-avatar-size: 4.25rem;
      }

      .user-editor-profile-meta {
        display: grid;
        gap: 0.9rem;
      }

      .user-editor-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.82);
        color: var(--bs-heading-color);
        font-size: 0.875rem;
        font-weight: 500;
      }

      .user-editor-flag {
        width: 1.35rem;
        height: 1rem;
        border-radius: 0.2rem;
        flex: 0 0 auto;
        box-shadow: inset 0 0 0 1px rgba(67, 89, 113, 0.14);
      }

      .user-editor-sidebar-card dl {
        margin-bottom: 0;
      }

      .user-editor-sidebar-card dt {
        color: var(--bs-body-color);
        font-weight: 600;
      }

      .user-editor-sidebar-card dd {
        margin-bottom: 0.9rem;
        color: var(--bs-heading-color);
        word-break: break-word;
      }

      .user-editor-sidebar-card dd:last-child {
        margin-bottom: 0;
      }

      .user-editor-shell .form-label {
        font-weight: 600;
      }

      .user-editor-shell .form-control,
      .user-editor-shell .form-select {
        min-height: calc(2.75rem + 2px);
      }

      .user-editor-shell .form-control[readonly],
      .user-editor-shell .form-select:disabled {
        color: var(--bs-heading-color);
      }

      .user-editor-readonly .form-control[readonly],
      .user-editor-readonly .form-select:disabled {
        background: rgba(67, 89, 113, 0.06);
        border-color: rgba(67, 89, 113, 0.12);
        box-shadow: none;
        cursor: default;
      }

      .user-editor-modal .modal-dialog {
        max-width: 1120px;
      }

      .user-editor-modal .modal-content {
        background: transparent;
        border: 0;
        box-shadow: none;
      }

      .user-editor-modal .btn-close {
        position: absolute;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 2;
        padding: 0.85rem;
        border-radius: 0.9rem;
        background-color: var(--bs-paper-bg, #fff);
        box-shadow: 0 0.65rem 1.5rem rgba(67, 89, 113, 0.18);
        opacity: 1;
      }

      .user-editor-inline-note {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.6rem 0.9rem;
        border-radius: 0.9rem;
        background: rgba(67, 89, 113, 0.06);
      }

      @media (max-width: 1199.98px) {
        .user-editor-modal .btn-close {
          top: 0.75rem;
          right: 0.75rem;
        }

        .user-editor-shell {
          padding: 1.25rem;
          border-radius: 1.25rem;
        }
      }
    </style>
  @endpush

  @push('page-scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const bindCountryFlagSelect = function (element) {
          if (!element || typeof window.jQuery === 'undefined' || !window.jQuery.fn.select2) {
            return;
          }

          const $select = window.jQuery(element);
          const renderCountryOption = function (option) {
            if (!option.id) {
              return option.text;
            }

            const flagCode = option.element?.dataset.flag || 'xx';

            return `
              <span class="d-flex align-items-center gap-2">
                <span class="fi fis fi-${flagCode} user-editor-flag"></span>
                <span>${option.text}</span>
              </span>
            `;
          };

          if (!$select.parent().hasClass('position-relative')) {
            $select.wrap('<div class="position-relative"></div>');
          }

          if ($select.data('select2')) {
            $select.prop('disabled', element.disabled);
            $select.trigger('change.select2');
            return;
          }

          const modalParent = $select.closest('.modal');

          $select.select2({
            dropdownParent: modalParent.length ? modalParent : $select.parent(),
            minimumResultsForSearch: 8,
            templateResult: renderCountryOption,
            templateSelection: renderCountryOption,
            escapeMarkup: function (markup) {
              return markup;
            },
          });
        };

        const bindIdentityTypeControl = function (shell) {
          if (!shell || shell.dataset.identityTypeBound === 'true') {
            return;
          }

          const countrySelect = shell.querySelector('[data-user-country-select]');
          const identityHidden = shell.querySelector('[data-user-identity-hidden]');
          const identitySelect = shell.querySelector('[data-user-identity-display]');
          const identityNote = shell.querySelector('[data-user-identity-note]');

          if (!countrySelect || !identityHidden || !identitySelect) {
            return;
          }

          const setIdentitySelectDisabled = function (disabled) {
            identitySelect.disabled = disabled;

            if (disabled) {
              identitySelect.setAttribute('disabled', 'disabled');
              return;
            }

            identitySelect.removeAttribute('disabled');
          };

          const currentCountryValue = String(countrySelect.value || '').toUpperCase();
          shell.dataset.lastMalaysiaIdentityType = currentCountryValue === 'MY'
            ? (identityHidden.value || identitySelect.value || 'national_id')
            : 'national_id';
          shell.dataset.lastIdentityCountry = currentCountryValue;

          const sync = function () {
            const countryValue = String(countrySelect.value || '').toUpperCase();
            const isReadonly = shell.dataset.userEditorMode === 'view';
            const previousCountryValue = shell.dataset.lastIdentityCountry || '';

            if (countryValue !== 'MY') {
              if (previousCountryValue === 'MY') {
                shell.dataset.lastMalaysiaIdentityType = identitySelect.value || identityHidden.value || 'national_id';
              }

              identityHidden.value = 'passport';
              identitySelect.value = 'passport';
              setIdentitySelectDisabled(true);
              shell.dataset.lastIdentityCountry = countryValue;

              if (identityNote) {
                identityNote.textContent = 'For participants outside Malaysia, the system automatically uses Passport.';
                identityNote.style.display = '';
              }

              return;
            }

            const malaysiaIdentityType = shell.dataset.lastMalaysiaIdentityType || 'national_id';
            identitySelect.value = malaysiaIdentityType;
            setIdentitySelectDisabled(isReadonly);
            identityHidden.value = identitySelect.value || 'national_id';
            shell.dataset.lastIdentityCountry = countryValue;

            if (identityNote) {
              identityNote.textContent = '';
              identityNote.style.display = 'none';
            }
          };

          countrySelect.addEventListener('change', sync);

          if (typeof window.jQuery !== 'undefined') {
            window.jQuery(countrySelect).on('select2:select.userIdentity select2:clear.userIdentity', sync);
          }

          identitySelect.addEventListener('change', function () {
            identityHidden.value = identitySelect.value || 'passport';

            if (String(countrySelect.value || '').toUpperCase() === 'MY') {
              shell.dataset.lastMalaysiaIdentityType = identityHidden.value || 'national_id';
            }
          });

          shell.dataset.identityTypeBound = 'true';
          shell._syncIdentityTypeControl = sync;
          sync();
        };

        window.refreshUserEditorIdentityRules = function (scope) {
          const shells = scope
            ? (scope.matches?.('[data-user-editor-shell]') ? [scope] : scope.querySelectorAll?.('[data-user-editor-shell]') || [])
            : document.querySelectorAll('[data-user-editor-shell]');

          Array.from(shells).forEach(function (shell) {
            bindIdentityTypeControl(shell);
            shell._syncIdentityTypeControl?.();
          });
        };

        window.refreshUserEditorCountrySelects = function (scope) {
          const selects = scope
            ? (scope.matches?.('[data-user-country-select]') ? [scope] : scope.querySelectorAll?.('[data-user-country-select]') || [])
            : document.querySelectorAll('[data-user-country-select]');

          Array.from(selects).forEach(function (select) {
            bindCountryFlagSelect(select);
          });
        };

        window.refreshUserEditorIdentityRules();
        window.refreshUserEditorCountrySelects();
      });
    </script>
  @endpush
@endonce

@php
  $mode = $mode ?? 'edit';
  $isReadonly = $mode === 'view';
  $isModal = $isModal ?? false;
  $oldInputEnabled = $oldInputEnabled ?? false;
  $viewErrors = app('view')->shared('errors');

  if (!$viewErrors instanceof \Illuminate\Support\ViewErrorBag) {
    $viewErrors = new \Illuminate\Support\ViewErrorBag();
  }

  $cancelUrl = $cancelUrl ?? route('admin.users.index');
  $headingId = $headingId ?? null;
  $fieldIdPrefix = $fieldIdPrefix ?? ('user-editor-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($user['user_id'] ?? 'user')));
  $ticket = is_array($user['ticket'] ?? null) ? $user['ticket'] : [];
  $countryCode = strtolower(trim((string) ($user['country'] ?? '')));
  $countryFlagClass = preg_match('/^[a-z]{2}$/', $countryCode) ? $countryCode : 'xx';
  $accountStatus = (string) ($user['account_status'] ?? 'pending_verification');
  $verificationStatus = (string) ($user['verification_status'] ?? 'unverified');
  $attendanceStatus = (string) data_get($ticket, 'attendance_status', $user['attendance_status'] ?? 'not_checked_in');
  $ticketCode = (string) data_get($ticket, 'ticket_code', $user['ticket_code'] ?? '-');
  $identityType = (string) ($user['identity_type'] ?? 'passport');
  $countryLabel = (string) ($user['country_label'] ?? ($user['country'] ?? '-'));
  $phoneDisplay = (string) ($user['phone_number'] ?? '');

  if ($phoneDisplay === '' && filled($user['phone_country_code'] ?? null) && filled($user['phone_national_number'] ?? null)) {
    $phoneDisplay = (string) $user['phone_country_code'] . (string) $user['phone_national_number'];
  }

  $countryOptions = [
    'AU' => 'Australia',
    'CN' => 'China',
    'DE' => 'Germany',
    'FR' => 'France',
    'GB' => 'United Kingdom',
    'ID' => 'Indonesia',
    'IN' => 'India',
    'JP' => 'Japan',
    'KR' => 'South Korea',
    'MY' => 'Malaysia',
    'PH' => 'Philippines',
    'SG' => 'Singapore',
    'TH' => 'Thailand',
    'US' => 'United States',
    'VN' => 'Vietnam',
  ];

  $initials = static function (?string $name): string {
    $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $letters = collect($parts)
      ->take(2)
      ->map(fn(string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
      ->implode('');

    return $letters !== '' ? $letters : 'U';
  };

  $fieldValue = static function (string $key, mixed $default = '') use ($oldInputEnabled, $user) {
    return $oldInputEnabled ? old($key, data_get($user, $key, $default)) : data_get($user, $key, $default);
  };

  $currentCountry = strtoupper((string) $fieldValue('country', $user['country'] ?? ''));

  if ($currentCountry !== '' && !array_key_exists($currentCountry, $countryOptions)) {
    $countryOptions[$currentCountry] = $countryLabel !== '' ? $countryLabel : $currentCountry;
  }

  asort($countryOptions);

  $invalidClass = static function (string $key) use ($oldInputEnabled, $viewErrors): string {
    return $oldInputEnabled && $viewErrors->has($key) ? 'is-invalid' : '';
  };

  $errorMessage = static function (string $key) use ($oldInputEnabled, $viewErrors): ?string {
    return $oldInputEnabled ? $viewErrors->first($key) : null;
  };

  $accountMeta = match ($accountStatus) {
    'active' => ['label' => 'Active', 'class' => 'bg-label-primary', 'icon' => 'tabler-badge'],
    'blocked' => ['label' => 'Blocked', 'class' => 'bg-label-danger', 'icon' => 'tabler-lock'],
    default => ['label' => 'Pending', 'class' => 'bg-label-warning', 'icon' => 'tabler-loader'],
  };

  $verificationMeta = match ($verificationStatus) {
    'verified' => ['label' => 'Verified', 'class' => 'bg-label-success', 'icon' => 'tabler-circle-check'],
    default => ['label' => 'Unverified', 'class' => 'bg-label-secondary', 'icon' => 'tabler-shield-question'],
  };

  $attendanceMeta = match ($attendanceStatus) {
    'checked_in' => ['label' => 'Checked In', 'class' => 'bg-label-success', 'icon' => 'tabler-user-check'],
    default => ['label' => 'Not Checked In', 'class' => 'bg-label-warning', 'icon' => 'tabler-clock-hour-4'],
  };
@endphp

<div class="user-editor-shell {{ $isReadonly ? 'user-editor-readonly' : '' }}" data-user-editor-shell
  data-user-editor-mode="{{ $mode }}">
  <div class="user-editor-heading">
    <span class="badge rounded-pill bg-label-primary px-3 py-2 mb-3" data-user-editor-kicker>
      {{ $isReadonly ? 'Participant Overview' : 'Participant Update' }}
    </span>
    <h3 class="mb-2" data-user-editor-title @if ($headingId) id="{{ $headingId }}" @endif>
      {{ $isReadonly ? 'Participant Details' : 'Edit Participant' }}</h3>
    <p class="text-muted mb-0" data-user-editor-subtitle>
      {{ $isReadonly ? 'Review participant, ticket, and attendance details without changing Firestore data.' : 'Changes will update the participant data immediately.' }}
    </p>
  </div>

  <div class="row g-4 align-items-start">
    <div class="col-12 col-xl-8">
      <form method="POST" action="{{ route('admin.users.update', $user['user_id']) }}" class="d-grid gap-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="_modal_user_id" value="{{ $user['user_id'] }}" />
        <input type="hidden" name="_modal_mode" value="edit" />
        <input type="hidden" name="_simple_phone_mode" value="1" />
        <input type="hidden" name="phone_country_code" value="" />
        <input type="hidden" name="phone_national_number" value="" />
        <input type="hidden" name="identity_type" value="{{ $fieldValue('identity_type', $identityType) }}"
          data-user-identity-hidden />

        <div class="user-editor-section">
          <div class="user-editor-section-head">
            <div>
              <h5 class="mb-1">Participant Information</h5>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-full-name">Full Name</label>
              <input type="text" class="form-control {{ $invalidClass('full_name') }}"
                id="{{ $fieldIdPrefix }}-full-name" name="full_name" value="{{ $fieldValue('full_name') }}" {{ $isReadonly ? 'readonly' : '' }} data-user-input required />
              @if ($errorMessage('full_name'))
                <div class="invalid-feedback d-block">{{ $errorMessage('full_name') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-email">Email</label>
              <input type="email" class="form-control {{ $invalidClass('email') }}" id="{{ $fieldIdPrefix }}-email"
                name="email" value="{{ $fieldValue('email') }}" {{ $isReadonly ? 'readonly' : '' }} data-user-input
                required />
              @if ($errorMessage('email'))
                <div class="invalid-feedback d-block">{{ $errorMessage('email') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-phone-number">Phone Number / WhatsApp</label>
              <input type="text" class="form-control {{ $invalidClass('phone_number') }}"
                id="{{ $fieldIdPrefix }}-phone-number" name="phone_number"
                value="{{ $fieldValue('phone_number', $phoneDisplay) }}" placeholder="+628123456789" {{ $isReadonly ? 'readonly' : '' }} data-user-input />
              @if ($errorMessage('phone_number'))
                <div class="invalid-feedback d-block">{{ $errorMessage('phone_number') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-country">Country</label>
              <select class="form-select {{ $invalidClass('country') }}" id="{{ $fieldIdPrefix }}-country"
                name="country" {{ $isReadonly ? 'disabled' : '' }} data-user-input data-user-country-select required>
                @foreach ($countryOptions as $countryCodeOption => $countryName)
                  <option value="{{ $countryCodeOption }}" data-flag="{{ strtolower($countryCodeOption) }}"
                    @selected($fieldValue('country', $user['country'] ?? '') === $countryCodeOption)>{{ $countryName }}</option>
                @endforeach
              </select>
              @if ($errorMessage('country'))
                <div class="invalid-feedback d-block">{{ $errorMessage('country') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-identity-type-display">Identity Document</label>
              <select class="form-select {{ $invalidClass('identity_type') }}"
                id="{{ $fieldIdPrefix }}-identity-type-display" {{ $isReadonly ? 'disabled' : '' }} data-user-input
                data-user-identity-display>
                <option value="passport" @selected($fieldValue('identity_type', $identityType) === 'passport')>Passport
                </option>
                <option value="national_id" @selected($fieldValue('identity_type', $identityType) === 'national_id')>IC /
                  National ID</option>
              </select>
              <small class="text-muted d-block mt-1" data-user-identity-note></small>
              @if ($errorMessage('identity_type'))
                <div class="invalid-feedback d-block">{{ $errorMessage('identity_type') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-identity-number">Identity Number</label>
              <input type="text" class="form-control {{ $invalidClass('identity_number') }}"
                id="{{ $fieldIdPrefix }}-identity-number" name="identity_number"
                value="{{ $fieldValue('identity_number') }}" {{ $isReadonly ? 'readonly' : '' }} data-user-input
                required />
              @if ($errorMessage('identity_number'))
                <div class="invalid-feedback d-block">{{ $errorMessage('identity_number') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-account-status">Account Status</label>
              <select class="form-select {{ $invalidClass('account_status') }}" id="{{ $fieldIdPrefix }}-account-status"
                name="account_status" {{ $isReadonly ? 'disabled' : '' }} data-user-input required>
                <option value="pending_verification" @selected($fieldValue('account_status', $accountStatus) === 'pending_verification')>Pending Verification</option>
                <option value="active" @selected($fieldValue('account_status', $accountStatus) === 'active')>Active
                </option>
                <option value="blocked" @selected($fieldValue('account_status', $accountStatus) === 'blocked')>Blocked
                </option>
              </select>
              @if ($errorMessage('account_status'))
                <div class="invalid-feedback d-block">{{ $errorMessage('account_status') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-verification-status">Verification Status</label>
              <select class="form-select {{ $invalidClass('verification_status') }}"
                id="{{ $fieldIdPrefix }}-verification-status" name="verification_status" {{ $isReadonly ? 'disabled' : '' }} data-user-input required>
                <option value="unverified" @selected($fieldValue('verification_status', $verificationStatus) === 'unverified')>Unverified</option>
                <option value="verified" @selected($fieldValue('verification_status', $verificationStatus) === 'verified')>Verified</option>
              </select>
              @if ($errorMessage('verification_status'))
                <div class="invalid-feedback d-block">{{ $errorMessage('verification_status') }}</div>
              @endif
            </div>
          </div>
        </div>

        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 pt-1">
          <button type="submit" class="btn btn-primary px-5" data-user-edit-only @if ($isReadonly) style="display:none;"
          @endif>
            Save Changes
          </button>

          @if ($isModal)
            <button type="button" class="btn btn-label-secondary px-5" data-user-edit-only @if ($isReadonly)
            style="display:none;" @endif data-bs-dismiss="modal">
              Cancel
            </button>
            <button type="button" class="btn btn-label-secondary px-5" data-user-view-only @if (!$isReadonly)
            style="display:none;" @endif data-bs-dismiss="modal">
              Close
            </button>
          @else
            <a href="{{ $cancelUrl }}" class="btn btn-label-secondary px-5">Back</a>
          @endif
        </div>
      </form>
    </div>

    <div class="col-12 col-xl-4">
      <div class="d-grid gap-4">
        <div class="user-editor-profile">
          <div class="d-flex align-items-start gap-3 mb-4">
            <div class="avatar">
              <span
                class="avatar-initial rounded-circle bg-label-primary">{{ $initials($user['full_name'] ?? null) }}</span>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-1">{{ $user['full_name'] ?? '-' }}</h5>
              <p class="text-muted mb-2">{{ $user['email'] ?? '-' }}</p>
              <div class="d-flex flex-wrap gap-2">
                <span class="badge {{ $accountMeta['class'] }}">{{ $accountMeta['label'] }}</span>
                <span class="badge {{ $verificationMeta['class'] }}">{{ $verificationMeta['label'] }}</span>
                <span class="badge {{ $attendanceMeta['class'] }}">{{ $attendanceMeta['label'] }}</span>
              </div>
            </div>
          </div>

          <div class="user-editor-profile-meta">
            <span class="user-editor-meta-chip">
              <span class="fi fis fi-{{ $countryFlagClass }} user-editor-flag"></span>
              {{ $countryLabel }}
            </span>
            <span class="user-editor-meta-chip">
              <i class="icon-base ti tabler-id-badge-2"></i>
              Document {{ ucfirst(str_replace('_', ' ', $identityType)) }}
            </span>
          </div>
        </div>

        <div class="user-editor-sidebar-card">
          <div class="mb-3">
            <div>
              <h5 class="mb-1">Ticket Snapshot</h5>
              <p class="text-muted mb-0">Summary of the participant's active ticket.</p>
            </div>
          </div>

          <dl class="row gy-2">
            <dt class="col-5">Ticket Code</dt>
            <dd class="col-7">{{ $ticketCode !== '' ? $ticketCode : '-' }}</dd>
          </dl>

          <hr class="my-4" />

          <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
              <h5 class="mb-1">QR Actions</h5>
              <p class="text-muted mb-0">Reset attendance or generate a new QR code when needed.</p>
            </div>
            <span class="user-editor-inline-note text-muted small" data-user-view-only @if (!$isReadonly)
            style="display:none;" @endif>
              <i class="icon-base ti tabler-eye"></i>
              Available in edit mode
            </span>
          </div>

          <div class="d-grid gap-3" data-user-edit-only @if ($isReadonly) style="display:none;" @endif>
            <form method="POST" action="{{ route('admin.users.qr.reset', $user['user_id']) }}">
              @csrf
              <button type="submit" class="btn btn-label-warning w-100">Reset Attendance</button>
            </form>

            <form method="POST" action="{{ route('admin.users.qr.regenerate', $user['user_id']) }}">
              @csrf
              <button type="submit" class="btn btn-label-primary w-100">Generate QR</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
