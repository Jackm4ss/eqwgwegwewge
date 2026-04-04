@extends('admin.layouts.app')

@php
  $management = array_merge([
    'badge' => 'Admin Management',
    'edit_page_title' => 'Edit Admin',
    'edit_heading' => 'Edit Admin',
    'edit_description' => 'Update the local administrator profile, email identity, and account status from one place.',
    'index_route' => 'admin.admin-users.index',
    'back_to_list_label' => 'Back to List User Admin',
    'edit_card_title' => 'Admin Profile',
    'edit_card_description' => 'Edit the main fields used to sign in to the admin dashboard.',
    'update_route' => 'admin.admin-users.update',
    'edit_role_display_help' => 'This page only manages admin-role accounts.',
    'save_button_label' => 'Save Changes',
    'cancel_label' => 'Cancel',
    'snapshot_title' => 'Account Snapshot',
    'snapshot_description' => 'Quick reference before saving changes.',
    'snapshot_id_label' => 'Admin ID',
  ], $management ?? []);
  $title = $management['edit_page_title'];
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
            <span class="badge bg-label-primary mb-2">{{ $management['badge'] }}</span>
            <h4 class="mb-1">{{ $management['edit_heading'] }}</h4>
            <p class="text-muted mb-0">
              {{ $management['edit_description'] }}
            </p>
          </div>
          <a href="{{ route($management['index_route']) }}" class="btn btn-label-secondary">
            {{ $management['back_to_list_label'] }}
          </a>
        </div>
      </div>

      <div class="row g-6">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-1">{{ $management['edit_card_title'] }}</h5>
              <small class="text-muted">{{ $management['edit_card_description'] }}</small>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route($management['update_route'], $adminUser) }}" class="row g-4">
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
                  <small class="text-muted">{{ $management['edit_role_display_help'] }}</small>
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
                  <button type="submit" class="btn btn-primary">{{ $management['save_button_label'] }}</button>
                  <a href="{{ route($management['index_route']) }}" class="btn btn-label-secondary">{{ $management['cancel_label'] }}</a>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="mb-1">{{ $management['snapshot_title'] }}</h5>
              <small class="text-muted">{{ $management['snapshot_description'] }}</small>
            </div>
            <div class="card-body">
              <dl class="row mb-0 g-3">
                <dt class="col-sm-5 text-muted">{{ $management['snapshot_id_label'] }}</dt>
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
