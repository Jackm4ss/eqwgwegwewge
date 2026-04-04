@extends('admin.layouts.app')

@php
  $management = array_merge([
    'badge' => 'Admin Management',
    'list_title' => 'List User Admin',
    'list_description' => '',
    'summary_total_label' => 'Total admins',
    'summary_active_label' => 'Active accounts',
    'summary_online_label' => 'Visible online',
    'summary_offline_label' => 'Visible offline',
    'index_route' => 'admin.admin-users.index',
    'search_label' => 'Search admin',
    'search_placeholder' => 'Search by admin name or email',
    'directory_title' => 'Admin Account Directory',
    'directory_count_noun' => 'admin accounts',
    'create_route' => 'admin.admin-users.create',
    'create_button_label' => 'Create Admin',
    'edit_route' => 'admin.admin-users.edit',
    'destroy_route' => 'admin.admin-users.destroy',
    'empty_state' => 'No admin accounts matched the current filters.',
    'presence_footer_resource_label' => 'admin accounts',
    'delete_dialog_title' => 'Delete this admin account?',
    'delete_dialog_subject' => 'this admin',
    'delete_dialog_directory' => 'admin directory',
    'delete_dialog_access' => 'admin dashboard',
    'delete_confirm_label' => 'Yes, delete admin',
    'delete_cancel_label' => 'Keep account',
  ], $management ?? []);
  $title = $management['list_title'];
@endphp

@push('vendor-styles')
  <link rel="stylesheet" href="{{ asset('assets-vuexy/vendor/libs/sweetalert2/sweetalert2.css') }}" />
  <style>
    .admin-user-table .avatar {
      --bs-avatar-size: 2.75rem;
    }
  </style>
@endpush

@push('vendor-scripts')
  <script src="{{ asset('assets-vuexy/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endpush

