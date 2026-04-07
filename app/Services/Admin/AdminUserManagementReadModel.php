<?php

namespace App\Services\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RedisStore;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class AdminUserManagementReadModel
{
    private const META_CACHE_KEY = 'admin:user-management:read-model:meta:v1';
    private const SYNC_CACHE_KEY = 'admin:user-management:read-model:sync:v1';
    private const ROWS_CACHE_KEY = 'admin:user-management:read-model:rows:v1';
    private const ORDER_CACHE_KEY = 'admin:user-management:read-model:order:v1';
    private const USER_SYNC_LOCK_PREFIX = 'admin:user-management:read-model:sync-user:';
    private const USER_SYNC_LOCK_SECONDS = 30;

    public function __construct(
        private readonly AdminFirestoreRepository $repository,
        private readonly AdminAnalyticsService $analytics,
        private readonly AdminUserManagementSyncStatusFactory $syncStatusFactory,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('admin.user_management.read_model.enabled', false);
    }

    public function page(array $filters = []): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $meta = $this->cachedMeta();

        if (! is_array($meta)) {
            return null;
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? config('admin.per_page', 10)));
        $syncStatus = $this->syncStatus();

        if ($this->canUseIndexedPagination($filters)) {
            $rows = $this->pageRows($page, $perPage);
            $total = (int) data_get($meta, 'overview.total_users', count($rows));

            return [
                'users' => new LengthAwarePaginator(
                    $rows,
                    $total,
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                        'pageName' => 'page',
                    ],
                ),
                'overview' => is_array($meta['overview'] ?? null)
                    ? $meta['overview']
                    : $this->analytics->buildUserManagementOverview($rows),
                'filter_options' => is_array($meta['filter_options'] ?? null)
                    ? $meta['filter_options']
                    : $this->analytics->buildUserFilterOptions([]),
                'sync_status' => $syncStatus,
            ];
        }

        $allRows = $this->allRows();
        $filteredRows = $this->analytics->filterUserRows($allRows, $filters);
        $offset = ($page - 1) * $perPage;

        return [
            'users' => new LengthAwarePaginator(
                array_values(array_slice($filteredRows, $offset, $perPage)),
                count($filteredRows),
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                    'pageName' => 'page',
                ],
            ),
            'overview' => $this->analytics->buildUserManagementOverview($filteredRows),
            'filter_options' => is_array($meta['filter_options'] ?? null)
                ? $meta['filter_options']
                : $this->analytics->buildUserFilterOptions($allRows),
            'sync_status' => $syncStatus,
        ];
    }

    public function rowForUser(string $userId): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        return $this->usingRedisStorage()
            ? $this->rowForUserUsingRedis($userId)
            : $this->rowForUserUsingCache($userId);
    }

    public function rebuild(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $this->markRebuilding();

        try {
            $rows = $this->analytics->attachAttendanceProgress(
                $this->analytics->buildUserRows(
                    $this->repository->allUsers(),
                    $this->repository->allTickets(),
                ),
                $this->repository->allScanLogs(),
            );

            $rowsByUserId = $this->rowsByUserId($rows);
            $orderedUserIds = array_values(array_map(
                fn (array $row): string => (string) ($row['user_id'] ?? ''),
                array_values(array_filter($rows, fn (array $row): bool => trim((string) ($row['user_id'] ?? '')) !== '')),
            ));
            $meta = $this->buildMeta(array_values($rowsByUserId));
            $sync = $this->rawSyncPayload('fresh');

            $this->storeModel($rowsByUserId, $orderedUserIds, $meta, $sync);

            return [
                'directory' => array_values($rowsByUserId),
                'meta' => $meta,
                'sync_status' => $this->syncStatus(),
            ];
        } catch (\Throwable $exception) {
            $this->markFailed();

            throw $exception;
        }
    }

    public function syncUser(string $userId): void
    {
        if (! $this->enabled() || trim($userId) === '') {
            return;
        }

        $lockKey = self::USER_SYNC_LOCK_PREFIX.$userId;

        if (! Cache::add($lockKey, now()->toIso8601String(), now()->addSeconds(self::USER_SYNC_LOCK_SECONDS))) {
            return;
        }

        try {
            $row = $this->buildProjectedRowForUser($userId);

            if ($row === null) {
                $this->removeUser($userId);

                return;
            }

            $this->upsertRow($row);
            $this->refreshMetaFromStoredRows();
        } finally {
            Cache::forget($lockKey);
        }
    }

    public function removeUser(string $userId): void
    {
        if (! $this->enabled() || trim($userId) === '') {
            return;
        }

        if ($this->usingRedisStorage()) {
            $this->removeRowUsingRedis($userId);
        } else {
            $this->removeRowUsingCache($userId);
        }

        $this->refreshMetaFromStoredRows();
    }

    public function flush(): void
    {
        Cache::forget(self::META_CACHE_KEY);
        Cache::forget(self::SYNC_CACHE_KEY);
        Cache::forget(self::ROWS_CACHE_KEY);
        Cache::forget(self::ORDER_CACHE_KEY);

        if ($this->usingRedisStorage()) {
            $redis = $this->redisConnection();
            $redis->del(
                self::META_CACHE_KEY,
                self::SYNC_CACHE_KEY,
                self::ROWS_CACHE_KEY,
                self::ORDER_CACHE_KEY,
            );
        }
    }

    public function markRebuilding(): void
    {
        $currentRawSync = $this->cachedRawSync() ?? [];

        $this->storeSync(array_filter([
            'source' => 'read_model',
            'state' => 'rebuilding',
            'last_synced_at_utc' => $currentRawSync['last_synced_at_utc'] ?? null,
        ], static fn (mixed $value): bool => $value !== null));
    }

    public function markFailed(): void
    {
        $currentRawSync = $this->cachedRawSync() ?? [];

        $this->storeSync(array_filter([
            'source' => 'read_model',
            'state' => 'fallback',
            'last_synced_at_utc' => $currentRawSync['last_synced_at_utc'] ?? null,
        ], static fn (mixed $value): bool => $value !== null));
    }

    public function syncStatus(): array
    {
        $raw = $this->cachedRawSync();

        return $this->syncStatusFactory->make(
            is_array($raw) ? ($raw['last_synced_at_utc'] ?? null) : null,
            is_array($raw) ? (string) ($raw['source'] ?? 'read_model') : 'read_model',
            is_array($raw) ? ($raw['state'] ?? null) : null,
        );
    }

    private function refreshMetaFromStoredRows(): void
    {
        $rows = $this->allRows();
        $meta = $this->buildMeta($rows);

        $this->storeMeta($meta);
        $this->storeSync($this->rawSyncPayload('fresh'));
    }

    private function buildProjectedRowForUser(string $userId): ?array
    {
        $user = $this->repository->findUser($userId);

        if (! is_array($user)) {
            return null;
        }

        $ticket = null;
        $ticketId = trim((string) ($user['ticket_id'] ?? ''));

        if ($ticketId !== '') {
            $ticket = $this->repository->findTicket($ticketId);
        }

        $rows = $this->analytics->attachAttendanceProgress(
            $this->analytics->buildUserRows([$user], is_array($ticket) ? [$ticket] : []),
            $this->repository->findScanLogsByUserIds([$userId]),
        );

        return $rows[0] ?? null;
    }

    private function canUseIndexedPagination(array $filters): bool
    {
        return trim((string) ($filters['q'] ?? '')) === ''
            && trim((string) ($filters['country'] ?? '')) === ''
            && trim((string) ($filters['identity_type'] ?? '')) === ''
            && trim((string) ($filters['verification_status'] ?? '')) === ''
            && trim((string) ($filters['attendance_status'] ?? '')) === ''
            && trim((string) ($filters['email_typo'] ?? '')) === '';
    }

    private function buildMeta(array $rows): array
    {
        return [
            'overview' => $this->analytics->buildUserManagementOverview($rows),
            'filter_options' => $this->analytics->buildUserFilterOptions($rows),
        ];
    }

    private function rowsByUserId(array $rows): array
    {
        $rowsByUserId = [];

        foreach ($rows as $row) {
            $userId = trim((string) ($row['user_id'] ?? ''));

            if ($userId === '') {
                continue;
            }

            $rowsByUserId[$userId] = $row;
        }

        return $rowsByUserId;
    }

    private function storeModel(array $rowsByUserId, array $orderedUserIds, array $meta, array $sync): void
    {
        if ($this->usingRedisStorage()) {
            $this->storeModelUsingRedis($rowsByUserId, $orderedUserIds, $meta, $sync);

            return;
        }

        Cache::forever(self::ROWS_CACHE_KEY, $rowsByUserId);
        Cache::forever(self::ORDER_CACHE_KEY, $orderedUserIds);
        Cache::forever(self::META_CACHE_KEY, $meta);
        Cache::forever(self::SYNC_CACHE_KEY, $sync);
    }

    private function storeModelUsingRedis(array $rowsByUserId, array $orderedUserIds, array $meta, array $sync): void
    {
        $redis = $this->redisConnection();
        $tempSuffix = (string) Str::ulid();
        $tempRowsKey = self::ROWS_CACHE_KEY.':tmp:'.$tempSuffix;
        $tempOrderKey = self::ORDER_CACHE_KEY.':tmp:'.$tempSuffix;
        $tempMetaKey = self::META_CACHE_KEY.':tmp:'.$tempSuffix;
        $tempSyncKey = self::SYNC_CACHE_KEY.':tmp:'.$tempSuffix;

        $redis->pipeline(function ($pipe) use ($meta, $orderedUserIds, $rowsByUserId, $sync, $tempMetaKey, $tempOrderKey, $tempRowsKey, $tempSyncKey): void {
            $pipe->del($tempRowsKey, $tempOrderKey, $tempMetaKey, $tempSyncKey);

            foreach ($rowsByUserId as $userId => $row) {
                $pipe->hset($tempRowsKey, $userId, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
            }

            foreach ($orderedUserIds as $userId) {
                if (! isset($rowsByUserId[$userId])) {
                    continue;
                }

                $pipe->zadd($tempOrderKey, $this->scoreForRow($rowsByUserId[$userId]), $userId);
            }

            $pipe->set($tempMetaKey, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
            $pipe->set($tempSyncKey, json_encode($sync, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        });

        $redis->pipeline(function ($pipe) use ($orderedUserIds, $rowsByUserId, $tempMetaKey, $tempOrderKey, $tempRowsKey, $tempSyncKey): void {
            $pipe->del(self::ROWS_CACHE_KEY, self::ORDER_CACHE_KEY, self::META_CACHE_KEY, self::SYNC_CACHE_KEY);

            if ($rowsByUserId !== []) {
                $pipe->rename($tempRowsKey, self::ROWS_CACHE_KEY);
            }

            if ($orderedUserIds !== []) {
                $pipe->rename($tempOrderKey, self::ORDER_CACHE_KEY);
            }

            $pipe->rename($tempMetaKey, self::META_CACHE_KEY);
            $pipe->rename($tempSyncKey, self::SYNC_CACHE_KEY);
        });
    }

    private function pageRows(int $page, int $perPage): array
    {
        return $this->usingRedisStorage()
            ? $this->pageRowsUsingRedis($page, $perPage)
            : $this->pageRowsUsingCache($page, $perPage);
    }

    private function pageRowsUsingRedis(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $userIds = $this->redisConnection()->zrevrange(
            self::ORDER_CACHE_KEY,
            $offset,
            max($offset, $offset + $perPage - 1),
        );

        if (! is_array($userIds) || $userIds === []) {
            return [];
        }

        $encodedRows = $this->redisConnection()->hmget(self::ROWS_CACHE_KEY, $userIds);
        $rows = [];

        foreach ($encodedRows as $encodedRow) {
            $decodedRow = is_string($encodedRow) ? json_decode($encodedRow, true) : null;

            if (is_array($decodedRow)) {
                $rows[] = $decodedRow;
            }
        }

        return $rows;
    }

    private function pageRowsUsingCache(int $page, int $perPage): array
    {
        $orderedUserIds = Cache::get(self::ORDER_CACHE_KEY, []);
        $rowsByUserId = Cache::get(self::ROWS_CACHE_KEY, []);
        $pageUserIds = array_slice($orderedUserIds, ($page - 1) * $perPage, $perPage);

        return array_values(array_filter(array_map(
            fn (string $userId): ?array => is_array($rowsByUserId[$userId] ?? null) ? $rowsByUserId[$userId] : null,
            $pageUserIds,
        )));
    }

    private function allRows(): array
    {
        return $this->usingRedisStorage()
            ? $this->allRowsUsingRedis()
            : $this->allRowsUsingCache();
    }

    private function allRowsUsingRedis(): array
    {
        $encodedRows = $this->redisConnection()->hgetall(self::ROWS_CACHE_KEY);

        if (! is_array($encodedRows) || $encodedRows === []) {
            return [];
        }

        $rows = [];

        foreach ($encodedRows as $encodedRow) {
            $decodedRow = is_string($encodedRow) ? json_decode($encodedRow, true) : null;

            if (is_array($decodedRow)) {
                $rows[] = $decodedRow;
            }
        }

        usort($rows, fn (array $left, array $right): int => strcmp(
            (string) ($right['created_at'] ?? ''),
            (string) ($left['created_at'] ?? ''),
        ));

        return $rows;
    }

    private function allRowsUsingCache(): array
    {
        $rowsByUserId = Cache::get(self::ROWS_CACHE_KEY, []);
        $rows = array_values(array_filter($rowsByUserId, fn (mixed $row): bool => is_array($row)));

        usort($rows, fn (array $left, array $right): int => strcmp(
            (string) ($right['created_at'] ?? ''),
            (string) ($left['created_at'] ?? ''),
        ));

        return $rows;
    }

    private function upsertRow(array $row): void
    {
        $userId = trim((string) ($row['user_id'] ?? ''));

        if ($userId === '') {
            return;
        }

        if ($this->usingRedisStorage()) {
            $this->redisConnection()->hset(
                self::ROWS_CACHE_KEY,
                $userId,
                json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            );
            $this->redisConnection()->zadd(self::ORDER_CACHE_KEY, $this->scoreForRow($row), $userId);

            return;
        }

        $rowsByUserId = Cache::get(self::ROWS_CACHE_KEY, []);
        $rowsByUserId[$userId] = $row;
        Cache::forever(self::ROWS_CACHE_KEY, $rowsByUserId);

        $orderedUserIds = array_values(array_unique(array_merge(
            array_map(fn (array $existingRow): string => (string) ($existingRow['user_id'] ?? ''), $this->allRowsUsingCache()),
            [$userId],
        )));
        usort($orderedUserIds, function (string $leftUserId, string $rightUserId) use ($rowsByUserId): int {
            return strcmp(
                (string) ($rowsByUserId[$rightUserId]['created_at'] ?? ''),
                (string) ($rowsByUserId[$leftUserId]['created_at'] ?? ''),
            );
        });

        Cache::forever(self::ORDER_CACHE_KEY, $orderedUserIds);
    }

    private function removeRowUsingRedis(string $userId): void
    {
        $this->redisConnection()->hdel(self::ROWS_CACHE_KEY, $userId);
        $this->redisConnection()->zrem(self::ORDER_CACHE_KEY, $userId);
    }

    private function removeRowUsingCache(string $userId): void
    {
        $rowsByUserId = Cache::get(self::ROWS_CACHE_KEY, []);
        unset($rowsByUserId[$userId]);
        Cache::forever(self::ROWS_CACHE_KEY, $rowsByUserId);

        $orderedUserIds = array_values(array_filter(
            Cache::get(self::ORDER_CACHE_KEY, []),
            fn (string $existingUserId): bool => $existingUserId !== $userId,
        ));
        Cache::forever(self::ORDER_CACHE_KEY, $orderedUserIds);
    }

    private function rowForUserUsingRedis(string $userId): ?array
    {
        $encodedRow = $this->redisConnection()->hget(self::ROWS_CACHE_KEY, $userId);
        $decodedRow = is_string($encodedRow) ? json_decode($encodedRow, true) : null;

        return is_array($decodedRow) ? $decodedRow : null;
    }

    private function rowForUserUsingCache(string $userId): ?array
    {
        $rowsByUserId = Cache::get(self::ROWS_CACHE_KEY, []);
        $row = $rowsByUserId[$userId] ?? null;

        return is_array($row) ? $row : null;
    }

    private function cachedMeta(): ?array
    {
        if ($this->usingRedisStorage()) {
            $encodedMeta = $this->redisConnection()->get(self::META_CACHE_KEY);
            $decodedMeta = is_string($encodedMeta) ? json_decode($encodedMeta, true) : null;

            return is_array($decodedMeta) ? $decodedMeta : null;
        }

        $meta = Cache::get(self::META_CACHE_KEY);

        return is_array($meta) ? $meta : null;
    }

    private function cachedRawSync(): ?array
    {
        if ($this->usingRedisStorage()) {
            $encodedSync = $this->redisConnection()->get(self::SYNC_CACHE_KEY);
            $decodedSync = is_string($encodedSync) ? json_decode($encodedSync, true) : null;

            return is_array($decodedSync) ? $decodedSync : null;
        }

        $sync = Cache::get(self::SYNC_CACHE_KEY);

        return is_array($sync) ? $sync : null;
    }

    private function storeMeta(array $meta): void
    {
        if ($this->usingRedisStorage()) {
            $this->redisConnection()->set(
                self::META_CACHE_KEY,
                json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            );

            return;
        }

        Cache::forever(self::META_CACHE_KEY, $meta);
    }

    private function storeSync(array $sync): void
    {
        if ($this->usingRedisStorage()) {
            $this->redisConnection()->set(
                self::SYNC_CACHE_KEY,
                json_encode($sync, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            );

            return;
        }

        Cache::forever(self::SYNC_CACHE_KEY, $sync);
    }

    private function rawSyncPayload(string $state): array
    {
        return [
            'source' => 'read_model',
            'state' => $state,
            'last_synced_at_utc' => now('UTC')->toIso8601String(),
        ];
    }

    private function scoreForRow(array $row): float
    {
        $createdAt = trim((string) ($row['created_at'] ?? ''));

        if ($createdAt === '') {
            return 0.0;
        }

        try {
            return (float) CarbonImmutable::parse($createdAt)->utc()->format('U.u');
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function usingRedisStorage(): bool
    {
        return Cache::getStore() instanceof RedisStore;
    }

    private function redisConnection()
    {
        return Redis::connection((string) config('cache.stores.redis.connection', 'cache'));
    }
}
