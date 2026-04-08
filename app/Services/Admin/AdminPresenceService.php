<?php

namespace App\Services\Admin;

use App\Models\Admin;
use Illuminate\Support\Facades\Cache;

class AdminPresenceService
{
    private const ONLINE_CACHE_KEY_PREFIX = 'admin:presence:';

    private const LAST_SEEN_CACHE_KEY_PREFIX = 'admin:presence:last-seen:';

    public function markOnline(Admin|int|string $admin): void
    {
        $timestamp = now()->toISOString();

        Cache::put(
            $this->onlineCacheKey($admin),
            $timestamp,
            now()->addSeconds($this->ttlSeconds()),
        );

        Cache::put(
            $this->lastSeenCacheKey($admin),
            $timestamp,
            now()->addDays($this->historyTtlDays()),
        );
    }

    public function markOffline(Admin|int|string $admin): void
    {
        Cache::put(
            $this->lastSeenCacheKey($admin),
            now()->toISOString(),
            now()->addDays($this->historyTtlDays()),
        );

        Cache::forget($this->onlineCacheKey($admin));
    }

    /**
     * @param  array<int, int|string>|int[]|string[]  $adminIds
     * @return array<string, bool>
     */
    public function statuses(array $adminIds): array
    {
        $normalizedIds = $this->normalizeAdminIds($adminIds);

        if ($normalizedIds === []) {
            return [];
        }

        $values = Cache::many(array_map(
            fn (string $adminId): string => $this->onlineCacheKey($adminId),
            $normalizedIds,
        ));

        $statuses = [];

        foreach ($normalizedIds as $adminId) {
            $statuses[$adminId] = ! blank($values[$this->onlineCacheKey($adminId)] ?? null);
        }

        return $statuses;
    }

    /**
     * @param  array<int, int|string>|int[]|string[]  $adminIds
     * @return array<string, string|null>
     */
    public function lastSeenTimestamps(array $adminIds): array
    {
        $normalizedIds = $this->normalizeAdminIds($adminIds);

        if ($normalizedIds === []) {
            return [];
        }

        $values = Cache::many(array_map(
            fn (string $adminId): string => $this->lastSeenCacheKey($adminId),
            $normalizedIds,
        ));

        $timestamps = [];

        foreach ($normalizedIds as $adminId) {
            $value = $values[$this->lastSeenCacheKey($adminId)] ?? null;
            $timestamps[$adminId] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        return $timestamps;
    }

    public function isOnline(Admin|int|string $admin): bool
    {
        return ! blank(Cache::get($this->onlineCacheKey($admin)));
    }

    public function heartbeatSeconds(): int
    {
        return max(15, (int) config('admin.presence.heartbeat_seconds', 45));
    }

    public function ttlSeconds(): int
    {
        return max(
            $this->heartbeatSeconds() + 15,
            (int) config('admin.presence.ttl_seconds', 120),
        );
    }

    public function historyTtlDays(): int
    {
        return max(1, (int) config('admin.presence.history_ttl_days', 30));
    }

    private function onlineCacheKey(Admin|int|string $admin): string
    {
        $adminId = $this->normalizeAdminId($admin);

        return self::ONLINE_CACHE_KEY_PREFIX.$adminId;
    }

    private function lastSeenCacheKey(Admin|int|string $admin): string
    {
        $adminId = $this->normalizeAdminId($admin);

        return self::LAST_SEEN_CACHE_KEY_PREFIX.$adminId;
    }

    private function normalizeAdminId(Admin|int|string $admin): string
    {
        return $admin instanceof Admin
            ? (string) $admin->getKey()
            : trim((string) $admin);
    }

    /**
     * @param  array<int, int|string>|int[]|string[]  $adminIds
     * @return array<int, string>
     */
    private function normalizeAdminIds(array $adminIds): array
    {
        return collect($adminIds)
            ->map(fn (mixed $adminId): string => trim((string) $adminId))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
