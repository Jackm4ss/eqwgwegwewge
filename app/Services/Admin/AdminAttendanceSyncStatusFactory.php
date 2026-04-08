<?php

namespace App\Services\Admin;

use Carbon\CarbonImmutable;

class AdminAttendanceSyncStatusFactory
{
    public function live(string $source = 'live_document'): array
    {
        return $this->make(now('UTC')->toIso8601String(), $source, 'fresh');
    }

    public function make(
        ?string $lastSyncedAtUtc,
        string $source = 'read_model',
        ?string $state = null,
    ): array {
        $freshWithinSeconds = max(5, (int) config('admin.attendance.read_model.fresh_within_seconds', 15));
        $degradedAfterSeconds = max(
            $freshWithinSeconds + 1,
            (int) config('admin.attendance.read_model.degraded_after_seconds', 60),
        );
        $fallbackAfterSeconds = max(
            $degradedAfterSeconds + 1,
            (int) config('admin.attendance.read_model.fallback_after_seconds', 300),
        );
        $eventTimezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));
        $parsedTimestamp = $this->parseUtcTimestamp($lastSyncedAtUtc);
        $ageSeconds = $parsedTimestamp?->diffInSeconds(CarbonImmutable::now('UTC')) ?? null;
        $resolvedState = $this->resolveState(
            $state,
            $ageSeconds,
            $freshWithinSeconds,
            $degradedAfterSeconds,
            $fallbackAfterSeconds,
        );
        $lastSyncedAtEventTimezone = $parsedTimestamp?->setTimezone($eventTimezone);
        $refreshCountdownSeconds = $this->refreshCountdownSeconds(
            $resolvedState,
            $ageSeconds,
            $freshWithinSeconds,
        );

        return [
            'source' => $source,
            'state' => $resolvedState,
            'last_synced_at_utc' => $parsedTimestamp?->toIso8601String(),
            'last_synced_at_myt' => $lastSyncedAtEventTimezone?->toIso8601String(),
            'last_synced_label' => $lastSyncedAtEventTimezone !== null
                ? 'Updated at '.$lastSyncedAtEventTimezone->format('H:i:s').' Malaysia time'
                : 'Last update time unavailable',
            'relative_label' => $this->relativeLabel($resolvedState, $refreshCountdownSeconds),
            'helper_label' => $this->helperLabel($resolvedState, $refreshCountdownSeconds),
            'age_seconds' => $ageSeconds,
            'refresh_countdown_seconds' => $refreshCountdownSeconds,
            'fresh_within_seconds' => $freshWithinSeconds,
            'degraded_after_seconds' => $degradedAfterSeconds,
            'fallback_after_seconds' => $fallbackAfterSeconds,
        ];
    }

    private function parseUtcTimestamp(?string $timestamp): ?CarbonImmutable
    {
        if (! is_string($timestamp) || trim($timestamp) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($timestamp)->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveState(
        ?string $state,
        ?int $ageSeconds,
        int $freshWithinSeconds,
        int $degradedAfterSeconds,
        int $fallbackAfterSeconds,
    ): string {
        $normalizedState = strtolower(trim((string) $state));

        if (in_array($normalizedState, ['fresh', 'degraded', 'rebuilding', 'fallback'], true)) {
            return $normalizedState;
        }

        if ($ageSeconds === null) {
            return 'fallback';
        }

        if ($ageSeconds <= $freshWithinSeconds) {
            return 'fresh';
        }

        if ($ageSeconds <= $degradedAfterSeconds) {
            return 'fresh';
        }

        if ($ageSeconds <= $fallbackAfterSeconds) {
            return 'degraded';
        }

        return 'fallback';
    }

    private function refreshCountdownSeconds(
        string $state,
        ?int $ageSeconds,
        int $freshWithinSeconds,
    ): ?int {
        if ($state === 'rebuilding') {
            return null;
        }

        if ($ageSeconds === null) {
            return 0;
        }

        return max(0, $freshWithinSeconds - max(0, $ageSeconds));
    }

    private function relativeLabel(string $state, ?int $refreshCountdownSeconds): string
    {
        return match ($state) {
            'rebuilding' => 'Updating data...',
            default => $refreshCountdownSeconds !== null && $refreshCountdownSeconds > 0
                ? 'Refresh in '.$refreshCountdownSeconds.'s'
                : 'Refresh now',
        };
    }

    private function helperLabel(string $state, ?int $refreshCountdownSeconds): string
    {
        if ($state === 'rebuilding') {
            return 'Please wait while data is being updated.';
        }

        if ($refreshCountdownSeconds !== null && $refreshCountdownSeconds > 0) {
            return 'After the timer ends, refresh browser to see the latest data.';
        }

        return match ($state) {
            'fallback' => 'Refresh browser now. If it still looks old, wait a moment and try again.',
            default => 'Refresh browser now to see the latest data.',
        };
    }
}
