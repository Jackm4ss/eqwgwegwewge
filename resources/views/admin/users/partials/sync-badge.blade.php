@php
  $syncStatus = is_array($syncStatus ?? null) ? $syncStatus : [];
  $badgeState = (string) ($syncStatus['state'] ?? 'fallback');
  $badgeMeta = match ($badgeState) {
    'fresh' => ['class' => 'bg-label-success text-success', 'icon' => 'tabler-circle-check'],
    'degraded' => ['class' => 'bg-label-warning text-warning', 'icon' => 'tabler-clock-hour-4'],
    'rebuilding' => ['class' => 'bg-label-info text-info', 'icon' => 'tabler-refresh'],
    default => ['class' => 'bg-label-secondary text-secondary', 'icon' => 'tabler-database-exclamation'],
  };
  $syncBadgeId = $syncBadgeId ?? ('user-sync-status-' . \Illuminate\Support\Str::ulid());
  $syncBadgeClass = trim('user-sync-status ' . ($syncBadgeClass ?? ''));
@endphp

<div id="{{ $syncBadgeId }}" class="{{ $syncBadgeClass }}" data-sync-status-widget
  data-sync-source="{{ $syncStatus['source'] ?? 'read_model' }}"
  data-sync-state="{{ $badgeState }}"
  data-sync-last-synced-at="{{ $syncStatus['last_synced_at_utc'] ?? '' }}"
  data-sync-fresh-within="{{ (int) ($syncStatus['fresh_within_seconds'] ?? 15) }}"
  data-sync-degraded-after="{{ (int) ($syncStatus['degraded_after_seconds'] ?? 60) }}"
  data-sync-fallback-after="{{ (int) ($syncStatus['fallback_after_seconds'] ?? 300) }}">
  <span class="badge rounded-pill {{ $badgeMeta['class'] }} d-inline-flex align-items-center gap-2 px-3 py-2"
    title="{{ $syncStatus['last_synced_label'] ?? 'Last update time unavailable' }}" data-sync-badge>
    <i class="icon-base ti {{ $badgeMeta['icon'] }}" data-sync-icon></i>
    <span data-sync-relative>{{ $syncStatus['relative_label'] ?? 'Refresh now' }}</span>
  </span>
  <small class="text-muted d-block mt-2" data-sync-helper>
    {{ $syncStatus['helper_label'] ?? 'Refresh browser now to see the latest data.' }}
  </small>
</div>
