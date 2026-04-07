<?php

namespace App\Services\Admin;

use Carbon\CarbonImmutable;

class AdminUserManagementSyncStatusFactory
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
        $freshWithinSeconds = max(5, (int) config('admin.user_management.read_model.fresh_within_seconds', 15));
        $degradedAfterSeconds = max(
            $freshWithinSeconds + 1,
            (int) config('admin.user_management.read_model.degraded_after_seconds', 60),
        );
        $fallbackAfterSeconds = max(
            $degradedAfterSeconds + 1,
            (int) config('admin.user_management.read_model.fallback_after_seconds', 300),
        );
        $malaysiaTimezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));
        $parsedTimestamp = $this->parseUtcTimestamp($lastSyncedAtUtc);
        $ageSeconds = $parsedTimestamp?->diffInSeconds(CarbonImmutable::now('UTC')) ?? null;
        $resolvedState = $this->resolveState(
            $state,
            $ageSeconds,
            $freshWithinSeconds,
            $degradedAfterSeconds,
            $fallbackAfterSeconds,
        );
        $lastSyncedAtMalaysia = $parsedTimestamp?->setTimezone($malaysiaTimezone);

        return [
            'source' => $source,
            'state' => $resolvedState,
            'last_synced_at_utc' => $parsedTimestamp?->toIso8601String(),
            'last_synced_at_myt' => $lastSyncedAtMalaysia?->toIso8601String(),
            'last_synced_label' => $lastSyncedAtMalaysia !== null
                ? 'Last synced at '.$lastSyncedAtMalaysia->format('H:i:s').' Malaysia Time'
                : 'Last sync time unavailable',
            'relative_label' => $this->relativeLabel($resolvedState, $ageSeconds),
            'age_seconds' => $ageSeconds,
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

    private function relativeLabel(string $state, ?int $ageSeconds): string
    {
        return match ($state) {
            'fresh' => 'Data synced '.max(0, (int) $ageSeconds).'s ago',
            'degraded' => 'Data delayed',
            'rebuilding' => 'Sync in progress',
            default => 'Showing last available data',
        };
    }
}
