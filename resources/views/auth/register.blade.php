<!doctype html>
@php
  $shareImagePath = implode('/', array_map('rawurlencode', explode('/', 'images/Songkran logo.png')));
  $metaTitle = 'Songkran Festival 2026';
  $metaDescription = "Malaysia's Premier Songkran Festival.";
  $metaImage = asset($shareImagePath);
  $metaUrl = url()->current();
@endphp

<html
  lang="en"
  class=" layout-wide  customizer-hide"
  dir="ltr"
  data-skin="default"
  data-bs-theme="light"
  data-assets-path="{{ asset('assets-vuexy/') }}/"
  data-template="vertical-menu-template">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Register | Songkran Festival 2026</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $metaUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:image:alt" content="Songkran Festival 2026 logo">
    <meta property="og:url" content="{{ $metaUrl }}">
    <meta property="og:site_name" content="{{ $metaTitle }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">
    <meta name="twitter:image:alt" content="Songkran Festival 2026 logo">

    <link rel="icon" type="image/png" href="{{ $metaImage }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ $metaImage }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/fonts/iconify-icons-subset.css') }}" />

    <script src="{{ asset('assets-vuexy/vendor/libs/@algolia/autocomplete-js.js') }}"></script>

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/pickr/pickr-themes.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/@form-validation/form-validation.css') }}" />
    
    <!-- Page CSS -->
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/css/pages/page-auth.css') }}" />

    <!-- Helpers -->
    <script src="{{ asset('assets-vuexy/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/js/template-customizer.js') }}"></script>
    <script src="{{ asset('assets-vuexy/js/config.js') }}"></script>
  </head>

  <body>
    <!-- Content -->

    <div class="authentication-wrapper authentication-cover">
      <!-- Logo -->
      <a href="{{ url('/') }}" class="app-brand auth-cover-brand">
        <span class="app-brand-logo demo">
          <span class="text-primary">
            <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z"
                fill="currentColor" />
              <path
                opacity="0.06"
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M7.69824 16.4364L12.5199 3.23696L16.5541 7.25596L7.69824 16.4364Z"
                fill="#161616" />
              <path
                opacity="0.06"
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M8.07751 15.9175L13.9419 4.63989L16.5849 7.28475L8.07751 15.9175Z"
                fill="#161616" />
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M7.77295 16.3566L23.6563 0H32V6.88383C32 6.88383 31.8262 9.17836 30.6591 10.4057L19.7824 22H13.6938L7.77295 16.3566Z"
                fill="currentColor" />
            </svg>
          </span>
        </span>
        <span class="app-brand-text demo text-heading fw-bold">Vuexy</span>
      </a>
      <!-- /Logo -->
      <div class="authentication-inner row m-0">
        <!-- /Left Text -->
        <div class="d-none d-lg-flex col-lg-6 col-xl-7 p-0">
          <div class="auth-cover-bg d-flex justify-content-center align-items-center">
            <img
              src="{{ asset('assets-vuexy/img/illustrations/auth-register-illustration-light.png') }}"
              alt="auth-register-cover"
              class="my-5 auth-illustration"
              data-app-light-img="illustrations/auth-register-illustration-light.png"
              data-app-dark-img="illustrations/auth-register-illustration-dark.png" />
            <img
              src="{{ asset('assets-vuexy/img/illustrations/bg-shape-image-light.png') }}"
              alt="auth-register-cover"
              class="platform-bg"
              data-app-light-img="illustrations/bg-shape-image-light.png"
              data-app-dark-img="illustrations/bg-shape-image-dark.png" />
          </div>
        </div>
        <!-- /Left Text -->

        <!-- Register -->
        <div class="d-flex col-12 col-lg-6 col-xl-5 align-items-center authentication-bg p-sm-12 p-6">
          <div class="w-100 mx-auto mt-12 pt-5" style="max-width: 500px">
            <h4 class="mb-1">Adventure starts here 🚀</h4>
            <p class="mb-6">Make your event management easy and fun!</p>

            @if ($errors->any())
                <div class="alert alert-danger mb-4 rounded-0" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="formAuthentication" class="mb-6" action="{{ route('register.submit') }}" method="POST">
              @csrf
              <div class="row g-3">
                
                <!-- Section: Data Pribadi -->
                <div class="col-12 mt-4 mb-2">
                  <h5 class="mb-0">Data Pribadi</h5>
                  <hr class="my-2" />
                </div>

                <div class="col-md-6 form-control-validation">
                  <label for="full_name" class="form-label">Nama Lengkap</label>
                  <input type="text" class="form-control" id="full_name" name="full_name" value="{{ old('full_name') }}" placeholder="John Doe" required autofocus />
                </div>
                <div class="col-md-6 form-control-validation">
                  <label for="email" class="form-label">Email</label>
                  <input type="email" class="form-control" id="email" required value="{{ old('email') }}" name="email" placeholder="john@example.com" />
                </div>

                <div class="col-md-6 form-control-validation">
                  <label for="phone_number" class="form-label">Nomor HP</label>
                  <input type="text" class="form-control" id="phone_number" required value="{{ old('phone_number') }}" name="phone_number" placeholder="08123456789" />
                </div>
                <div class="col-md-6 form-control-validation">
                  <label for="country" class="form-label">Negara</label>
                  <input type="text" class="form-control" id="country" required value="{{ old('country') }}" name="country" placeholder="Indonesia" />
                </div>

                <div class="col-md-6 form-control-validation">
                  <label for="identity_type" class="form-label">Jenis Dokumen</label>
                  <select class="form-control" id="identity_type" required name="identity_type">
                    <option value="">Pilih jenis dokumen</option>
                    <option value="national_id" @selected(old('identity_type') === 'national_id')>IC Malaysia / National ID</option>
                    <option value="passport" @selected(old('identity_type') === 'passport')>Passport</option>
                  </select>
                  <small class="text-muted d-block mt-1">Untuk Malaysia gunakan Malaysia IC (MyKad). Untuk luar Malaysia gunakan Passport.</small>
                </div>

                <div class="col-md-6 form-control-validation">
                  <label for="identity_number" class="form-label">Nomor Dokumen</label>
                  <input type="text" class="form-control" id="identity_number" required value="{{ old('identity_number') }}" name="identity_number" placeholder="Input nomor dokumen" />
                  <small class="text-muted d-block mt-1" id="identity_number_hint">Untuk Malaysia, Malaysia IC (MyKad) wajib tepat 12 digit.</small>
                </div>

                <!-- Section: Konfirmasi -->
                <div class="col-12 mt-4 mb-2">
                  <h5 class="mb-0">Konfirmasi</h5>
                  <hr class="my-2" />
                </div>

                <div class="col-12 form-control-validation">
                  <div class="form-check mb-0 ms-2">
                    <input class="form-check-input" required type="checkbox" id="terms-conditions" name="agreeTerms" value="1" />
                    <label class="form-check-label" for="terms-conditions">
                      I agree to
                      <a href="javascript:void(0);">privacy policy & terms</a>
                    </label>
                  </div>
                </div>
                
                <div class="col-12 mt-4">
                  <button class="btn btn-primary d-grid w-100" type="submit">Sign up</button>
                </div>

              </div>
            </form>

            <p class="text-center mt-6">
              <span>Already have an account?</span>
              <a href="{{ route('login') }}">
                <span>Sign in instead</span>
              </a>
            </p>

          </div>
        </div>
        <!-- /Register -->
      </div>
    </div>

    <!-- / Content -->

    <!-- Core JS -->
    <script src="{{ asset('assets-vuexy/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/js/menu.js') }}"></script>

    <!-- Vendors JS -->
    <script src="{{ asset('assets-vuexy/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/@form-validation/auto-focus.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ asset('assets-vuexy/js/main.js') }}"></script>
    <script src="{{ asset('assets-vuexy/js/pages-auth.js') }}"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const countryInput = document.getElementById('country');
        const identityTypeSelect = document.getElementById('identity_type');
        const nationalIdOption = identityTypeSelect?.querySelector('option[value="national_id"]');
        const passportOption = identityTypeSelect?.querySelector('option[value="passport"]');
        const identityNumberInput = document.getElementById('identity_number');
        const identityNumberHint = document.getElementById('identity_number_hint');
        let previousCountry = countryInput?.value.trim().toUpperCase() ?? '';

        if (!countryInput || !identityTypeSelect || !nationalIdOption || !passportOption) {
          return;
        }

        const normalizeIdentityNumber = (value, identityType) => {
          if (identityType === 'national_id') {
            return value.replace(/\D/g, '').slice(0, 12);
          }

          if (identityType !== 'passport') {
            return value.trim();
          }

          return value.replace(/[^a-zA-Z0-9]/g, '').slice(0, 10).toUpperCase();
        };

        const syncIdentityType = () => {
          const country = countryInput.value.trim().toUpperCase();
          const malaysiaOnly = country === 'MY';
          const passportOnly = country !== '' && country !== 'MY';
          const shouldResetIdentityNumber = previousCountry !== '' && previousCountry !== country;

          nationalIdOption.hidden = passportOnly;
          passportOption.hidden = malaysiaOnly;

          if (malaysiaOnly) {
            identityTypeSelect.value = 'national_id';
          } else if (passportOnly) {
            identityTypeSelect.value = 'passport';
          } else {
            identityTypeSelect.value = '';
          }

          if (shouldResetIdentityNumber && identityNumberInput) {
            identityNumberInput.value = '';
          }

          if (identityNumberInput) {
            const isNationalId = identityTypeSelect.value === 'national_id';
            const isPassport = identityTypeSelect.value === 'passport';
            identityNumberInput.inputMode = isNationalId ? 'numeric' : 'text';
            identityNumberInput.maxLength = isNationalId ? 12 : (isPassport ? 10 : 80);
            identityNumberInput.value = normalizeIdentityNumber(identityNumberInput.value, identityTypeSelect.value);
          }

          if (identityNumberHint) {
            identityNumberHint.textContent = identityTypeSelect.value === 'national_id'
              ? 'Untuk Malaysia, Malaysia IC (MyKad) wajib tepat 12 digit.'
              : identityTypeSelect.value === 'passport'
                ? 'Untuk luar Malaysia, gunakan Passport yang valid sesuai dokumen perjalanan.'
                : 'Masukkan nomor dokumen sesuai jenis identitas yang dipilih.';
          }

          previousCountry = country;
        };

        identityTypeSelect.addEventListener('change', syncIdentityType);
        identityNumberInput?.addEventListener('input', function () {
          identityNumberInput.value = normalizeIdentityNumber(identityNumberInput.value, identityTypeSelect.value);
        });
        countryInput.addEventListener('input', syncIdentityType);
        syncIdentityType();
      });
    </script>
  </body>
</html>
