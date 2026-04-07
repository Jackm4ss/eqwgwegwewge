@once
  @push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/fonts/flag-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/sweetalert2/sweetalert2.css') }}" />
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

      .user-editor-shell .select2-container {
        width: 100% !important;
      }

      .user-editor-shell .select2-container .select2-selection--single {
        min-height: calc(2.75rem + 2px);
        border-color: var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        display: flex;
        align-items: center;
        box-shadow: none;
      }

      .user-editor-shell .select2-container .select2-selection__rendered {
        width: 100%;
        min-height: calc(2.75rem + 2px);
        padding-left: 0.875rem;
        padding-right: 2.5rem;
        display: flex;
        align-items: center;
        line-height: 1.2;
        color: var(--bs-body-color);
      }

      .user-editor-shell .select2-container .select2-selection__arrow {
        height: 100%;
        right: 0.5rem;
        width: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .user-editor-shell .select2-container .select2-selection__arrow b {
        margin-top: 0 !important;
      }

      .user-editor-shell .form-select.is-invalid + .select2-container .select2-selection--single {
        border-color: var(--bs-form-invalid-border-color);
      }

      .user-editor-shell .select2-container--disabled .select2-selection--single {
        background: rgba(67, 89, 113, 0.06);
      }

      .user-editor-shell .select2-dropdown {
        border-color: rgba(67, 89, 113, 0.14);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 1rem 2rem rgba(67, 89, 113, 0.14);
      }

      .user-editor-shell .select2-search--dropdown {
        padding: 0.75rem;
        border-bottom: 1px solid rgba(67, 89, 113, 0.12);
      }

      .user-editor-shell .select2-search__field {
        border-radius: 0.75rem !important;
        border-color: rgba(67, 89, 113, 0.16) !important;
        min-height: 2.5rem;
        padding: 0.65rem 0.85rem !important;
      }

      .user-editor-shell .select2-results__group {
        padding: 0.6rem 0.9rem;
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        border-top: 1px solid rgba(67, 89, 113, 0.08);
        background: rgba(245, 247, 250, 0.92);
      }

      .user-editor-shell .select2-results__option--group:first-child .select2-results__group {
        border-top: 0;
      }

      .user-editor-shell .select2-results__option {
        padding: 0.7rem 0.9rem;
      }

      .user-editor-select-option {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
        width: 100%;
      }

      .user-editor-select-text {
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
        flex: 1 1 auto;
      }

      .user-editor-select-primary {
        min-width: 0;
        flex: 1 1 auto;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .user-editor-select-secondary {
        color: var(--bs-secondary-color);
        white-space: nowrap;
        font-weight: 500;
        flex: 0 0 auto;
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

  @push('vendor-scripts')
    <script src="{{ asset('assets-vuexy/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
  @endpush

  @push('page-scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const escapeHtml = function (value) {
          return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        };

        const renderSelectMarkup = function (option, mode) {
          if (!option.id) {
            return escapeHtml(option.text || '');
          }

          const element = option.element;
          const flag = element?.dataset.flag || 'xx';
          const label = element?.dataset.label || option.text || '';
          const secondary = element?.dataset.secondary || '';

          if (mode === 'phone-selection') {
            return `
              <span class="user-editor-select-option">
                <span class="fi fis fi-${escapeHtml(flag)} user-editor-flag"></span>
                <span class="user-editor-select-secondary">${escapeHtml(secondary || label)}</span>
              </span>
            `;
          }

          return `
            <span class="user-editor-select-option">
              <span class="fi fis fi-${escapeHtml(flag)} user-editor-flag"></span>
              <span class="user-editor-select-text">
                <span class="user-editor-select-primary">${escapeHtml(label)}</span>
                ${secondary ? `<span class="user-editor-select-secondary">${escapeHtml(secondary)}</span>` : ''}
              </span>
            </span>
          `;
        };

        const matcher = function (params, data) {
          const term = window.jQuery.trim(params.term || '').toLowerCase();

          if (term === '') {
            return data;
          }

          if (Array.isArray(data.children) && data.children.length > 0) {
            const matchedChildren = data.children
              .map(function (child) {
                return matcher(params, child);
              })
              .filter(function (child) {
                return child !== null;
              });

            if (matchedChildren.length > 0) {
              return {
                ...data,
                children: matchedChildren,
              };
            }
          }

          if (!data.element) {
            return data;
          }

          const haystack = String(data.element.dataset.search || data.text || '').toLowerCase();

          return haystack.includes(term) ? data : null;
        };

        const bindEnhancedSelect = function (element) {
          if (!element || typeof window.jQuery === 'undefined' || !window.jQuery.fn.select2) {
            return;
          }

          const $select = window.jQuery(element);

          if (!$select.parent().hasClass('position-relative')) {
            $select.wrap('<div class="position-relative"></div>');
          }

          if ($select.data('select2')) {
            $select.prop('disabled', element.disabled);
            $select.trigger('change.select2');
            return;
          }

          const modalParent = $select.closest('.modal');
          const kind = element.dataset.userSelectKind || 'country';
          const isPhone = kind === 'phone';
          const searchPlaceholder = element.dataset.placeholder || (isPhone ? 'Search country or dial code...' : 'Search nationality...');

          $select.select2({
            width: '100%',
            dropdownParent: modalParent.length ? modalParent : $select.parent(),
            matcher,
            minimumResultsForSearch: 0,
            placeholder: !element.required ? searchPlaceholder : undefined,
            allowClear: !element.required,
            templateResult: function (option) {
              return renderSelectMarkup(option, isPhone ? 'phone-option' : 'country-option');
            },
            templateSelection: function (option) {
              return renderSelectMarkup(option, isPhone ? 'phone-selection' : 'country-selection');
            },
            escapeMarkup: function (markup) {
              return markup;
            },
          });

          $select.on('select2:open.userSearchPlaceholder', function () {
            const searchField = window.jQuery('.select2-container--open .select2-search__field').last();

            if (searchField.length) {
              searchField.attr('placeholder', searchPlaceholder);
            }
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

        const normalizePhoneCountryCode = function (value) {
          const digits = String(value || '').replace(/\D+/g, '');

          return digits !== '' ? `+${digits}` : '';
        };

        const normalizePhoneNationalNumber = function (value) {
          return String(value || '').replace(/\D+/g, '').replace(/^0+/, '');
        };

        const bindSimplePhoneSubmit = function (shell) {
          if (!shell || shell.dataset.simplePhoneBound === 'true') {
            return;
          }

          const form = shell.querySelector('form');
          const phoneNumberInput = form?.querySelector('input[name="phone_number"]');
          const simplePhoneModeInput = form?.querySelector('input[name="_simple_phone_mode"]');
          const phoneCountryCodeSelect = form?.querySelector('select[name="phone_country_code"]');
          const phoneNationalNumberInput = form?.querySelector('input[name="phone_national_number"]');

          if (!form || !phoneNumberInput || !simplePhoneModeInput || !phoneCountryCodeSelect || !phoneNationalNumberInput) {
            return;
          }

          const syncPhoneNumber = function () {
            const phoneCountryCode = normalizePhoneCountryCode(phoneCountryCodeSelect.value);
            const phoneNationalNumber = normalizePhoneNationalNumber(phoneNationalNumberInput.value);

            phoneNumberInput.value = phoneNationalNumber !== ''
              ? `${phoneCountryCode}${phoneNationalNumber}`
              : '';
          };

          phoneCountryCodeSelect.addEventListener('change', syncPhoneNumber);
          phoneNationalNumberInput.addEventListener('input', syncPhoneNumber);

          if (typeof window.jQuery !== 'undefined') {
            window.jQuery(phoneCountryCodeSelect).on('select2:select.userPhone select2:clear.userPhone', syncPhoneNumber);
          }

          form.addEventListener('submit', function () {
            simplePhoneModeInput.value = '1';
            syncPhoneNumber();
          });

          syncPhoneNumber();
          shell.dataset.simplePhoneBound = 'true';
        };

        window.refreshUserEditorIdentityRules = function (scope) {
          const shells = scope
            ? (scope.matches?.('[data-user-editor-shell]') ? [scope] : scope.querySelectorAll?.('[data-user-editor-shell]') || [])
            : document.querySelectorAll('[data-user-editor-shell]');

          Array.from(shells).forEach(function (shell) {
            bindIdentityTypeControl(shell);
            bindSimplePhoneSubmit(shell);
            shell._syncIdentityTypeControl?.();
          });
        };

        window.refreshUserEditorCountrySelects = function (scope) {
          const selects = scope
            ? (scope.matches?.('[data-user-select-kind]') ? [scope] : scope.querySelectorAll?.('[data-user-select-kind]') || [])
            : document.querySelectorAll('[data-user-select-kind]');

          Array.from(selects).forEach(function (select) {
            bindEnhancedSelect(select);
          });
        };

        const bindQrActionConfirmation = function (form) {
          if (!form || form.dataset.qrActionConfirmBound === 'true') {
            return;
          }

          form.addEventListener('submit', async function (event) {
            if (form.dataset.qrActionConfirmed === 'true') {
              return;
            }

            event.preventDefault();

            const title = form.dataset.confirmTitle || 'Are you sure?';
            const text = form.dataset.confirmText || 'Please confirm this action before continuing.';
            const confirmButtonText = form.dataset.confirmButtonText || 'Yes, continue';
            const cancelButtonText = form.dataset.cancelButtonText || 'Cancel';
            const confirmButtonClass = form.dataset.confirmButtonClass || 'btn btn-primary me-2';
            const cancelButtonClass = form.dataset.cancelButtonClass || 'btn btn-label-secondary';

            let shouldContinue = false;

            if (window.Swal?.fire) {
              const result = await window.Swal.fire({
                title,
                text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText,
                cancelButtonText,
                reverseButtons: true,
                buttonsStyling: false,
                customClass: {
                  confirmButton: confirmButtonClass,
                  cancelButton: cancelButtonClass,
                },
              });

              shouldContinue = Boolean(result.isConfirmed);
            } else {
              shouldContinue = window.confirm(`${title}\n\n${text}`);
            }

            if (!shouldContinue) {
              return;
            }

            form.dataset.qrActionConfirmed = 'true';

            const submitButton = event.submitter instanceof HTMLButtonElement
              ? event.submitter
              : form.querySelector('button[type="submit"]');

            if (submitButton) {
              submitButton.disabled = true;
            }

            form.submit();
          });

          form.dataset.qrActionConfirmBound = 'true';
        };

        window.refreshUserEditorQrActionConfirmations = function (scope) {
          const forms = scope
            ? (scope.matches?.('[data-user-qr-action-form]') ? [scope] : scope.querySelectorAll?.('[data-user-qr-action-form]') || [])
            : document.querySelectorAll('[data-user-qr-action-form]');

          Array.from(forms).forEach(function (form) {
            bindQrActionConfirmation(form);
          });
        };

        window.refreshUserEditorIdentityRules();
        window.refreshUserEditorCountrySelects();
        window.refreshUserEditorQrActionConfirmations();
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
  $identityTypeLabel = $identityType === 'national_id' ? 'Malaysia IC (MyKad)' : 'Passport';
  $countryLabel = (string) ($user['country_label'] ?? (\App\Support\CountryCatalog::nameFor($user['country'] ?? null) ?? ($user['country'] ?? '-')));
  $phoneDisplay = (string) ($user['phone_number'] ?? '');

  if ($phoneDisplay === '' && filled($user['phone_country_code'] ?? null) && filled($user['phone_national_number'] ?? null)) {
    $phoneDisplay = (string) $user['phone_country_code'] . (string) $user['phone_national_number'];
  }

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

  $normalizePhoneNumber = static function (?string $phoneNumber): string {
    $digits = preg_replace('/\D+/', '', (string) $phoneNumber) ?? '';

    return $digits === '' ? '' : '+' . $digits;
  };

  $currentCountry = strtoupper((string) $fieldValue('country', $user['country'] ?? ''));
  $countryOptionGroups = \App\Support\CountryCatalog::registrationCountryGroups();
  $phoneOptionGroups = \App\Support\CountryCatalog::registrationPhoneGroups();
  $flatPhoneOptions = collect($phoneOptionGroups)
    ->flatten(1)
    ->filter(fn (mixed $option): bool => is_array($option))
    ->sortByDesc(fn (array $option): int => strlen((string) ($option['dial_code'] ?? '')))
    ->values()
    ->all();

  $resolvePhoneParts = static function (array $source) use ($normalizePhoneNumber, $flatPhoneOptions): array {
    $phoneCountryCode = trim((string) ($source['phone_country_code'] ?? ''));
    $phoneNationalNumber = trim((string) ($source['phone_national_number'] ?? ''));
    $phoneNumber = $normalizePhoneNumber((string) ($source['phone_number'] ?? ''));
    $phoneOptionCountry = '';

    if ($phoneNumber === '' && $phoneCountryCode !== '' && $phoneNationalNumber !== '') {
      $phoneNumber = $phoneCountryCode . $phoneNationalNumber;
    }

    if ($phoneNumber !== '') {
      $matchedPhoneOption = collect($flatPhoneOptions)->first(function (array $option) use ($phoneNumber): bool {
        $dialCode = (string) ($option['dial_code'] ?? '');

        return $dialCode !== '' && str_starts_with($phoneNumber, $dialCode);
      });

      if (is_array($matchedPhoneOption)) {
        $phoneOptionCountry = strtoupper((string) ($matchedPhoneOption['country_code'] ?? ''));

        if ($phoneCountryCode === '') {
          $phoneCountryCode = (string) ($matchedPhoneOption['dial_code'] ?? '');
        }
      }
    }

    if ($phoneNationalNumber === '' && $phoneNumber !== '') {
      if ($phoneCountryCode !== '' && str_starts_with($phoneNumber, $phoneCountryCode)) {
        $phoneNationalNumber = ltrim(substr($phoneNumber, strlen($phoneCountryCode)), '0');
      } else {
        $phoneNationalNumber = ltrim(ltrim($phoneNumber, '+'), '0');
      }
    }

    return [
      'phone_country_code' => $phoneCountryCode,
      'phone_national_number' => $phoneNationalNumber,
      'phone_number' => $phoneNumber,
      'phone_option_country' => $phoneOptionCountry,
    ];
  };

  $currentPhoneParts = $resolvePhoneParts([
    'phone_country_code' => $fieldValue('phone_country_code', $user['phone_country_code'] ?? ''),
    'phone_national_number' => $fieldValue('phone_national_number', $user['phone_national_number'] ?? ''),
    'phone_number' => $fieldValue('phone_number', $phoneDisplay),
  ]);
  $selectedPhoneCountryCode = (string) ($currentPhoneParts['phone_country_code'] ?? '');
  $selectedPhoneNationalNumber = (string) ($currentPhoneParts['phone_national_number'] ?? '');
  $selectedPhoneOptionCountry = (string) ($currentPhoneParts['phone_option_country'] ?? '');

  $currentCountryExists = collect($countryOptionGroups)
    ->flatten(1)
    ->contains(fn (mixed $option): bool => is_array($option) && strtoupper((string) ($option['code'] ?? '')) === $currentCountry);

  if ($currentCountry !== '' && !$currentCountryExists) {
    $countryOptionGroups['other'][] = [
      'code' => $currentCountry,
      'alpha3' => '',
      'name' => $countryLabel !== '' ? $countryLabel : $currentCountry,
      'flag' => strtolower($currentCountry),
      'group' => 'other',
    ];

    usort($countryOptionGroups['other'], fn (array $left, array $right): int => strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? '')));
  }

  $selectedPhoneOptionExists = collect($phoneOptionGroups)
    ->flatten(1)
    ->contains(function (mixed $option) use ($selectedPhoneCountryCode, $selectedPhoneOptionCountry): bool {
      if (!is_array($option)) {
        return false;
      }

      if ((string) ($option['dial_code'] ?? '') !== $selectedPhoneCountryCode) {
        return false;
      }

      return $selectedPhoneOptionCountry === ''
        || strtoupper((string) ($option['country_code'] ?? '')) === $selectedPhoneOptionCountry;
    });

  if ($selectedPhoneCountryCode !== '' && !$selectedPhoneOptionExists) {
    $fallbackPhoneCountry = $selectedPhoneOptionCountry !== '' ? $selectedPhoneOptionCountry : $currentCountry;
    $fallbackPhoneCountryName = \App\Support\CountryCatalog::nameFor($fallbackPhoneCountry) ?? ($countryLabel !== '' ? $countryLabel : $fallbackPhoneCountry);

    $phoneOptionGroups['other'][] = [
      'value' => $selectedPhoneCountryCode,
      'key' => ($fallbackPhoneCountry !== '' ? $fallbackPhoneCountry : 'ZZ') . ':' . $selectedPhoneCountryCode,
      'country_code' => $fallbackPhoneCountry !== '' ? $fallbackPhoneCountry : 'ZZ',
      'alpha3' => '',
      'name' => $fallbackPhoneCountryName !== '' ? $fallbackPhoneCountryName : $selectedPhoneCountryCode,
      'dial_code' => $selectedPhoneCountryCode,
      'flag' => preg_match('/^[A-Z]{2}$/', $fallbackPhoneCountry) ? strtolower($fallbackPhoneCountry) : 'xx',
      'group' => 'other',
    ];

    usort($phoneOptionGroups['other'], fn (array $left, array $right): int => strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? '')));
  }

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
        <input type="hidden" name="_skip_phone_index_sync" value="1" />
        <input type="hidden" name="phone_number" value="{{ $currentPhoneParts['phone_number'] ?? '' }}" />
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
              <label class="form-label" for="{{ $fieldIdPrefix }}-phone-national-number">Phone Number / WhatsApp</label>
              <div class="d-grid gap-3">
                <div>
                  <select class="form-select {{ $invalidClass('phone_country_code') }}"
                    id="{{ $fieldIdPrefix }}-phone-country-code" name="phone_country_code" {{ $isReadonly ? 'disabled' : '' }}
                    data-user-input data-user-select-kind="phone" data-placeholder="Search country or dial code...">
                    <option value=""></option>
                    @php
                      $selectedPhoneCountryCodeResolved = false;
                    @endphp
                    @foreach ($phoneOptionGroups as $group => $groupOptions)
                      <optgroup label="{{ $group === 'priority' ? 'Priority Countries' : 'All Other Countries' }}">
                        @foreach ($groupOptions as $option)
                          @php
                            $matchesSelectedPhoneOption = !$selectedPhoneCountryCodeResolved
                              && $selectedPhoneCountryCode !== ''
                              && $selectedPhoneCountryCode === ($option['dial_code'] ?? '')
                              && ($selectedPhoneOptionCountry === '' || $selectedPhoneOptionCountry === strtoupper((string) ($option['country_code'] ?? '')));
                            $selectedPhoneCountryCodeResolved = $selectedPhoneCountryCodeResolved || $matchesSelectedPhoneOption;
                          @endphp
                          <option value="{{ $option['dial_code'] }}" data-flag="{{ $option['flag'] }}"
                            data-label="{{ $option['name'] }}" data-secondary="{{ $option['dial_code'] }}"
                            data-search="{{ implode(' ', array_filter([$option['name'], $option['dial_code'], $option['country_code'], $option['alpha3']])) }}"
                            @selected($matchesSelectedPhoneOption)>
                            {{ $option['name'] }} {{ $option['dial_code'] }}
                          </option>
                        @endforeach
                      </optgroup>
                    @endforeach
                  </select>
                  @if ($errorMessage('phone_country_code'))
                    <div class="invalid-feedback d-block">{{ $errorMessage('phone_country_code') }}</div>
                  @endif
                </div>
                <div>
                  <input type="text"
                    class="form-control {{ trim($invalidClass('phone_national_number') . ' ' . $invalidClass('phone_number')) }}"
                    id="{{ $fieldIdPrefix }}-phone-national-number" name="phone_national_number"
                    value="{{ $selectedPhoneNationalNumber }}" placeholder="8123456789" inputmode="numeric"
                    {{ $isReadonly ? 'readonly' : '' }} data-user-input />
                  @if ($errorMessage('phone_national_number'))
                    <div class="invalid-feedback d-block">{{ $errorMessage('phone_national_number') }}</div>
                  @endif
                  @if ($errorMessage('phone_number'))
                    <div class="invalid-feedback d-block">{{ $errorMessage('phone_number') }}</div>
                  @endif
                </div>
              </div>
              <small class="text-muted d-block mt-2">Choose the country code first, then enter the number without the leading zero.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-country">Country</label>
              <select class="form-select {{ $invalidClass('country') }}" id="{{ $fieldIdPrefix }}-country"
                name="country" {{ $isReadonly ? 'disabled' : '' }} data-user-input data-user-country-select
                data-user-select-kind="country" data-placeholder="Search nationality..." required>
                @foreach ($countryOptionGroups as $group => $groupCountries)
                  <optgroup label="{{ $group === 'priority' ? 'Priority Countries' : 'All Other Countries' }}">
                    @foreach ($groupCountries as $country)
                      <option value="{{ $country['code'] }}" data-flag="{{ $country['flag'] }}"
                        data-label="{{ $country['name'] }}"
                        data-search="{{ implode(' ', array_filter([$country['name'], $country['code'], $country['alpha3']])) }}"
                        @selected($fieldValue('country', $user['country'] ?? '') === $country['code'])>{{ $country['name'] }}</option>
                    @endforeach
                  </optgroup>
                @endforeach
              </select>
              @if ($errorMessage('country'))
                <div class="invalid-feedback d-block">{{ $errorMessage('country') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-identity-type-display">Document Type</label>
              <select class="form-select {{ $invalidClass('identity_type') }}"
                id="{{ $fieldIdPrefix }}-identity-type-display" {{ $isReadonly ? 'disabled' : '' }} data-user-input
                data-user-identity-display>
                <option value="passport" @selected($fieldValue('identity_type', $identityType) === 'passport')>Passport
                </option>
                <option value="national_id" @selected($fieldValue('identity_type', $identityType) === 'national_id')>Malaysia
                  IC (MyKad)</option>
              </select>
              <small class="text-muted d-block mt-1" data-user-identity-note></small>
              @if ($errorMessage('identity_type'))
                <div class="invalid-feedback d-block">{{ $errorMessage('identity_type') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label" for="{{ $fieldIdPrefix }}-identity-number">Document Number</label>
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
              {{ $identityTypeLabel }}
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
            <form method="POST" action="{{ route('admin.users.qr.reset', $user['user_id']) }}"
              data-user-qr-action-form
              data-confirm-title="Reset attendance?"
              data-confirm-text="This will clear the participant attendance record and reset the QR state for this participant. Do you want to continue?"
              data-confirm-button-text="Yes, reset attendance"
              data-confirm-button-class="btn btn-label-warning me-2">
              @csrf
              <button type="submit" class="btn btn-label-warning w-100">Reset Attendance</button>
            </form>

            <form method="POST" action="{{ route('admin.users.qr.regenerate', $user['user_id']) }}"
              data-user-qr-action-form
              data-confirm-title="Generate a new QR code?"
              data-confirm-text="This will generate a fresh QR code for the participant. Previous QR references should no longer be used. Do you want to continue?"
              data-confirm-button-text="Yes, generate QR"
              data-confirm-button-class="btn btn-primary me-2">
              @csrf
              <button type="submit" class="btn btn-label-primary w-100">Generate QR</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
