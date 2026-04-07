@once
  @push('page-scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const widgets = document.querySelectorAll('[data-sync-status-widget]');

        if (!widgets.length) {
          return;
        }

        const stateMeta = {
          fresh: {
            badgeClass: 'bg-label-success text-success',
            iconClass: 'tabler-clock-hour-4',
            relativeLabel: (countdownSeconds) => countdownSeconds > 0 ? `Refresh in ${countdownSeconds}s` : 'Refresh now',
            helperLabel: (countdownSeconds) => countdownSeconds > 0
              ? 'After the timer ends, refresh browser to see the latest data.'
              : 'Refresh browser now to see the latest data.',
          },
          degraded: {
            badgeClass: 'bg-label-warning text-warning',
            iconClass: 'tabler-clock-hour-4',
            relativeLabel: () => 'Refresh now',
            helperLabel: () => 'Refresh browser now to see the latest data.',
          },
          rebuilding: {
            badgeClass: 'bg-label-info text-info',
            iconClass: 'tabler-refresh',
            relativeLabel: () => 'Updating data...',
            helperLabel: () => 'Please wait while data is being updated.',
          },
          fallback: {
            badgeClass: 'bg-label-secondary text-secondary',
            iconClass: 'tabler-database-exclamation',
            relativeLabel: () => 'Refresh now',
            helperLabel: () => 'Refresh browser now. If it still looks old, wait a moment and try again.',
          },
        };

        const applyState = function (widget, state, countdownSeconds) {
          const badge = widget.querySelector('[data-sync-badge]');
          const icon = widget.querySelector('[data-sync-icon]');
          const relative = widget.querySelector('[data-sync-relative]');
          const helper = widget.querySelector('[data-sync-helper]');
          const meta = stateMeta[state] || stateMeta.fallback;

          if (badge) {
            badge.className = `badge rounded-pill ${meta.badgeClass} d-inline-flex align-items-center gap-2 px-3 py-2`;
          }

          if (icon) {
            icon.className = `icon-base ti ${meta.iconClass}`;
          }

          if (relative) {
            relative.textContent = meta.relativeLabel(countdownSeconds);
          }

          if (helper) {
            helper.textContent = meta.helperLabel(countdownSeconds);
          }
        };

        const refreshWidget = function (widget) {
          const timestamp = widget.dataset.syncLastSyncedAt;
          const explicitState = widget.dataset.syncState || 'fallback';
          const freshWithinSeconds = Number(widget.dataset.syncFreshWithin || 15);
          const degradedAfterSeconds = Number(widget.dataset.syncDegradedAfter || 60);
          const fallbackAfterSeconds = Number(widget.dataset.syncFallbackAfter || 300);

          if (!timestamp || explicitState === 'rebuilding') {
            applyState(widget, explicitState, 0);
            return;
          }

          const ageSeconds = Math.max(0, Math.floor((Date.now() - new Date(timestamp).getTime()) / 1000));
          const countdownSeconds = Math.max(0, freshWithinSeconds - Math.min(ageSeconds, freshWithinSeconds));
          let nextState = explicitState;

          if (explicitState !== 'fallback') {
            if (ageSeconds <= freshWithinSeconds) {
              nextState = 'fresh';
            } else if (ageSeconds <= degradedAfterSeconds) {
              nextState = 'fresh';
            } else if (ageSeconds <= fallbackAfterSeconds) {
              nextState = 'degraded';
            } else {
              nextState = 'fallback';
            }
          }

          applyState(widget, nextState, countdownSeconds);
        };

        widgets.forEach(refreshWidget);
        window.setInterval(function () {
          widgets.forEach(refreshWidget);
        }, 1000);
      });
    </script>
  @endpush
@endonce
