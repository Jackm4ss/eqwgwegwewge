@extends('admin.layouts.app')

@php
  $title = 'List User Admin';
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
          <span class="badge bg-label-primary mb-2">Admin Management</span>
          <h5 class="mb-1">List User Admin</h5>
          <p class="text-muted mb-0">
            View administrator accounts stored in the local SQL table, including account status,
            email identity, and live online presence.
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <small class="text-muted d-block mb-1">Total admins</small>
          <h4 class="mb-0">{{ number_format((int) ($summary['total'] ?? 0)) }}</h4>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <small class="text-muted d-block mb-1">Active accounts</small>
          <h4 class="mb-0">{{ number_format((int) ($summary['active'] ?? 0)) }}</h4>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <small class="text-muted d-block mb-1">Visible online</small>
          <h4 class="mb-0" data-online-count>{{ number_format($visibleOnlineCount) }}</h4>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <small class="text-muted d-block mb-1">Visible offline</small>
          <h4 class="mb-0" data-offline-count>{{ number_format($visibleOfflineCount) }}</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-6">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.admin-users.index') }}" class="row g-4 align-items-end">
        <div class="col-md-3">
          <label class="form-label" for="q">Search admin</label>
          <input
            type="text"
            class="form-control"
            id="q"
            name="q"
            value="{{ $filters['q'] ?? '' }}"
            placeholder="Search by admin name or email" />
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
            <a href="{{ route('admin.admin-users.index') }}" class="btn btn-label-danger flex-fill">
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
        <h5 class="mb-1">Admin Account Directory</h5>
        <small class="text-muted">{{ number_format($admins->total()) }} admin accounts found</small>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.admin-users.create') }}" class="btn btn-primary">
          Tambah Admin
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
                <span
                  class="badge {{ $isOnline ? 'bg-label-success' : 'bg-label-secondary' }}"
                  data-admin-presence-badge
                  data-admin-id="{{ $admin->getKey() }}"
                  data-online-class="bg-label-success"
                  data-offline-class="bg-label-secondary">
                  {{ $isOnline ? 'Online' : 'Offline' }}
                </span>
              </td>
              <td>{{ $formatDateTime($admin->last_login_at) }}</td>
              <td>{{ $formatDateTime($admin->created_at) }}</td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                  <a href="{{ route('admin.admin-users.edit', $admin) }}" class="btn btn-sm btn-label-primary">
                    Edit
                  </a>
                  @if ($isCurrentAdmin)
                    <button type="button" class="btn btn-sm btn-label-secondary" disabled>
                      Current account
                    </button>
                  @else
                    <form
                      method="POST"
                      action="{{ route('admin.admin-users.destroy', $admin) }}"
                      class="js-delete-admin-form d-inline">
                      @csrf
                      @method('DELETE')
                      <button
                        type="submit"
                        class="btn btn-sm btn-label-danger"
                        data-admin-name="{{ $admin->name }}"
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
              <td colspan="7" class="text-center py-6 text-muted">No admin accounts matched the current filters.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body border-top">
      <div class="small text-muted mb-3">
        Presence updates automatically every {{ (int) ($presence['heartbeat_seconds'] ?? 45) }} seconds for the admin accounts shown on this page,
        using cache heartbeat so the online indicator stays lightweight and does not write to the SQL admin table.
      </div>
      {{ $admins->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  </div>
@endsection

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
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
          const adminName = button?.dataset.adminName || 'this admin';
          const adminEmail = button?.dataset.adminEmail || '';

          event.preventDefault();

          const detailLine = adminEmail
            ? `${adminName} (${adminEmail})`
            : adminName;
          const safeDetailLine = escapeHtml(detailLine);
          const fallbackMessage = `${detailLine} will be removed from the admin directory and will no longer be able to sign in.`;

          let shouldDelete = false;

          if (window.Swal?.fire) {
            const result = await window.Swal.fire({
              title: 'Delete this admin account?',
              html: `
                <p class="mb-2 text-start">You are about to remove <strong>${safeDetailLine}</strong> from the admin directory.</p>
                <p class="mb-0 text-start text-muted">This account will immediately lose access to the admin dashboard.</p>
              `,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Yes, delete admin',
              cancelButtonText: 'Keep account',
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
            shouldDelete = window.confirm(`${fallbackMessage}\n\nContinue deleting this admin account?`);
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
