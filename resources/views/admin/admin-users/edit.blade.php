@extends('admin.layouts.app')

@php
  $title = 'Edit Admin';
@endphp

@section('content')
  @php
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
  @endphp

  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
      <div class="card mb-6">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4">
          <div>
            <span class="badge bg-label-primary mb-2">Admin Management</span>
            <h4 class="mb-1">Edit Admin</h4>
            <p class="text-muted mb-0">
              Update the local administrator profile, email identity, and account status from one place.
            </p>
          </div>
          <a href="{{ route('admin.admin-users.index') }}" class="btn btn-label-secondary">
            Back to List User Admin
          </a>
        </div>
      </div>

      <div class="row g-6">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-1">Admin Profile</h5>
              <small class="text-muted">Edit the main fields used to sign in to the admin dashboard.</small>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('admin.admin-users.update', $adminUser) }}" class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                  <label for="name" class="form-label">Full Name</label>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $adminUser->name) }}"
                    maxlength="120"
                    required />
                  @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="email" class="form-label">Email Address</label>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email', $adminUser->email) }}"
                    maxlength="255"
                    required />
                  @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="role_display" class="form-label">Role</label>
                  <input
                    type="text"
                    id="role_display"
                    class="form-control"
                    value="{{ ucfirst((string) $adminUser->role) }}"
                    disabled />
                  <small class="text-muted">This page only manages admin-role accounts.</small>
                </div>

                <div class="col-md-6">
                  <label for="is_active" class="form-label">Account Status</label>
                  <select
                    id="is_active"
                    name="is_active"
                    class="form-select @error('is_active') is-invalid @enderror"
                    required>
                    <option value="1" @selected((bool) old('is_active', $adminUser->is_active))>Active</option>
                    <option value="0" @selected(! (bool) old('is_active', $adminUser->is_active))>Inactive</option>
                  </select>
                  @error('is_active')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="password" class="form-label">New Password</label>
                  <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Leave blank to keep current password" />
                  @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="password_confirmation" class="form-label">Confirm New Password</label>
                  <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control"
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Repeat the new password" />
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                  <button type="submit" class="btn btn-primary">Save Changes</button>
                  <a href="{{ route('admin.admin-users.index') }}" class="btn btn-label-secondary">Cancel</a>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="mb-1">Account Snapshot</h5>
              <small class="text-muted">Quick reference before saving changes.</small>
            </div>
            <div class="card-body">
              <dl class="row mb-0 g-3">
                <dt class="col-sm-5 text-muted">Admin ID</dt>
                <dd class="col-sm-7 mb-0">{{ $adminUser->getKey() }}</dd>

                <dt class="col-sm-5 text-muted">Current Status</dt>
                <dd class="col-sm-7 mb-0">
                  <span class="badge {{ $adminUser->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                    {{ $adminUser->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </dd>

                <dt class="col-sm-5 text-muted">Last Login</dt>
                <dd class="col-sm-7 mb-0">{{ $formatDateTime($adminUser->last_login_at) }}</dd>

                <dt class="col-sm-5 text-muted">Created</dt>
                <dd class="col-sm-7 mb-0">{{ $formatDateTime($adminUser->created_at) }}</dd>

                <dt class="col-sm-5 text-muted">Updated</dt>
                <dd class="col-sm-7 mb-0">{{ $formatDateTime($adminUser->updated_at) }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
