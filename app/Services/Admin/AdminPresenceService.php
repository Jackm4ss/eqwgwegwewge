<?php

namespace App\Services\Admin;

use App\Models\Admin;
use Illuminate\Support\Facades\Cache;

class AdminPresenceService
{
    private const CACHE_KEY_PREFIX = 'admin:presence:';

    public function markOnline(Admin|int|string $admin): void
    {
        Cache::put(
            $this->cacheKey($admin),
            now()->toISOString(),
            now()->addSeconds($this->ttlSeconds()),
        );
    }

    public function markOffline(Admin|int|string $admin): void
    {
        Cache::forget($this->cacheKey($admin));
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
            fn (string $adminId): string => $this->cacheKey($adminId),
            $normalizedIds,
        ));

        $statuses = [];

        foreach ($normalizedIds as $adminId) {
            $statuses[$adminId] = ! blank($values[$this->cacheKey($adminId)] ?? null);
        }

        return $statuses;
    }

    public function isOnline(Admin|int|string $admin): bool
    {
        return ! blank(Cache::get($this->cacheKey($admin)));
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

    private function cacheKey(Admin|int|string $admin): string
    {
        $adminId = $admin instanceof Admin
            ? (string) $admin->getKey()
            : trim((string) $admin);

        return self::CACHE_KEY_PREFIX.$adminId;
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
