<!doctype html>

<html
  lang="en"
  class="layout-wide customizer-hide"
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
    <title>Login | Event System</title>

    <meta name="description" content="" />

    <link rel="icon" type="image/x-icon" href="{{ asset('assets-vuexy/img/favicon/favicon.ico') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/fonts/iconify-icons.css') }}" />

    <script src="{{ asset('assets-vuexy/vendor/libs/@algolia/autocomplete-js.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/pickr/pickr-themes.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/css/pages/page-auth.css') }}" />

    <script src="{{ asset('assets-vuexy/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/js/template-customizer.js') }}"></script>
    <script src="{{ asset('assets-vuexy/js/config.js') }}"></script>
  </head>

  <body>
    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-6">
          <div class="card">
            <div class="card-body">
              <div class="app-brand justify-content-center mb-6">
                <a href="{{ url('/') }}" class="app-brand-link">
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
                  <span class="app-brand-text demo text-heading fw-bold">Event System</span>
                </a>
              </div>

              <h4 class="mb-1">Portal Login</h4>
              <p class="mb-6">Live sign-in is handled in the main portal. This repository only keeps the registration and recovery screens.</p>

              @if (session('status'))
                  <div class="alert alert-success mb-4 rounded-0" role="alert">
                      {{ session('status') }}
                  </div>
              @endif

              @if ($errors->any())
                  <div class="alert alert-danger mb-4 rounded-0" role="alert">
                      <ul class="mb-0">
                          @foreach ($errors->all() as $error)
                              <li>{{ $error }}</li>
                          @endforeach
                      </ul>
                  </div>
              @endif

              <form id="formAuthentication" class="mb-4" action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="mb-6 form-control-validation">
                  <label for="email" class="form-label">Email</label>
                  <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="{{ old('email') }}"
                    required
                    autofocus />
                </div>

                <div class="mb-6 form-password-toggle form-control-validation">
                  <label class="form-label" for="password">Password</label>
                  <div class="input-group input-group-merge">
                    <input
                      type="password"
                      id="password"
                      class="form-control"
                      name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      required
                      aria-describedby="password" />
                    <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
                  </div>
                </div>

                <div class="my-8">
                  <div class="d-flex justify-content-between">
                    <div class="form-check mb-0 ms-2">
                      <input class="form-check-input" type="checkbox" name="remember" id="remember-me" {{ old('remember') ? 'checked' : '' }} />
                      <label class="form-check-label" for="remember-me"> Remember Me </label>
                    </div>
                    <a href="{{ route('password.request') }}">
                      <p class="mb-0">Forgot Password?</p>
                    </a>
                  </div>
                </div>

                <div class="mb-6">
                  <button class="btn btn-primary d-grid w-100" type="submit">Login</button>
                </div>
              </form>

              <p class="text-center">
                <span>New on our platform?</span>
                <a href="{{ route('register.form') }}">
                  <span>Create an account</span>
                </a>
              </p>

              <div class="divider my-6">
                <div class="divider-text">or</div>
              </div>

              <div class="d-flex justify-content-center">
                <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-facebook me-1_5">
                  <i class="icon-base ti tabler-brand-facebook-filled icon-20px"></i>
                </a>

                <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-twitter me-1_5">
                  <i class="icon-base ti tabler-brand-twitter-filled icon-20px"></i>
                </a>

                <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-github me-1_5">
                  <i class="icon-base ti tabler-brand-github-filled icon-20px"></i>
                </a>

                <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-google-plus">
                  <i class="icon-base ti tabler-brand-google-filled icon-20px"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="{{ asset('assets-vuexy/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/js/menu.js') }}"></script>

    <script src="{{ asset('assets-vuexy/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('assets-vuexy/vendor/libs/@form-validation/auto-focus.js') }}"></script>

    <script src="{{ asset('assets-vuexy/js/main.js') }}"></script>
    <script src="{{ asset('assets-vuexy/js/pages-auth.js') }}"></script>
  </body>
</html>
