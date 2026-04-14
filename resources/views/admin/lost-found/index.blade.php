@extends('admin.layouts.app')

@php
  $title = 'Lost & Found Management';
  $statusLabel = $statusLabel ?? static fn(string $value): string => ucfirst($value);
  $statusBadgeClass = $statusBadgeClass ?? static fn(string $value): string => 'bg-label-secondary';
  $imageUrl = $imageUrl ?? static fn(?string $path): ?string => null;
  $contactValue = $contactValue ?? static fn (?string $value): string => (string) $value;
  $formatDate = static function (mixed $value): string {
    if (blank($value)) {
      return '-';
    }

    try {
      return \Carbon\CarbonImmutable::parse((string) $value)->format('d M Y');
    } catch (\Throwable) {
      return (string) $value;
    }
  };
@endphp

@section('content')
  <div class="card mb-6">
    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4">
      <div>
        <span class="badge bg-label-warning mb-2">Report Management</span>
        <h4 class="mb-1">Lost &amp; Found Management</h4>
        <p class="text-muted mb-0">
          Manage and publish found items
        </p>
      </div>
      <a href="{{ route('admin.lost-found.create') }}" class="btn btn-primary" @disabled(!$storageReady)>Create Lost &amp; Found Item</a>
    </div>
  </div>

  @if (!$storageReady)
    <div class="alert alert-warning mb-6" role="alert">
      {{ $storageNotReadyMessage }}
    </div>
  @endif

  <div class="card">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-3">
      <div>
        <h5 class="mb-1">Found Item Library</h5>
        <small class="text-muted">Only items with status `Available` are exposed to the public `/found` page.</small>
      </div>
    </div>

    <div class="card-body border-bottom">
      <form method="GET" action="{{ route('admin.lost-found.index') }}" class="row g-4 align-items-end">
        <div class="col-md-5">
          <label for="q" class="form-label">Search</label>
          <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control"
            placeholder="title, description, location, contact, status" />
        </div>
        <div class="col-md-3">
          <label for="status" class="form-label">Status</label>
          <select id="status" name="status" class="form-select">
            <option value="all">All statuses</option>
            @foreach ($statusOptions as $option)
              <option value="{{ $option['value'] }}" @selected(($filters['status'] ?? 'all') === $option['value'])>
                {{ $option['label'] }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label for="per_page" class="form-label">Per Page</label>
          <select id="per_page" name="per_page" class="form-select">
            @foreach ([10, 25, 50, 100] as $perPageOption)
              <option value="{{ $perPageOption }}" @selected((int) ($filters['per_page'] ?? 10) === $perPageOption)>
                {{ $perPageOption }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100">Apply</button>
          <a href="{{ route('admin.lost-found.index') }}" class="btn btn-label-secondary">Reset</a>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>Image</th>
            <th>Item</th>
            <th>Location Found</th>
            <th>Found Date</th>
            <th>WhatsApp / Phone Number</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($items as $item)
            @php
              $thumbnail = $imageUrl($item->image_thumbnail_path ?: $item->image_path);
            @endphp
            <tr>
              <td style="width: 110px;">
                @if ($thumbnail)
                  <img src="{{ $thumbnail }}" alt="{{ $item->title }}" class="rounded-3 border"
                    style="width: 84px; height: 84px; object-fit: cover;" />
                @else
                  <div class="rounded-3 border d-flex align-items-center justify-content-center text-muted"
                    style="width: 84px; height: 84px;">
                    N/A
                  </div>
                @endif
              </td>
              <td style="min-width: 260px;">
                <div class="fw-semibold text-heading">{{ $item->title }}</div>
                <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($item->description, 120) }}</small>
              </td>
              <td style="min-width: 180px;">{{ $item->location_found }}</td>
              <td>{{ $formatDate($item->found_date) }}</td>
              <td style="min-width: 220px; white-space: pre-line;">{{ $contactValue($item->contact_info) }}</td>
              <td>
                <span class="badge {{ $statusBadgeClass($item->status) }}">
                  {{ $statusLabel($item->status) }}
                </span>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-2">
                  <a href="{{ route('admin.lost-found.edit', $item->id) }}" class="btn btn-sm btn-label-primary">Edit</a>
                  <form method="POST" action="{{ route('admin.lost-found.destroy', $item->id) }}"
                    onsubmit="return confirm('Delete this lost & found item?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-label-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-6 text-muted">
                No lost &amp; found items have been posted yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-body border-top">
      {{ $items->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  </div>
@endsection
