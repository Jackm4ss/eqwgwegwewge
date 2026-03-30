@extends('admin.layouts.app')

@php
  $title = 'Gate Management';
@endphp

@push('vendor-styles')
  <style>
    .gate-hero {
      position: relative;
      overflow: hidden;
      border: 0;
      border-radius: 1.75rem;
      background:
        radial-gradient(circle at top right, rgba(255, 255, 255, 0.24), transparent 32%),
        linear-gradient(135deg, #0f5f8f 0%, #0f88b8 58%, #2bb4d6 100%);
      color: #fff;
      box-shadow: 0 1.4rem 3rem rgba(15, 95, 143, 0.22);
    }

    .gate-hero::after {
      content: '';
      position: absolute;
      right: -4rem;
      bottom: -5rem;
      width: 15rem;
      height: 15rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
    }

    .gate-card,
    .gate-table-card,
    .gate-create-card,
    .gate-guide-card {
      border: 1px solid rgba(67, 89, 113, 0.12);
      border-radius: 1.25rem;
      box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.04);
    }

    .gate-stat {
      border-radius: 1rem;
      border: 1px solid rgba(255, 255, 255, 0.18);
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(10px);
    }

    .gate-table td {
      vertical-align: middle;
    }

    .gate-sidebar {
      display: grid;
      gap: 1rem;
    }

    .gate-guide-card {
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(245, 247, 250, 0.98)),
        radial-gradient(circle at top right, rgba(15, 136, 184, 0.08), transparent 42%);
    }

    .gate-guide-list {
      display: grid;
      gap: 0.9rem;
      margin: 0;
      padding: 0;
      list-style: none;
    }

    .gate-guide-item {
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 0.85rem;
      align-items: start;
    }

    .gate-guide-index {
      width: 2rem;
      height: 2rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      background: rgba(var(--bs-primary-rgb), 0.12);
      color: var(--bs-primary);
      font-weight: 700;
      font-size: 0.875rem;
      flex: 0 0 auto;
    }

    .gate-preview-list {
      display: grid;
      gap: 0.75rem;
    }

    .gate-preview-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      padding: 0.85rem 1rem;
      border-radius: 1rem;
      border: 1px solid rgba(67, 89, 113, 0.12);
      background: rgba(255, 255, 255, 0.88);
    }

    .gate-preview-order {
      width: 2rem;
      height: 2rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      background: rgba(var(--bs-primary-rgb), 0.12);
      color: var(--bs-primary);
      font-weight: 700;
      font-size: 0.85rem;
      flex: 0 0 auto;
    }

    .gate-editor-stack {
      display: grid;
      gap: 1rem;
    }

    .gate-editor-card {
      border: 1px solid rgba(67, 89, 113, 0.12);
      border-radius: 1.25rem;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 249, 251, 0.98)),
        radial-gradient(circle at top right, rgba(15, 136, 184, 0.08), transparent 42%);
      padding: 1.2rem;
      box-shadow: 0 0.85rem 1.8rem rgba(15, 23, 42, 0.04);
    }

    .gate-editor-card.is-primary {
      border-color: rgba(var(--bs-primary-rgb), 0.24);
      box-shadow: 0 1rem 2.1rem rgba(var(--bs-primary-rgb), 0.12);
    }

    .gate-editor-card.has-errors {
      border-color: rgba(var(--bs-danger-rgb), 0.26);
      box-shadow: 0 1rem 2rem rgba(var(--bs-danger-rgb), 0.08);
    }

    .gate-editor-head {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .gate-editor-title {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.6rem;
      margin-bottom: 0.4rem;
    }

    .gate-editor-title h6 {
      margin: 0;
      color: var(--bs-heading-color);
    }

    .gate-editor-order-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 2.25rem;
      height: 2.25rem;
      padding: 0 0.75rem;
      border-radius: 999px;
      background: rgba(var(--bs-primary-rgb), 0.12);
      color: var(--bs-primary);
      font-weight: 700;
      font-size: 0.85rem;
    }

    .gate-editor-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.9rem;
      color: var(--bs-secondary-color);
      font-size: 0.8125rem;
    }

    .gate-editor-meta span {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .gate-editor-actions {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px dashed rgba(67, 89, 113, 0.14);
    }

    .gate-empty-state {
      border: 1px dashed rgba(67, 89, 113, 0.18);
      border-radius: 1.25rem;
      padding: 2rem 1.5rem;
      text-align: center;
      background: rgba(255, 255, 255, 0.68);
    }

    @media (min-width: 1200px) {
      .gate-sidebar {
        position: sticky;
        top: 1.5rem;
      }
    }
  </style>
@endpush

@section('content')
  @php
    $formatDateTime = static function (mixed $value): string {
      if (blank($value)) {
        return '-';
      }

      try {
        return \Carbon\CarbonImmutable::parse((string) $value)
          ->setTimezone(config('app.timezone'))
          ->format('d M Y, h:i A');
      } catch (\Throwable) {
        return (string) $value;
      }
    };

    $submittedGateId = old('gate_id');
    $createSubmissionAttempted = blank($submittedGateId) && (old('name') !== null || old('sort_order') !== null);
    $createHasErrors = $createSubmissionAttempted && ($errors->has('name') || $errors->has('sort_order'));
  @endphp

  <div class="card gate-hero mb-6">
    <div class="card-body p-5 p-lg-6">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <span class="badge rounded-pill bg-white text-primary mb-3">Scanner Configuration</span>
          <h3 class="text-white mb-2">Manage the gate list for all scanner operators</h3>
          <p class="mb-0 text-white-50">
            Gates created here are used immediately in staff login, gate switching on the scanner page,
            and all daily scanning activity.
          </p>
        </div>
        <div class="col-lg-5">
          <div class="row g-3">
            <div class="col-sm-4">
              <div class="gate-stat p-3 h-100">
                <div class="small text-white-50 mb-1">Total gates</div>
                <h3 class="text-white mb-0">{{ number_format($overview['total'] ?? 0) }}</h3>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="gate-stat p-3 h-100">
                <div class="small text-white-50 mb-1">Primary</div>
                <div class="fw-semibold text-white">{{ $overview['primary'] ?? '-' }}</div>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="gate-stat p-3 h-100">
                <div class="small text-white-50 mb-1">Updated</div>
                <div class="fw-semibold text-white">{{ $formatDateTime($overview['latest_update'] ?? null) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @unless ($storageReady)
    <div class="alert alert-warning d-flex align-items-start gap-3 mb-4" role="alert">
      <i class="icon-base tabler tabler-alert-triangle mt-1"></i>
      <div>
        <div class="fw-semibold mb-1">Gate Management is not ready yet</div>
        <div class="text-body">
          The <code>scanner_gates</code> table is not available yet, so gate changes cannot be saved from the dashboard.
          Run <code>php artisan migrate</code> first. Until then, the scanner page will continue using the fallback gates from the application configuration.
        </div>
      </div>
    </div>
  @endunless

  <div class="row g-4">
    <div class="col-xl-4">
      <div class="gate-sidebar">
        <div class="card gate-create-card">
          <div class="card-body p-4">
            <div class="mb-4">
              <h5 class="mb-1">Create Gate</h5>
              <p class="text-muted mb-0">Add a gate once, then fine-tune its label and ordering from the management cards.</p>
            </div>

            <form method="POST" action="{{ route('admin.gates.store') }}" class="row g-3">
              @csrf
              @if ($createHasErrors)
                <div class="col-12">
                  <div class="alert alert-danger mb-0" role="alert">
                    Please review the highlighted fields before creating a new gate.
                  </div>
                </div>
              @endif
              <div class="col-12">
                <label class="form-label" for="new-gate-name">Gate name</label>
                <input
                  id="new-gate-name"
                  type="text"
                  name="name"
                  value="{{ old('name') }}"
                  @class(['form-control', 'is-invalid' => $createHasErrors && $errors->has('name')])
                  placeholder="Example: VIP Gate"
                  @disabled(! $storageReady)
                  required />
                @if ($createHasErrors && $errors->has('name'))
                  <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                @endif
              </div>
              <div class="col-12">
                <label class="form-label" for="new-gate-sort-order">Display order</label>
                <input
                  id="new-gate-sort-order"
                  type="number"
                  min="0"
                  max="999"
                  name="sort_order"
                  value="{{ old('sort_order', $gates->count()) }}"
                  @class(['form-control', 'is-invalid' => $createHasErrors && $errors->has('sort_order')])
                  placeholder="0"
                  @disabled(! $storageReady) />
                @if ($createHasErrors && $errors->has('sort_order'))
                  <div class="invalid-feedback">{{ $errors->first('sort_order') }}</div>
                @else
                  <div class="form-text">Lower numbers appear first on the login and scanner pages.</div>
                @endif
              </div>
              <div class="col-12 d-grid">
                <button type="submit" class="btn btn-primary" @disabled(! $storageReady)>Add Gate</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card gate-guide-card">
          <div class="card-body p-4">
            <div class="mb-4">
              <h5 class="mb-1">Scanner Order Preview</h5>
              <p class="text-muted mb-0">This is the live order scanner staff will see after your changes are saved.</p>
            </div>

            @if ($gates->isEmpty())
              <div class="gate-empty-state">
                <div class="fw-semibold mb-1">No gates to preview</div>
                <div class="text-muted">Create the first gate to start building your scanner flow.</div>
              </div>
            @else
              <div class="gate-preview-list">
                @foreach ($gates as $gate)
                  <div class="gate-preview-item">
                    <div class="d-flex align-items-center gap-3">
                      <span class="gate-preview-order">{{ $loop->iteration }}</span>
                      <div>
                        <div class="fw-semibold text-heading">{{ $gate->name }}</div>
                        <small class="text-muted">Display order {{ $gate->sort_order }}</small>
                      </div>
                    </div>
                    @if ($loop->first)
                      <span class="badge bg-label-primary">Primary</span>
                    @endif
                  </div>
                @endforeach
              </div>
            @endif

            <hr class="my-4">

            <ul class="gate-guide-list">
              <li class="gate-guide-item">
                <span class="gate-guide-index">1</span>
                <div>
                  <div class="fw-semibold text-heading">Use short, recognizable names</div>
                  <small class="text-muted">Gate names should be easy for scanner staff to identify at a glance.</small>
                </div>
              </li>
              <li class="gate-guide-item">
                <span class="gate-guide-index">2</span>
                <div>
                  <div class="fw-semibold text-heading">Keep the order intentional</div>
                  <small class="text-muted">Lower order values appear first everywhere staff choose a gate.</small>
                </div>
              </li>
              <li class="gate-guide-item">
                <span class="gate-guide-index">3</span>
                <div>
                  <div class="fw-semibold text-heading">Save gate-by-gate</div>
                  <small class="text-muted">Each card is independent, so admins can update one gate without affecting the rest.</small>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <div class="card gate-table-card">
        <div class="card-body p-4">
          <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div>
              <h5 class="mb-1">Manage Active Gates</h5>
              <p class="text-muted mb-0">Every gate is editable in its own card, with clearer save and delete actions.</p>
            </div>
            <div class="text-muted small">
              Changes take effect as soon as the corresponding gate card is saved.
            </div>
          </div>

          @if (! $storageReady)
            <div class="alert alert-warning mb-0" role="alert">
              Gate data cannot be managed yet because the database table has not been created. Run
              <code>php artisan migrate</code>, then refresh this page.
            </div>
          @elseif ($gates->isEmpty())
            <div class="gate-empty-state">
              <div class="fw-semibold text-heading mb-2">No active gates yet</div>
              <p class="text-muted mb-0">Create the first gate from the left panel so scanner staff can start selecting an active gate.</p>
            </div>
          @else
            <div class="gate-editor-stack">
              @foreach ($gates as $gate)
                @php
                  $isEditingThisGate = filled($submittedGateId) && (string) $submittedGateId === (string) $gate->id;
                  $updateHasErrors = $isEditingThisGate && ($errors->has('name') || $errors->has('sort_order'));
                @endphp
                <article @class([
                  'gate-editor-card',
                  'is-primary' => $loop->first,
                  'has-errors' => $updateHasErrors,
                ])>
                  <div class="gate-editor-head">
                    <div>
                      <div class="gate-editor-title">
                        <span class="gate-editor-order-badge">#{{ $loop->iteration }}</span>
                        <h6>{{ $gate->name }}</h6>
                        @if ($loop->first)
                          <span class="badge bg-label-primary">Primary</span>
                        @endif
                      </div>
                      <div class="gate-editor-meta">
                        <span>
                          <i class="icon-base ti tabler-clock-hour-4"></i>
                          Updated {{ $formatDateTime($gate->updated_at) }}
                        </span>
                        <span>
                          <i class="icon-base ti tabler-scan"></i>
                          Available to scanner staff
                        </span>
                      </div>
                    </div>
                  </div>

                  @if ($updateHasErrors)
                    <div class="alert alert-danger mb-3" role="alert">
                      This gate could not be saved yet. Review the fields below and try again.
                    </div>
                  @endif

                  <form id="gate-update-{{ $gate->id }}" method="POST" action="{{ route('admin.gates.update', $gate) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="gate_id" value="{{ $gate->id }}">

                    <div class="row g-3">
                      <div class="col-lg-8">
                        <label class="form-label" for="gate-name-{{ $gate->id }}">Gate name</label>
                        <input
                          id="gate-name-{{ $gate->id }}"
                          type="text"
                          name="name"
                          value="{{ $isEditingThisGate ? old('name', $gate->name) : $gate->name }}"
                          @class(['form-control', 'is-invalid' => $updateHasErrors && $errors->has('name')])
                          required />
                        @if ($updateHasErrors && $errors->has('name'))
                          <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                      </div>
                      <div class="col-sm-6 col-lg-4">
                        <label class="form-label" for="gate-order-{{ $gate->id }}">Display order</label>
                        <input
                          id="gate-order-{{ $gate->id }}"
                          type="number"
                          min="0"
                          max="999"
                          name="sort_order"
                          value="{{ $isEditingThisGate ? old('sort_order', $gate->sort_order) : $gate->sort_order }}"
                          @class(['form-control', 'is-invalid' => $updateHasErrors && $errors->has('sort_order')]) />
                        @if ($updateHasErrors && $errors->has('sort_order'))
                          <div class="invalid-feedback">{{ $errors->first('sort_order') }}</div>
                        @endif
                      </div>
                    </div>
                  </form>

                  <div class="gate-editor-actions">
                    <div class="text-muted small">
                      Lower display order values appear earlier in staff gate selectors.
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                      <button type="submit" form="gate-update-{{ $gate->id }}" class="btn btn-primary btn-sm">
                        Save Changes
                      </button>
                      <form method="POST" action="{{ route('admin.gates.destroy', $gate) }}" onsubmit="return confirm('Delete gate {{ $gate->name }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-label-danger btn-sm">Delete Gate</button>
                      </form>
                    </div>
                  </div>
                </article>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
