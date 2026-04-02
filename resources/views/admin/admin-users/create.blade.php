@extends('admin.layouts.app')

@php
  $title = 'Create Admin';
@endphp

@section('content')
  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
      <div class="card mb-6">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4">
          <div>
            <span class="badge bg-label-primary mb-2">Admin Management</span>
            <h4 class="mb-1">Create Admin</h4>
            <p class="text-muted mb-0">
              Add a new local admin account so the team can sign in to the dashboard with its own credentials.
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
              <h5 class="mb-1">New Admin Profile</h5>
              <small class="text-muted">Create a new administrator account in the local SQL admin table.</small>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('admin.admin-users.store') }}" class="row g-4">
                @csrf

                <div class="col-md-6">
                  <label for="name" class="form-label">Full Name</label>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}"
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
                    value="{{ old('email') }}"
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
                    value="Admin"
                    disabled />
                  <small class="text-muted">This page creates dashboard admin accounts only.</small>
                </div>

                <div class="col-md-6">
                  <label for="is_active" class="form-label">Account Status</label>
                  <select
                    id="is_active"
                    name="is_active"
                    class="form-select @error('is_active') is-invalid @enderror"
                    required>
                    <option value="1" @selected((bool) old('is_active', true))>Active</option>
                    <option value="0" @selected(! (bool) old('is_active', true))>Inactive</option>
                  </select>
                  @error('is_active')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="password" class="form-label">Initial Password</label>
                  <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    minlength="8"
                    autocomplete="new-password"
                    required />
                  @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="password_confirmation" class="form-label">Confirm Password</label>
                  <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control"
                    minlength="8"
                    autocomplete="new-password"
                    required />
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                  <button type="submit" class="btn btn-primary">Create Admin</button>
                  <a href="{{ route('admin.admin-users.index') }}" class="btn btn-label-secondary">Cancel</a>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-1">Provisioning Notes</h5>
              <small class="text-muted">Quick reminders before the account goes live.</small>
            </div>
            <div class="card-body">
              <ul class="mb-0 ps-3 text-muted">
                <li>Use a unique email address for each admin account.</li>
                <li>Set the initial password to at least 8 characters.</li>
                <li>Inactive admins stay listed but cannot access the dashboard.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
