@extends('admin.layouts.app')

@php
  $title = 'Create Lost & Found Item';
@endphp

@section('content')
  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
      <div class="card mb-6">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4">
          <div>
            <span class="badge bg-label-warning mb-2">Report Management</span>
            <h4 class="mb-1">Create Lost &amp; Found Item</h4>
            <p class="text-muted mb-0">
              Post a newly found item so the admin team and public `/found` page stay in sync.
            </p>
          </div>
          <a href="{{ route('admin.lost-found.index') }}" class="btn btn-label-secondary">Back to Lost &amp; Found List</a>
        </div>
      </div>

      @if (! $storageReady)
        <div class="alert alert-warning mb-6" role="alert">
          {{ $storageNotReadyMessage }}
        </div>
      @endif

      <div class="card">
        <div class="card-header">
          <h5 class="mb-1">Found Item Details</h5>
          <small class="text-muted">Every new image will be compressed automatically before it is saved.</small>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.lost-found.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.lost-found._form', [
              'statusOptions' => $statusOptions,
              'storageReady' => $storageReady,
              'submitLabel' => 'Create Item',
            ])
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
