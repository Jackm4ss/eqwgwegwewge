<!doctype html>
<html lang="en" class="layout-menu-fixed layout-navbar-fixed" dir="ltr" data-skin="default" data-bs-theme="light"
  data-assets-path="{{ asset('assets-vuexy/') }}/" data-template="vertical-menu-template">

<head>
  @php
    $shareImagePath = implode('/', array_map('rawurlencode', explode('/', 'images/Songkran logo.png')));
    $faviconImage = asset($shareImagePath);
  @endphp
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="robots" content="noindex, nofollow" />
  <title>{{ $title ?? 'Admin Panel' }} | Event System</title>

  <link rel="icon" type="image/png" href="{{ $faviconImage }}">
  <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
  <link rel="apple-touch-icon" href="{{ $faviconImage }}">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />

  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/fonts/iconify-icons-subset.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/node-waves/node-waves.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/css/core.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets-vuexy/css/demo.css') }}" />
  @stack('vendor-styles')

  <script src="{{ asset('assets-vuexy/vendor/js/helpers.js') }}"></script>
  <script src="{{ asset('assets-vuexy/js/config.js') }}"></script>
</head>

<body>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
          <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo text-primary">
              <i class="icon-base ti tabler-layout-dashboard fs-2"></i>
            </span>
            <span class="app-brand-text demo menu-text fw-bold">Event Admin</span>
          </a>

          <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="icon-base ti tabler-chevron-left align-middle"></i>
          </a>
        </div>

        <div class="menu-inner-shadow"></div>

        @php
          $adminManagementOpen = request()->routeIs('admin.logs.*') || request()->routeIs('admin.admin-users.*');
          $scannerManagementOpen = request()->routeIs('admin.scanner-users.*')
            || request()->routeIs('admin.attendance.*')
            || request()->routeIs('admin.gates.*');
          $reportManagementOpen = request()->routeIs('admin.reports.*') || request()->routeIs('admin.public-reports.*');
        @endphp

        <ul class="menu-inner py-1">
          <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="menu-link">
              <i class="menu-icon icon-base ti tabler-layout-dashboard"></i>
              <div>Dashboard</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <a href="{{ route('admin.users.index') }}" class="menu-link">
              <i class="menu-icon icon-base ti tabler-users"></i>
              <div>User Management</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('admin.campaign-links.*') ? 'active' : '' }}">
            <a href="{{ route('admin.campaign-links.index') }}" class="menu-link">
              <i class="menu-icon icon-base ti tabler-badge"></i>
              <div>Campaign Links</div>
            </a>
          </li>
          <li class="menu-item {{ $adminManagementOpen ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
              <i class="menu-icon icon-base ti tabler-lock"></i>
              <div>Admin Management</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                <a href="{{ route('admin.logs.index') }}" class="menu-link">
                  <div>Admin Activity Log</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('admin.admin-users.*') ? 'active' : '' }}">
                <a href="{{ route('admin.admin-users.index') }}" class="menu-link">
                  <div>List User Admin</div>
                </a>
              </li>
            </ul>
          </li>
          <li class="menu-item {{ $scannerManagementOpen ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
              <i class="menu-icon icon-base ti tabler-scan"></i>
              <div>Scanner Management</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}">
                <a href="{{ route('admin.attendance.index') }}" class="menu-link">
                  <div>Monitoring Attendance</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('admin.gates.*') ? 'active' : '' }}">
                <a href="{{ route('admin.gates.index') }}" class="menu-link">
                  <div>Gate Management</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('admin.scanner-users.*') ? 'active' : '' }}">
                <a href="{{ route('admin.scanner-users.index') }}" class="menu-link">
                  <div>List User Scanner</div>
                </a>
              </li>
            </ul>
          </li>
          <li class="menu-item {{ $reportManagementOpen ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
              <i class="menu-icon icon-base ti tabler-report-analytics"></i>
              <div>Report Management</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <a href="{{ route('admin.reports.index') }}" class="menu-link">
                  <div>Reporting & Export</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('admin.public-reports.*') ? 'active' : '' }}">
                <a href="{{ route('admin.public-reports.index') }}" class="menu-link">
                  <div>Public Report</div>
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </aside>

      <div class="layout-page">
        <nav
          class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
          id="layout-navbar">
          <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
              <i class="icon-base ti tabler-menu-2 icon-md"></i>
            </a>
          </div>

          <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
            <div class="navbar-nav align-items-center">
              <div class="nav-item d-flex flex-column">
                <span class="fw-semibold">{{ $title ?? 'Admin Panel' }}</span>

              </div>
            </div>

            <ul class="navbar-nav flex-row align-items-center ms-auto">
              <li class="nav-item dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                  <div class="avatar avatar-online">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      {{ strtoupper(substr((string) optional(auth('admin')->user())->name, 0, 1)) }}
                    </span>
                  </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                      <div class="d-flex">
                        <div class="flex-grow-1">
                          <span class="fw-medium d-block">{{ optional(auth('admin')->user())->name }}</span>
                          <small class="text-muted">{{ optional(auth('admin')->user())->email }}</small>
                        </div>
                      </div>
                    </a>
                  </li>
                  <li>
                    <div class="dropdown-divider my-1"></div>
                  </li>
                  <li>
                    <form method="POST" action="{{ route('admin.logout') }}">
                      @csrf
                      <button type="submit" class="dropdown-item">
                        <i class="icon-base ti tabler-logout me-2"></i>
                        <span>Logout</span>
                      </button>
                    </form>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </nav>

        <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y">
            @if (session('status'))
              <div class="alert alert-success alert-dismissible mb-4" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @if ($errors->any())
              <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                <div class="fw-semibold mb-2">There are a few things to review:</div>
                <ul class="mb-0 ps-3">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @if (isset($firestoreAvailable) && !$firestoreAvailable)
              <div class="alert alert-warning mb-4" role="alert">
                Firestore is not available in this environment. The admin pages can still load, but dashboard,
                participant, attendance, and activity log data will stay empty until Firebase credentials are active.
              </div>
            @endif

            @yield('content')
          </div>

          <footer class="content-footer footer bg-footer-theme">
            <div class="container-xxl">
              <div
                class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                <div class="text-body mb-2 mb-md-0">
                  Event System Admin Panel
                </div>
                <div class="d-none d-lg-inline-block">
                </div>
              </div>
            </div>
          </footer>
        </div>
      </div>
    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
    <div class="drag-target"></div>
  </div>

  <script src="{{ asset('assets-vuexy/vendor/libs/jquery/jquery.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/libs/popper/popper.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/js/bootstrap.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/libs/node-waves/node-waves.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/libs/hammer/hammer.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/libs/i18n/i18n.js') }}"></script>
  <script src="{{ asset('assets-vuexy/vendor/js/menu.js') }}"></script>
  <script src="{{ asset('assets-vuexy/js/main.js') }}"></script>
  @stack('vendor-scripts')
  @stack('page-scripts')
  @if (auth('admin')->check() && optional(auth('admin')->user())->role === 'admin')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const heartbeatUrl = @json(route('admin.presence.heartbeat'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const heartbeatIntervalMs = {{ max(15000, ((int) config('admin.presence.heartbeat_seconds', 45)) * 1000) }};
        let heartbeatRequest = null;

        const sendHeartbeat = () => {
          if (!heartbeatUrl || !csrfToken || heartbeatRequest !== null) {
            return;
          }

          heartbeatRequest = fetch(heartbeatUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ heartbeat: true }),
            keepalive: true
          }).catch(() => null).finally(() => {
            heartbeatRequest = null;
          });
        };

        sendHeartbeat();
        window.setInterval(sendHeartbeat, heartbeatIntervalMs);

        document.addEventListener('visibilitychange', function () {
          if (document.visibilityState === 'visible') {
            sendHeartbeat();
          }
        });
      });
    </script>
  @endif
</body>

</html>