@section('content')
  @php
    $adminInitials = static function (?string $name): string {
      $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
      $letters = collect($parts)
        ->take(2)
        ->map(fn(string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

      return $letters !== '' ? $letters : 'A';
    };

    $formatDateTime = static function (mixed $value): string {
      if (blank($value)) {
        return 'Never';
      }

      try {
        return \Carbon\CarbonImmutable::parse((string) $value)
          ->setTimezone(config('app.timezone'))
          ->format('d M Y, H:i');
      } catch (\Throwable) {
        return (string) $value;
      }
    };

    $currentPerPage = (int) ($filters['per_page'] ?? 10);
    $currentPresence = (string) ($filters['presence'] ?? 'all');
    $presenceStatuses = $presence['statuses'] ?? [];
    $visibleOnlineCount = collect($presenceStatuses)->filter()->count();
    $visibleOfflineCount = max(0, count($presenceStatuses) - $visibleOnlineCount);
  @endphp

  <div class="card mb-6">
    <div class="card-body">
      <div>
        <div>
          <span class="badge bg-label-primary mb-2">{{ $management['badge'] }}</span>
          <h5 class="mb-1">{{ $management['list_title'] }}</h5>
          @if (filled($management['list_description'] ?? null))
            <p class="text-muted mb-0">
              {{ $management['list_description'] }}
            </p>
          @endif
        </div>
      </div>
    </div>
    <div class="card-body border-top pt-4">
      <div class="row g-4">
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 h-100 p-4">
            <div class="d-flex align-items-start mb-3">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="icon-base ti tabler-users"></i>
                </span>
              </div>
            </div>
            <small class="text-muted d-block mb-1">{{ $management['summary_total_label'] }}</small>
            <h4 class="mb-0">{{ number_format((int) ($summary['total'] ?? 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 h-100 p-4">
            <div class="d-flex align-items-start mb-3">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="icon-base ti tabler-user-check"></i>
                </span>
              </div>
            </div>
            <small class="text-muted d-block mb-1">{{ $management['summary_active_label'] }}</small>
            <h4 class="mb-0">{{ number_format((int) ($summary['active'] ?? 0)) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 h-100 p-4">
            <div class="d-flex align-items-start mb-3">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded bg-label-info">
                  <i class="icon-base ti tabler-circle-check"></i>
                </span>
              </div>
            </div>
            <small class="text-muted d-block mb-1">{{ $management['summary_online_label'] }}</small>
            <h4 class="mb-0" data-online-count>{{ number_format($visibleOnlineCount) }}</h4>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="border rounded-3 h-100 p-4">
            <div class="d-flex align-items-start mb-3">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded bg-label-secondary">
                  <i class="icon-base ti tabler-eye-off"></i>
                </span>
              </div>
            </div>
            <small class="text-muted d-block mb-1">{{ $management['summary_offline_label'] }}</small>
            <h4 class="mb-0" data-offline-count>{{ number_format($visibleOfflineCount) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-6">
    <div class="card-body">
      <form method="GET" action="{{ route($management['index_route']) }}" class="row g-4 align-items-end">
        <div class="col-md-3">
          <label class="form-label" for="q">{{ $management['search_label'] }}</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
            placeholder="{{ $management['search_placeholder'] }}" />
        </div>
        <div class="col-md-2">
          <label class="form-label" for="status">Status</label>
          <select class="form-select" id="status" name="status">
            <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All status</option>
            <option value="active" @selected(($filters['status'] ?? 'all') === 'active')>Active only</option>
            <option value="inactive" @selected(($filters['status'] ?? 'all') === 'inactive')>Inactive only</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label" for="presence">Presence</label>
          <select class="form-select" id="presence" name="presence">
            <option value="all" @selected($currentPresence === 'all')>All presence</option>
            <option value="online" @selected($currentPresence === 'online')>Online only</option>
            <option value="offline" @selected($currentPresence === 'offline')>Offline only</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label" for="per_page">Per page</label>
          <select class="form-select" id="per_page" name="per_page">
            @foreach ([10, 25, 50, 100] as $perPageOption)
              <option value="{{ $perPageOption }}" @selected($currentPerPage === $perPageOption)>{{ $perPageOption }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <div class="d-flex flex-column flex-sm-row gap-2">
            <button type="submit" class="btn btn-primary flex-fill">Apply</button>
            <a href="{{ route($management['index_route']) }}" class="btn btn-label-danger flex-fill">
              Reset filters
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
      <div>
        <h5 class="mb-1">{{ $management['directory_title'] }}</h5>
        <small class="text-muted">{{ number_format($admins->total()) }} {{ $management['directory_count_noun'] }} found</small>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route($management['create_route']) }}" class="btn btn-primary">
          {{ $management['create_button_label'] }}
        </a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle admin-user-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Account status</th>
            <th>Role</th>
            <th>Presence</th>
            <th>Last login</th>
            <th>Created</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($admins as $admin)
            @php
              $isOnline = (bool) ($presenceStatuses[(string) $admin->getKey()] ?? false);
              $isCurrentAdmin = (int) optional(auth('admin')->user())->getKey() === (int) $admin->getKey();
            @endphp
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar me-4">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      {{ $adminInitials($admin->name) }}
                    </span>
                  </div>
                  <div class="d-flex flex-column">
                    <span class="fw-medium text-heading">{{ $admin->name }}</span>
                    <small class="text-muted">{{ $admin->email }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge {{ $admin->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                  {{ $admin->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td>
                <span class="badge bg-label-primary">{{ ucfirst((string) $admin->role) }}</span>
              </td>
              <td>
                <span class="badge {{ $isOnline ? 'bg-label-success' : 'bg-label-secondary' }}" data-admin-presence-badge
                  data-admin-id="{{ $admin->getKey() }}" data-online-class="bg-label-success"
                  data-offline-class="bg-label-secondary">
                  {{ $isOnline ? 'Online' : 'Offline' }}
                </span>
              </td>
              <td>{{ $formatDateTime($admin->last_login_at) }}</td>
              <td>{{ $formatDateTime($admin->created_at) }}</td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                  <a href="{{ route($management['edit_route'], $admin) }}" class="btn btn-sm btn-label-primary">
                    Edit
                  </a>
                  @if ($isCurrentAdmin)
                    <button type="button" class="btn btn-sm btn-label-secondary" disabled>
                      Current account
                    </button>
                  @else
                    <form method="POST" action="{{ route($management['destroy_route'], $admin) }}"
                      class="js-delete-admin-form d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-label-danger" data-admin-name="{{ $admin->name }}"
                        data-admin-email="{{ $admin->email }}">
                        Delete
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-6 text-muted">{{ $management['empty_state'] }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body border-top">
      <div class="small text-muted mb-3">
        Presence updates automatically every {{ (int) ($presence['heartbeat_seconds'] ?? 45) }} seconds for the
        {{ $management['presence_footer_resource_label'] }} shown on this page,
      </div>
      {{ $admins->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  </div>
@endsection

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const deleteDialogTitle = @json($management['delete_dialog_title']);
      const deleteDialogSubject = @json($management['delete_dialog_subject']);
      const deleteDialogDirectory = @json($management['delete_dialog_directory']);
      const deleteDialogAccess = @json($management['delete_dialog_access']);
      const deleteDialogConfirmLabel = @json($management['delete_confirm_label']);
      const deleteDialogCancelLabel = @json($management['delete_cancel_label']);

      const escapeHtml = function (value) {
        return String(value)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      };

      document.querySelectorAll('.js-delete-admin-form').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
          if (form.dataset.deleteConfirmed === 'true') {
            return;
          }

          const button = form.querySelector('button[type="submit"]');
          const adminName = button?.dataset.adminName || deleteDialogSubject;
          const adminEmail = button?.dataset.adminEmail || '';

          event.preventDefault();

          const detailLine = adminEmail
            ? `${adminName} (${adminEmail})`
            : adminName;
          const safeDetailLine = escapeHtml(detailLine);
          const fallbackMessage = `${detailLine} will be removed from the ${deleteDialogDirectory} and will no longer be able to sign in to the ${deleteDialogAccess}.`;

          let shouldDelete = false;

          if (window.Swal?.fire) {
            const result = await window.Swal.fire({
              title: deleteDialogTitle,
              html: `
                      <p class="mb-2 text-start">You are about to remove <strong>${safeDetailLine}</strong> from the ${escapeHtml(deleteDialogDirectory)}.</p>
                      <p class="mb-0 text-start text-muted">This account will immediately lose access to the ${escapeHtml(deleteDialogAccess)}.</p>
                    `,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: deleteDialogConfirmLabel,
              cancelButtonText: deleteDialogCancelLabel,
              reverseButtons: true,
              focusCancel: true,
              buttonsStyling: false,
              customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-label-secondary'
              }
            });

            shouldDelete = Boolean(result.isConfirmed);
          } else {
            shouldDelete = window.confirm(`${fallbackMessage}\n\nContinue deleting this account?`);
          }

          if (!shouldDelete) {
            return;
          }

          form.dataset.deleteConfirmed = 'true';

          if (button) {
            button.disabled = true;
          }

          form.submit();
        });
      });

      const statusesUrl = @json($presence['statuses_url'] ?? '');
      const refreshIntervalMs = {{ max(15000, ((int) ($presence['heartbeat_seconds'] ?? 45)) * 1000) }};
      const badges = Array.from(document.querySelectorAll('[data-admin-presence-badge]'));
      const onlineCountEl = document.querySelector('[data-online-count]');
      const offlineCountEl = document.querySelector('[data-offline-count]');

      if (!statusesUrl || badges.length === 0) {
        return;
      }

      const applyStatus = (badge, isOnline) => {
        const onlineClass = badge.dataset.onlineClass || 'bg-label-success';
        const offlineClass = badge.dataset.offlineClass || 'bg-label-secondary';

        badge.classList.remove(onlineClass, offlineClass);
        badge.classList.add(isOnline ? onlineClass : offlineClass);
        badge.textContent = isOnline ? 'Online' : 'Offline';
        badge.dataset.presenceState = isOnline ? 'online' : 'offline';
      };

      const updateCounters = () => {
        const onlineCount = badges.filter((badge) => badge.dataset.presenceState === 'online').length;
        const offlineCount = Math.max(0, badges.length - onlineCount);

        if (onlineCountEl) {
          onlineCountEl.textContent = String(onlineCount);
        }

        if (offlineCountEl) {
          offlineCountEl.textContent = String(offlineCount);
        }
      };

      badges.forEach((badge) => {
        applyStatus(badge, badge.textContent.trim().toLowerCase() === 'online');
      });

      const refreshStatuses = () => {
        const params = new URLSearchParams();

        badges.forEach((badge) => {
          const adminId = badge.dataset.adminId || '';

          if (adminId) {
            params.append('ids[]', adminId);
          }
        });

        fetch(`${statusesUrl}?${params.toString()}`, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
          .then((response) => response.ok ? response.json() : null)
          .then((payload) => {
            if (!payload || typeof payload !== 'object' || typeof payload.statuses !== 'object') {
              return;
            }

            badges.forEach((badge) => {
              const adminId = badge.dataset.adminId || '';

              applyStatus(badge, Boolean(payload.statuses[adminId]));
            });

            updateCounters();
          })
          .catch(() => null);
      };

      updateCounters();
      window.setInterval(refreshStatuses, refreshIntervalMs);

      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
          refreshStatuses();
        }
      });
    });
  </script>
@endpush
