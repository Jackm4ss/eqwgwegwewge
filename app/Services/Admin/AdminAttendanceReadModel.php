<?php

namespace App\Services\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RedisStore;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class AdminAttendanceReadModel
{
    private const META_CACHE_KEY = 'admin:attendance:read-model:meta:v1';
    private const SYNC_CACHE_KEY = 'admin:attendance:read-model:sync:v1';
    private const ROWS_CACHE_KEY = 'admin:attendance:read-model:rows:v1';
    private const ORDER_CACHE_KEY = 'admin:attendance:read-model:order:v1';
    private const USER_SYNC_LOCK_PREFIX = 'admin:attendance:read-model:sync-user:';
    private const SCAN_SYNC_LOCK_PREFIX = 'admin:attendance:read-model:sync-scan:';
    private const SYNC_LOCK_SECONDS = 30;
    private const FILTER_SCAN_CHUNK_SIZE = 250;

    public function __construct(
        private readonly AdminFirestoreRepository $repository,
        private readonly AdminAnalyticsService $analytics,
        private readonly AdminAttendanceSyncStatusFactory $syncStatusFactory,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('admin.attendance.read_model.enabled', false);
    }

    public function supportsFilters(array $filters = []): bool
    {
        return $this->enabled();
    }

    public function page(array $filters = []): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $meta = $this->cachedMeta() ?? $this->buildMeta([]);
        $syncStatus = $this->syncStatus();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? config('admin.per_page', 10)));

        if ($this->storedRowCount() === 0) {
            return [
                'rows' => new LengthAwarePaginator(
                    [],
                    0,
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                        'pageName' => 'page',
                    ],
                ),
                'overview' => $meta['overview'] ?? $this->buildOverview([]),
                'filter_options' => $meta['filter_options'] ?? $this->buildFilterOptions([]),
                'sync_status' => $syncStatus,
            ];
        }

        if ($this->usingRedisStorage()) {
            return $this->filteredPageUsingRedis($filters, $page, $perPage, $meta, $syncStatus);
        }

        return $this->buildPageFromRows(
            $this->allRowsUsingCache(),
            $filters,
            $page,
            $perPage,
            $meta,
            $syncStatus,
        );
    }

    public function rebuild(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $this->markRebuilding();

        try {
            $allScanLogs = $this->repository->allScanLogs();
            $rows = $this->buildProjectedRows(
                $allScanLogs,
                $this->repository->allAttendanceDaily(),
            );
            $rowsById = [];
            $orderedScanIds = [];

            foreach ($rows as $row) {
                $scanId = trim((string) ($row['scan_id'] ?? ''));

                if ($scanId === '') {
                    continue;
                }

                $rowsById[$scanId] = $row;
                $orderedScanIds[] = $scanId;
            }

            $meta = $this->buildMeta(array_values($rowsById));
            $sync = $this->rawSyncPayload('fresh');

            $this->storeModel($rowsById, $orderedScanIds, $meta, $sync);

            return [
                'rows' => array_values($rowsById),
                'meta' => $meta,
                'sync_status' => $this->syncStatus(),
            ];
        } catch (\Throwable $exception) {
            $this->markFailed();

            throw $exception;
        }
    }

    public function syncScan(string $scanId): void
    {
        if (! $this->enabled() || trim($scanId) === '') {
            return;
        }

        $lockKey = self::SCAN_SYNC_LOCK_PREFIX.$scanId;

        if (! Cache::add($lockKey, now()->toIso8601String(), now()->addSeconds(self::SYNC_LOCK_SECONDS))) {
            return;
        }

        try {
            $scanLog = $this->repository->findScanLog($scanId);

            if (! is_array($scanLog)) {
                $this->removeScan($scanId);

                return;
            }

            $row = $this->buildProjectedRowForScanLog($scanLog);

            if ($row === null) {
                $this->removeScan($scanId);

                return;
            }

            $this->upsertRow($row);
            $this->refreshMetaAfterMutation();
        } finally {
            Cache::forget($lockKey);
        }
    }

    public function syncUser(string $userId): void
    {
        if (! $this->enabled() || trim($userId) === '') {
            return;
        }

        $lockKey = self::USER_SYNC_LOCK_PREFIX.$userId;

        if (! Cache::add($lockKey, now()->toIso8601String(), now()->addSeconds(self::SYNC_LOCK_SECONDS))) {
            return;
        }

        try {
            $scanLogs = $this->repository->findScanLogsByUserIds([$userId]);
            $attendanceLogs = $this->repository->findAttendanceDailyByUserIds([$userId]);

            if ($scanLogs === []) {
                $this->storeSync($this->rawSyncPayload('fresh'));

                return;
            }

            foreach ($this->buildProjectedRows($scanLogs, $attendanceLogs) as $row) {
                $this->upsertRow($row);
            }

            $this->refreshMetaAfterMutation();
        } finally {
            Cache::forget($lockKey);
        }
    }

    public function removeScan(string $scanId): void
    {
        if (! $this->enabled() || trim($scanId) === '') {
            return;
        }

        if ($this->usingRedisStorage()) {
            $this->redisConnection()->hdel(self::ROWS_CACHE_KEY, $scanId);
            $this->redisConnection()->zrem(self::ORDER_CACHE_KEY, $scanId);
        } else {
            $rowsById = Cache::get(self::ROWS_CACHE_KEY, []);
            unset($rowsById[$scanId]);
            Cache::forever(self::ROWS_CACHE_KEY, $rowsById);

            $orderedScanIds = array_values(array_filter(
                Cache::get(self::ORDER_CACHE_KEY, []),
                static fn (string $existingScanId): bool => $existingScanId !== $scanId,
            ));
            Cache::forever(self::ORDER_CACHE_KEY, $orderedScanIds);
        }

        $this->refreshMetaAfterMutation();
    }

    public function flush(): void
    {
        Cache::forget(self::META_CACHE_KEY);
        Cache::forget(self::SYNC_CACHE_KEY);
        Cache::forget(self::ROWS_CACHE_KEY);
        Cache::forget(self::ORDER_CACHE_KEY);

        if ($this->usingRedisStorage()) {
            $this->redisConnection()->del(
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

    public function refreshMetaFromProjection(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        if ($this->storedRowCount() === 0) {
            return [];
        }

        $this->refreshMetaFromStoredRows();

        return [
            'meta' => $this->cachedMeta() ?? [],
            'sync_status' => $this->syncStatus(),
        ];
    }

    private function refreshMetaFromStoredRows(): void
    {
        $rows = $this->allRows();
        $meta = $this->buildMeta($rows);

        $this->storeMeta($meta);
        $this->storeSync($this->rawSyncPayload('fresh'));
    }

    private function refreshMetaAfterMutation(): void
    {
        if (! $this->canRefreshMetaInline()) {
            $this->storeSync($this->rawSyncPayload('fresh'));

            return;
        }

        $this->refreshMetaFromStoredRows();
    }

    private function filteredPageUsingRedis(
        array $filters,
        int $page,
        int $perPage,
        array $meta,
        array $syncStatus,
    ): array {
        $participantAggregatesByKey = [];
        $overview = $this->emptyOverviewAccumulator();
        $rangeStart = 0;
        $chunkSize = max(self::FILTER_SCAN_CHUNK_SIZE, $perPage);
        $redis = $this->redisConnection();

        while (true) {
            $scanIds = $redis->zrevrange(
                self::ORDER_CACHE_KEY,
                $rangeStart,
                $rangeStart + $chunkSize - 1,
            );

            if (! is_array($scanIds) || $scanIds === []) {
                break;
            }

            $encodedRows = $redis->hmget(self::ROWS_CACHE_KEY, $scanIds);

            foreach ($encodedRows as $encodedRow) {
                $row = is_string($encodedRow) ? json_decode($encodedRow, true) : null;

                if (! is_array($row) || ! $this->matchesRowFilters($row, $filters)) {
                    continue;
                }

                $this->accumulateOverviewRow($overview, $row);

                $participantKey = trim((string) ($row['participant_key'] ?? ''));

                if ($participantKey === '' || ! $this->rowHasParticipantIdentity($row)) {
                    continue;
                }

                if (! isset($participantAggregatesByKey[$participantKey])) {
                    $participantAggregatesByKey[$participantKey] = $this->participantAggregateFromRow($row);
                    continue;
                }

                $this->accumulateParticipantAggregate($participantAggregatesByKey[$participantKey], $row);
            }

            $rangeStart += $chunkSize;
        }

        return $this->buildPagePayload(
            $this->participantRowsFromAggregates($participantAggregatesByKey),
            $this->finalizeOverviewAccumulator($overview),
            $meta,
            $syncStatus,
            $page,
            $perPage,
        );
    }

    private function buildPageFromRows(
        array $rows,
        array $filters,
        int $page,
        int $perPage,
        array $meta,
        array $syncStatus,
    ): array {
        $filteredRows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->matchesRowFilters($row, $filters),
        ));
        $overview = $this->buildOverview($filteredRows);
        $participantRows = $this->uniqueParticipantRows($filteredRows);

        return $this->buildPagePayload(
            $participantRows,
            $overview,
            $meta,
            $syncStatus,
            $page,
            $perPage,
        );
    }

    private function buildPagePayload(
        array $participantRows,
        array $overview,
        array $meta,
        array $syncStatus,
        int $page,
        int $perPage,
    ): array {
        $offset = ($page - 1) * $perPage;
        $pageRows = $this->hydratePageRowsWithAttendanceDaily(
            array_values(array_slice($participantRows, $offset, $perPage)),
        );

        return [
            'rows' => new LengthAwarePaginator(
                $pageRows,
                count($participantRows),
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                    'pageName' => 'page',
                ],
            ),
            'overview' => $overview,
            'filter_options' => is_array($meta['filter_options'] ?? null)
                ? $meta['filter_options']
                : $this->buildFilterOptions([]),
            'sync_status' => $syncStatus,
        ];
    }

    private function hydratePageRowsWithAttendanceDaily(array $participantRows): array
    {
        if ($participantRows === []) {
            return [];
        }

        $rowsByUserId = [];

        foreach ($participantRows as $row) {
            $userId = trim((string) ($row['user_id'] ?? ''));

            if (
                $userId === ''
                || $this->normalizeAttendanceStatus($row['attendance_status'] ?? null) !== 'checked_in'
            ) {
                continue;
            }

            $rowsByUserId[$userId] = [
                'user_id' => $userId,
                'ticket_id' => (string) ($row['ticket_id'] ?? ''),
                'ticket_code' => (string) ($row['ticket_code'] ?? ''),
            ];
        }

        if ($rowsByUserId === []) {
            return $participantRows;
        }

        try {
            $attendanceDailyRows = $this->repository->findAttendanceDailyByUserIds(array_keys($rowsByUserId));
        } catch (\Throwable $exception) {
            Log::warning('Unable to hydrate admin attendance participant rows from attendance_daily.', [
                'message' => $exception->getMessage(),
                'user_ids' => array_keys($rowsByUserId),
            ]);

            return $participantRows;
        }

        $progressRows = $this->analytics->attachAttendanceProgress(
            array_values($rowsByUserId),
            $attendanceDailyRows,
        );
        $progressByUserId = [];
        $latestAttendanceTimestampByUserId = [];

        foreach ($progressRows as $progressRow) {
            $userId = trim((string) ($progressRow['user_id'] ?? ''));

            if ($userId === '') {
                continue;
            }

            $progressByUserId[$userId] = [
                'attendance_days_count' => max(0, (int) ($progressRow['attendance_days_count'] ?? 0)),
                'attendance_total_days' => max(0, (int) ($progressRow['attendance_total_days'] ?? 0)),
                'attendance_progress_percent' => max(0, min(100, (int) ($progressRow['attendance_progress_percent'] ?? 0))),
            ];
        }

        foreach ($attendanceDailyRows as $attendanceDailyRow) {
            if (! is_array($attendanceDailyRow)) {
                continue;
            }

            $userId = trim((string) ($attendanceDailyRow['user_id'] ?? ''));
            $attendanceTimestamp = $this->attendanceTimestamp($attendanceDailyRow);

            if (
                $userId === ''
                || $attendanceTimestamp === null
                || strcmp($attendanceTimestamp, (string) ($latestAttendanceTimestampByUserId[$userId] ?? '')) <= 0
            ) {
                continue;
            }

            $latestAttendanceTimestampByUserId[$userId] = $attendanceTimestamp;
        }

        return array_map(function (array $row) use ($progressByUserId, $latestAttendanceTimestampByUserId): array {
            $userId = trim((string) ($row['user_id'] ?? ''));

            if (
                $userId === ''
                || $this->normalizeAttendanceStatus($row['attendance_status'] ?? null) !== 'checked_in'
            ) {
                return $row;
            }

            if (! isset($progressByUserId[$userId])) {
                $row['attendance_days_count'] = 0;
                $row['attendance_progress_percent'] = 0;
                $row['attendance_status'] = 'not_checked_in';
                $row['checked_in_at'] = null;

                return $row;
            }

            $row = array_merge($row, $progressByUserId[$userId]);
            $row['attendance_status'] = ($progressByUserId[$userId]['attendance_days_count'] ?? 0) > 0
                ? 'checked_in'
                : 'not_checked_in';

            if ($row['attendance_status'] !== 'checked_in') {
                $row['checked_in_at'] = null;

                return $row;
            }

            if (isset($latestAttendanceTimestampByUserId[$userId])) {
                $row['checked_in_at'] = $latestAttendanceTimestampByUserId[$userId];
            }

            return $row;
        }, $participantRows);
    }

    private function buildProjectedRowForScanLog(array $scanLog): ?array
    {
        $userId = trim((string) ($scanLog['user_id'] ?? ''));
        $attendanceLogs = $userId !== ''
            ? $this->repository->findAttendanceDailyByUserIds([$userId])
            : [];

        return $this->buildProjectedRows([$scanLog], $attendanceLogs)[0] ?? null;
    }

    private function buildProjectedRows(array $scanLogs, ?array $attendanceLogs = null): array
    {
        $scanLogs = array_values(array_filter($scanLogs, static fn (mixed $scanLog): bool => is_array($scanLog)));

        if ($scanLogs === []) {
            return [];
        }

        $userIds = array_values(array_unique(array_filter(array_map(
            fn (array $scanLog): string => trim((string) ($scanLog['user_id'] ?? '')),
            $scanLogs,
        ))));
        $ticketIds = array_values(array_unique(array_filter(array_map(
            fn (array $scanLog): string => trim((string) ($scanLog['ticket_id'] ?? '')),
            $scanLogs,
        ))));

        $users = $this->repository->findUsersByIds($userIds);
        $tickets = $this->repository->findTicketsByIds($ticketIds);
        $usersById = [];
        $ticketsById = [];

        foreach ($users as $user) {
            $userId = trim((string) ($user['user_id'] ?? ''));

            if ($userId !== '') {
                $usersById[$userId] = $user;
            }
        }

        foreach ($tickets as $ticket) {
            $ticketId = trim((string) ($ticket['ticket_id'] ?? ''));

            if ($ticketId !== '') {
                $ticketsById[$ticketId] = $ticket;
            }
        }

        $attendanceLogs ??= $userIds !== []
            ? $this->repository->findAttendanceDailyByUserIds($userIds)
            : [];
        $attendanceLogsByLookupKey = $this->indexAttendanceLogsByLookupKey($attendanceLogs);

        $participantRows = $this->analytics->attachAttendanceProgress(
            $this->analytics->buildUserRows(array_values($usersById), array_values($ticketsById)),
            $attendanceLogs,
        );
        $participantRowsByUserId = [];

        foreach ($participantRows as $participantRow) {
            $userId = trim((string) ($participantRow['user_id'] ?? ''));

            if ($userId !== '') {
                $participantRowsByUserId[$userId] = $participantRow;
            }
        }

        $rows = [];

        foreach ($scanLogs as $scanLog) {
            $row = $this->buildProjectedRow(
                $scanLog,
                $participantRowsByUserId,
                $ticketsById,
                $attendanceLogsByLookupKey,
            );

            if ($row !== null) {
                $rows[] = $row;
            }
        }

        usort($rows, function (array $left, array $right): int {
            $leftTimestamp = $this->scoreForRow($left);
            $rightTimestamp = $this->scoreForRow($right);

            if ($leftTimestamp === $rightTimestamp) {
                return strcmp(
                    (string) ($right['scan_id'] ?? ''),
                    (string) ($left['scan_id'] ?? ''),
                );
            }

            return $rightTimestamp <=> $leftTimestamp;
        });

        return $rows;
    }

    private function buildProjectedRow(
        array $scanLog,
        array $participantRowsByUserId,
        array $ticketsById,
        array $attendanceLogsByLookupKey,
    ): ?array {
        $scanId = trim((string) ($scanLog['scan_id'] ?? $scanLog['__id'] ?? ''));

        if ($scanId === '') {
            return null;
        }

        $userId = trim((string) ($scanLog['user_id'] ?? ''));
        $ticketId = trim((string) ($scanLog['ticket_id'] ?? ''));
        $participantRow = $userId !== '' ? ($participantRowsByUserId[$userId] ?? null) : null;
        $ticket = $ticketId !== '' ? ($ticketsById[$ticketId] ?? null) : null;
        $snapshot = $this->participantSnapshotFromLog($scanLog);
        $entryCodeDisplay = trim((string) ($scanLog['entry_code_display'] ?? ''));

        if ($entryCodeDisplay === '') {
            $entryCodeDisplay = trim((string) ($participantRow['entry_code_display'] ?? $snapshot['entry_code_display'] ?? $ticket['entry_code_display'] ?? ''));
        }

        $ticketCode = trim((string) ($scanLog['ticket_code'] ?? $participantRow['ticket_code'] ?? $snapshot['ticket_code'] ?? $ticket['ticket_code'] ?? ''));
        $fullName = trim((string) ($participantRow['full_name'] ?? $snapshot['full_name'] ?? ''));
        $email = trim((string) ($participantRow['email'] ?? $snapshot['email'] ?? ''));
        $phoneNumber = trim((string) ($participantRow['phone_number'] ?? $snapshot['phone_number'] ?? ''));
        $country = strtoupper(trim((string) ($participantRow['country'] ?? $snapshot['country'] ?? '')));
        $countryLabel = trim((string) ($participantRow['country_label'] ?? $snapshot['country_label'] ?? ''));
        $identityType = $this->normalizeIdentityType($participantRow['identity_type'] ?? $snapshot['identity_type'] ?? null);
        $identityNumber = trim((string) ($participantRow['identity_number'] ?? $snapshot['identity_number'] ?? ''));
        $attendanceStatus = $this->normalizeAttendanceStatus(
            $participantRow['attendance_status'] ?? $ticket['attendance_status'] ?? null,
        );
        $scanDate = trim((string) ($scanLog['scan_date'] ?? ''));

        if ($scanDate === '') {
            $scanDate = $this->resolveScanDate($scanLog['scanned_at'] ?? null);
        }

        if ($countryLabel === '' && $country !== '') {
            $countryLabel = $this->analytics->countryLabel($country);
        }

        $attendanceLookupKey = $this->attendanceLookupKey($ticketId, $userId, $ticketCode, $scanDate);
        $activeAttendanceRow = $attendanceLookupKey !== ''
            ? ($attendanceLogsByLookupKey[$attendanceLookupKey] ?? null)
            : null;

        $row = [
            'scan_id' => $scanId,
            'participant_key' => $this->participantKey(
                $userId,
                $ticketCode,
                $entryCodeDisplay,
                $email,
                $phoneNumber,
                $fullName,
                $scanId,
            ),
            'ticket_id' => $ticketId,
            'ticket_code' => $ticketCode,
            'user_id' => $userId,
            'scanner_id' => trim((string) ($scanLog['scanner_id'] ?? '')),
            'scanner_name' => trim((string) ($scanLog['scanner_name'] ?? '')),
            'scanner_role' => trim((string) ($scanLog['scanner_role'] ?? '')),
            'scanned_at' => trim((string) ($scanLog['scanned_at'] ?? '')),
            'scan_date' => $scanDate,
            'result' => $this->normalizeScanResult($scanLog['result'] ?? null),
            'entry_code_display' => $entryCodeDisplay,
            'full_name' => $fullName,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'country' => $country,
            'country_label' => $countryLabel,
            'identity_type' => $identityType,
            'identity_label' => $this->analytics->identityTypeLabel($identityType),
            'identity_number' => $identityNumber,
            'attendance_status' => $attendanceStatus,
            'checked_in_at' => $participantRow['checked_in_at'] ?? $ticket['checked_in_at'] ?? null,
            'attendance_active_for_scan_date' => is_array($activeAttendanceRow),
            'attendance_active_at' => is_array($activeAttendanceRow)
                ? $this->attendanceTimestamp($activeAttendanceRow)
                : null,
            'attendance_days_count' => (int) ($participantRow['attendance_days_count'] ?? 0),
            'attendance_total_days' => (int) ($participantRow['attendance_total_days'] ?? 0),
            'attendance_progress_percent' => (int) ($participantRow['attendance_progress_percent'] ?? 0),
        ];

        $row['search_blob'] = $this->searchBlob($row);

        return $row;
    }

    private function participantSnapshotFromLog(array $scanLog): array
    {
        $snapshot = $scanLog['participant_snapshot'] ?? null;

        if (! is_array($snapshot)) {
            return [];
        }

        $country = strtoupper(trim((string) ($snapshot['country'] ?? '')));
        $countryLabel = trim((string) ($snapshot['country_label'] ?? ''));

        if ($countryLabel === '' && $country !== '') {
            $countryLabel = $this->analytics->countryLabel($country);
        }

        $identityType = $this->normalizeIdentityType($snapshot['identity_type'] ?? null);

        return [
            'full_name' => trim((string) ($snapshot['full_name'] ?? $snapshot['name'] ?? '')),
            'email' => trim((string) ($snapshot['email'] ?? '')),
            'phone_number' => trim((string) ($snapshot['phone_number'] ?? '')),
            'country' => $country,
            'country_label' => $countryLabel,
            'identity_type' => $identityType,
            'identity_number' => trim((string) ($snapshot['identity_number'] ?? '')),
            'ticket_code' => trim((string) ($snapshot['ticket_code'] ?? $scanLog['ticket_code'] ?? '')),
            'entry_code_display' => trim((string) ($snapshot['entry_code_display'] ?? $scanLog['entry_code_display'] ?? '')),
        ];
    }

    private function indexAttendanceLogsByLookupKey(array $attendanceLogs): array
    {
        $attendanceLogsByLookupKey = [];

        foreach ($attendanceLogs as $attendanceLog) {
            if (! is_array($attendanceLog)) {
                continue;
            }

            $scanDate = trim((string) ($attendanceLog['scan_date'] ?? ''));

            if ($scanDate === '') {
                $scanDate = $this->resolveScanDate(
                    $attendanceLog['first_scanned_at']
                        ?? $attendanceLog['updated_at']
                        ?? null,
                );
            }

            $lookupKey = $this->attendanceLookupKey(
                trim((string) ($attendanceLog['ticket_id'] ?? '')),
                trim((string) ($attendanceLog['user_id'] ?? '')),
                trim((string) ($attendanceLog['ticket_code'] ?? '')),
                $scanDate,
            );

            if ($lookupKey === '') {
                continue;
            }

            $attendanceLogsByLookupKey[$lookupKey] = $attendanceLog;
        }

        return $attendanceLogsByLookupKey;
    }

    private function attendanceLookupKey(
        string $ticketId,
        string $userId,
        string $ticketCode,
        string $scanDate,
    ): string {
        $scanDate = trim($scanDate);

        if ($scanDate === '') {
            return '';
        }

        if ($ticketId !== '') {
            return 'ticket:'.$ticketId.':'.$scanDate;
        }

        if ($userId !== '') {
            return 'user:'.$userId.':'.$scanDate;
        }

        $ticketCode = strtoupper(trim($ticketCode));

        return $ticketCode !== ''
            ? 'code:'.$ticketCode.':'.$scanDate
            : '';
    }

    private function buildMeta(array $rows): array
    {
        return [
            'overview' => $this->buildOverview($rows),
            'filter_options' => $this->buildFilterOptions($rows),
        ];
    }

    private function buildOverview(array $rows): array
    {
        $overview = $this->emptyOverviewAccumulator();

        foreach ($rows as $row) {
            $this->accumulateOverviewRow($overview, $row);
        }

        return $this->finalizeOverviewAccumulator($overview);
    }

    private function emptyOverviewAccumulator(): array
    {
        return [
            'total_scans' => 0,
            'repeat_scans' => 0,
            'needs_review' => 0,
            'attended_participant_keys' => [],
            'checked_in_keys' => [],
            'gate_counts' => [],
        ];
    }

    private function accumulateOverviewRow(array &$overview, array $row): void
    {
        $overview['total_scans']++;

        $result = $this->normalizeScanResult($row['result'] ?? null);
        $participantKey = trim((string) ($row['participant_key'] ?? ''));
        $scannerName = trim((string) ($row['scanner_name'] ?? ''));
        $hasActiveAttendanceForScanDate = $this->rowHasActiveAttendanceForScanDate($row);

        if ($hasActiveAttendanceForScanDate && $participantKey !== '' && $this->rowHasParticipantIdentity($row)) {
            $overview['attended_participant_keys'][$participantKey] = true;
        }

        if ($result === 'duplicate') {
            $overview['repeat_scans']++;
        } elseif ($result !== 'success') {
            $overview['needs_review']++;
        }

        if (
            $hasActiveAttendanceForScanDate
            && in_array($result, ['success', 'duplicate'], true)
            && $participantKey !== ''
            && $this->rowHasParticipantIdentity($row)
        ) {
            $overview['checked_in_keys'][$participantKey] = true;
        }

        if ($scannerName !== '') {
            $overview['gate_counts'][$scannerName] = (int) ($overview['gate_counts'][$scannerName] ?? 0) + 1;
        }
    }

    private function finalizeOverviewAccumulator(array $overview): array
    {
        $gateCounts = [];

        foreach ($overview['gate_counts'] ?? [] as $gateName => $count) {
            $gateCounts[] = [
                'value' => $gateName,
                'label' => $gateName,
                'count' => (int) $count,
            ];
        }

        usort($gateCounts, function (array $left, array $right): int {
            $countComparison = ((int) ($right['count'] ?? 0)) <=> ((int) ($left['count'] ?? 0));

            if ($countComparison !== 0) {
                return $countComparison;
            }

            return strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        });

        return [
            'total_attendance' => count($overview['attended_participant_keys'] ?? []),
            'checked_in' => count($overview['checked_in_keys'] ?? []),
            'repeat_scans' => (int) ($overview['repeat_scans'] ?? 0),
            'needs_review' => (int) ($overview['needs_review'] ?? 0),
            'total_scans' => (int) ($overview['total_scans'] ?? 0),
            'gate_counts' => $gateCounts,
        ];
    }

    private function buildFilterOptions(array $rows): array
    {
        $participantRows = $this->uniqueParticipantRows($rows);
        $countryCounts = [];
        $identityTypeCounts = [];
        $attendanceStatusCounts = [];
        $scanResultCounts = [];
        $scanPostCounts = [];

        foreach ($participantRows as $row) {
            $country = strtoupper(trim((string) ($row['country'] ?? '')));

            if ($country !== '') {
                $countryCounts[$country] = (int) ($countryCounts[$country] ?? 0) + 1;
            }

            $identityType = $this->normalizeIdentityType($row['identity_type'] ?? null);
            $identityTypeCounts[$identityType] = (int) ($identityTypeCounts[$identityType] ?? 0) + 1;

            $attendanceStatus = $this->normalizeAttendanceStatus($row['attendance_status'] ?? null);
            $attendanceStatusCounts[$attendanceStatus] = (int) ($attendanceStatusCounts[$attendanceStatus] ?? 0) + 1;
        }

        foreach ($rows as $row) {
            $scanResult = $this->scanResultFilterValue($row['result'] ?? null);

            if ($scanResult !== '') {
                $scanResultCounts[$scanResult] = (int) ($scanResultCounts[$scanResult] ?? 0) + 1;
            }

            $scannerName = trim((string) ($row['scanner_name'] ?? ''));

            if ($scannerName !== '') {
                $scanPostCounts[$scannerName] = (int) ($scanPostCounts[$scannerName] ?? 0) + 1;
            }
        }

        $countries = array_map(function (string $country, int $count): array {
            return [
                'value' => $country,
                'label' => $this->analytics->countryLabel($country),
                'count' => $count,
            ];
        }, array_keys($countryCounts), array_values($countryCounts));
        usort($countries, fn (array $left, array $right): int => strcasecmp(
            (string) ($left['label'] ?? ''),
            (string) ($right['label'] ?? ''),
        ));

        $identityTypes = array_map(function (string $identityType, int $count): array {
            return [
                'value' => $identityType,
                'label' => $this->analytics->identityTypeLabel($identityType),
                'count' => $count,
            ];
        }, array_keys($identityTypeCounts), array_values($identityTypeCounts));
        usort($identityTypes, fn (array $left, array $right): int => strcasecmp(
            (string) ($left['label'] ?? ''),
            (string) ($right['label'] ?? ''),
        ));

        $attendanceStatuses = array_map(function (string $attendanceStatus, int $count): array {
            return [
                'value' => $attendanceStatus,
                'label' => $this->attendanceStatusLabel($attendanceStatus),
                'count' => $count,
            ];
        }, array_keys($attendanceStatusCounts), array_values($attendanceStatusCounts));
        usort($attendanceStatuses, fn (array $left, array $right): int => strcasecmp(
            (string) ($left['label'] ?? ''),
            (string) ($right['label'] ?? ''),
        ));

        $scanResults = array_map(function (string $scanResult, int $count): array {
            return [
                'value' => $scanResult,
                'label' => $this->scanResultFilterLabel($scanResult),
                'count' => $count,
            ];
        }, array_keys($scanResultCounts), array_values($scanResultCounts));
        usort($scanResults, fn (array $left, array $right): int => strcasecmp(
            (string) ($left['label'] ?? ''),
            (string) ($right['label'] ?? ''),
        ));

        $scanPosts = array_map(function (string $scannerName, int $count): array {
            return [
                'value' => $scannerName,
                'label' => $scannerName,
                'count' => $count,
            ];
        }, array_keys($scanPostCounts), array_values($scanPostCounts));
        usort($scanPosts, fn (array $left, array $right): int => strcasecmp(
            (string) ($left['label'] ?? ''),
            (string) ($right['label'] ?? ''),
        ));

        return [
            'countries' => array_values($countries),
            'identity_types' => array_values($identityTypes),
            'attendance_statuses' => array_values($attendanceStatuses),
            'scan_results' => array_values($scanResults),
            'scan_posts' => array_values($scanPosts),
        ];
    }

    private function uniqueParticipantRows(array $rows): array
    {
        $participantAggregatesByKey = [];

        foreach ($rows as $row) {
            $participantKey = trim((string) ($row['participant_key'] ?? ''));

            if ($participantKey === '' || ! $this->rowHasParticipantIdentity($row)) {
                continue;
            }

            if (! isset($participantAggregatesByKey[$participantKey])) {
                $participantAggregatesByKey[$participantKey] = $this->participantAggregateFromRow($row);
                continue;
            }

            $this->accumulateParticipantAggregate($participantAggregatesByKey[$participantKey], $row);
        }

        return $this->participantRowsFromAggregates($participantAggregatesByKey);
    }

    private function participantRowsFromAggregates(array $participantAggregatesByKey): array
    {
        $participantRows = [];

        foreach ($participantAggregatesByKey as $aggregate) {
            $participantRow = $this->participantTableRowFromAggregate($aggregate);

            if ($participantRow !== null) {
                $participantRows[] = $participantRow;
            }
        }

        return $participantRows;
    }

    private function participantAggregateFromRow(array $row): array
    {
        return [
            'base_row' => $row,
            'has_active_attendance' => $this->rowHasActiveAttendanceForScanDate($row),
            'latest_active_attendance_at' => $this->rowActiveAttendanceAt($row),
        ];
    }

    private function accumulateParticipantAggregate(array &$aggregate, array $row): void
    {
        if (! $this->rowHasActiveAttendanceForScanDate($row)) {
            return;
        }

        $aggregate['has_active_attendance'] = true;
        $candidateTimestamp = $this->rowActiveAttendanceAt($row);

        if (
            $candidateTimestamp !== null
            && strcmp($candidateTimestamp, (string) ($aggregate['latest_active_attendance_at'] ?? '')) > 0
        ) {
            $aggregate['latest_active_attendance_at'] = $candidateTimestamp;
        }
    }

    private function participantTableRowFromAggregate(array $aggregate): ?array
    {
        $participantRow = $this->participantTableRowFromScanRow($aggregate['base_row'] ?? []);

        if ($participantRow === null) {
            return null;
        }

        if (! ($aggregate['has_active_attendance'] ?? false)) {
            $participantRow['attendance_status'] = 'not_checked_in';
            $participantRow['checked_in_at'] = null;

            return $participantRow;
        }

        $participantRow['attendance_status'] = 'checked_in';

        if (($aggregate['latest_active_attendance_at'] ?? null) !== null) {
            $participantRow['checked_in_at'] = $aggregate['latest_active_attendance_at'];
        }

        return $participantRow;
    }

    private function participantTableRowFromScanRow(array $row): ?array
    {
        if (! $this->rowHasParticipantIdentity($row)) {
            return null;
        }

        $fullName = trim((string) ($row['full_name'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $labelSeed = $fullName !== '' ? $fullName : ($email !== '' ? $email : (string) ($row['entry_code_display'] ?? ''));

        return [
            'participant_key' => (string) ($row['participant_key'] ?? ''),
            'user_id' => (string) ($row['user_id'] ?? ''),
            'ticket_id' => (string) ($row['ticket_id'] ?? ''),
            'ticket_code' => (string) ($row['ticket_code'] ?? ''),
            'full_name' => $fullName,
            'email' => $email,
            'initials' => $this->initials($labelSeed),
            'country' => (string) ($row['country'] ?? ''),
            'country_label' => (string) ($row['country_label'] ?? ''),
            'entry_code_display' => (string) ($row['entry_code_display'] ?? ''),
            'attendance_days_count' => (int) ($row['attendance_days_count'] ?? 0),
            'attendance_total_days' => max(0, (int) ($row['attendance_total_days'] ?? 0)),
            'attendance_progress_percent' => max(0, min(100, (int) ($row['attendance_progress_percent'] ?? 0))),
            'attendance_status' => (string) ($row['attendance_status'] ?? 'not_checked_in'),
            'checked_in_at' => $row['checked_in_at'] ?? null,
            'scanner_name' => (string) ($row['scanner_name'] ?? ''),
            'scanner_role' => (string) ($row['scanner_role'] ?? ''),
            'scanner_id' => (string) ($row['scanner_id'] ?? ''),
            'latest_scan_at' => (string) ($row['scanned_at'] ?? ''),
            'latest_scan_result' => (string) ($row['result'] ?? ''),
            'identity_type' => (string) ($row['identity_type'] ?? ''),
            'identity_number' => (string) ($row['identity_number'] ?? ''),
            'phone_number' => (string) ($row['phone_number'] ?? ''),
        ];
    }

    private function matchesRowFilters(array $row, array $filters): bool
    {
        $query = $this->normalizeSearchText((string) ($filters['q'] ?? ''));

        if ($query !== '' && ! str_contains((string) ($row['search_blob'] ?? ''), $query)) {
            return false;
        }

        $country = strtoupper(trim((string) ($filters['country'] ?? '')));
        if ($country !== '' && strtoupper((string) ($row['country'] ?? '')) !== $country) {
            return false;
        }

        $identityType = trim((string) ($filters['identity_type'] ?? ''));
        if ($identityType !== '' && $this->normalizeIdentityType($row['identity_type'] ?? null) !== $this->normalizeIdentityType($identityType)) {
            return false;
        }

        $attendanceStatus = trim((string) ($filters['attendance_status'] ?? ''));
        if ($attendanceStatus !== '' && $this->normalizeAttendanceStatus($row['attendance_status'] ?? null) !== $this->normalizeAttendanceStatus($attendanceStatus)) {
            return false;
        }

        $scanResult = $this->scanResultFilterValue($filters['scan_result'] ?? null);
        if ($scanResult !== '' && $this->scanResultFilterValue($row['result'] ?? null) !== $scanResult) {
            return false;
        }

        $scannerPost = trim((string) ($filters['scanner_post'] ?? ''));
        if ($scannerPost !== '' && trim((string) ($row['scanner_name'] ?? '')) !== $scannerPost) {
            return false;
        }

        $fromDate = $this->normalizeDateFilter($filters['from'] ?? null);
        $toDate = $this->normalizeDateFilter($filters['to'] ?? null);
        $scanDate = trim((string) ($row['scan_date'] ?? ''));

        if ($fromDate !== null && ($scanDate === '' || $scanDate < $fromDate)) {
            return false;
        }

        if ($toDate !== null && ($scanDate === '' || $scanDate > $toDate)) {
            return false;
        }

        return true;
    }

    private function rowHasParticipantIdentity(array $row): bool
    {
        return trim((string) ($row['participant_key'] ?? '')) !== ''
            && (
                trim((string) ($row['full_name'] ?? '')) !== ''
                || trim((string) ($row['email'] ?? '')) !== ''
                || trim((string) ($row['entry_code_display'] ?? '')) !== ''
                || trim((string) ($row['user_id'] ?? '')) !== ''
            );
    }

    private function rowHasActiveAttendanceForScanDate(array $row): bool
    {
        return (bool) ($row['attendance_active_for_scan_date'] ?? false);
    }

    private function rowActiveAttendanceAt(array $row): ?string
    {
        $value = trim((string) ($row['attendance_active_at'] ?? ''));

        return $value !== '' ? $value : null;
    }

    private function searchBlob(array $row): string
    {
        return $this->normalizeSearchText(implode(' ', array_filter([
            (string) ($row['scan_id'] ?? ''),
            (string) ($row['ticket_code'] ?? ''),
            (string) ($row['entry_code_display'] ?? ''),
            (string) ($row['user_id'] ?? ''),
            (string) ($row['full_name'] ?? ''),
            (string) ($row['email'] ?? ''),
            (string) ($row['phone_number'] ?? ''),
            (string) ($row['country'] ?? ''),
            (string) ($row['country_label'] ?? ''),
            (string) ($row['identity_type'] ?? ''),
            (string) ($row['identity_label'] ?? ''),
            (string) ($row['identity_number'] ?? ''),
            (string) ($row['scanner_name'] ?? ''),
            (string) ($row['scanner_id'] ?? ''),
            (string) ($row['scanner_role'] ?? ''),
        ])));
    }

    private function participantKey(
        string $userId,
        string $ticketCode,
        string $entryCodeDisplay,
        string $email,
        string $phoneNumber,
        string $fullName,
        string $scanId,
    ): string {
        if ($userId !== '') {
            return 'user:'.$userId;
        }

        if ($ticketCode !== '') {
            return 'ticket:'.strtoupper($ticketCode);
        }

        $normalizedEntryCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $entryCodeDisplay) ?? '');
        if ($normalizedEntryCode !== '') {
            return 'entry:'.$normalizedEntryCode;
        }

        if ($email !== '') {
            return 'email:'.mb_strtolower($email);
        }

        if ($phoneNumber !== '') {
            return 'phone:'.preg_replace('/\s+/', '', $phoneNumber);
        }

        if ($fullName !== '') {
            return 'name:'.$this->normalizeSearchText($fullName).':'.$scanId;
        }

        return '';
    }

    private function initials(string $value): string
    {
        $parts = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $letters = collect($parts)
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $letters !== '' ? $letters : 'P';
    }

    private function normalizeSearchText(string $value): string
    {
        return str_replace(["\r", "\n"], ' ', mb_strtolower(trim($value)));
    }

    private function normalizeDateFilter(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse(
                $value,
                (string) config('admin.event.timezone', config('app.timezone', 'UTC')),
            )->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveScanDate(mixed $scannedAt): string
    {
        $scannedAt = trim((string) $scannedAt);

        if ($scannedAt === '') {
            return '';
        }

        try {
            return CarbonImmutable::parse($scannedAt)
                ->setTimezone((string) config('admin.event.timezone', config('app.timezone', 'UTC')))
                ->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }

    private function attendanceTimestamp(array $attendanceRow): ?string
    {
        foreach (['first_scanned_at', 'updated_at', 'created_at'] as $field) {
            $value = trim((string) ($attendanceRow[$field] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeScanResult(mixed $result): string
    {
        $normalized = strtolower(trim((string) $result));

        return match ($normalized) {
            'success', 'duplicate', 'invalid' => $normalized,
            default => $normalized !== '' ? $normalized : 'invalid',
        };
    }

    private function normalizeAttendanceStatus(mixed $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'checked_in', 'cancelled', 'invalid' => $normalized,
            default => 'not_checked_in',
        };
    }

    private function normalizeIdentityType(mixed $identityType): string
    {
        return strtolower(trim((string) $identityType)) === 'national_id'
            ? 'national_id'
            : 'passport';
    }

    private function attendanceStatusLabel(string $status): string
    {
        return match ($this->normalizeAttendanceStatus($status)) {
            'checked_in' => 'Checked In',
            'cancelled' => 'Cancelled',
            'invalid' => 'Invalid',
            default => 'Not Checked In',
        };
    }

    private function scanResultFilterValue(mixed $result): string
    {
        return match ($this->normalizeScanResult($result)) {
            'success' => 'success',
            'duplicate' => 'duplicate',
            default => trim((string) $result) !== '' ? 'needs_review' : '',
        };
    }

    private function scanResultFilterLabel(string $scanResult): string
    {
        return match ($scanResult) {
            'success' => 'Checked In',
            'duplicate' => 'Repeat Scans',
            default => 'Needs Review',
        };
    }

    private function storeModel(array $rowsById, array $orderedScanIds, array $meta, array $sync): void
    {
        if ($this->usingRedisStorage()) {
            $this->storeModelUsingRedis($rowsById, $orderedScanIds, $meta, $sync);

            return;
        }

        Cache::forever(self::ROWS_CACHE_KEY, $rowsById);
        Cache::forever(self::ORDER_CACHE_KEY, $orderedScanIds);
        Cache::forever(self::META_CACHE_KEY, $meta);
        Cache::forever(self::SYNC_CACHE_KEY, $sync);
    }

    private function storeModelUsingRedis(array $rowsById, array $orderedScanIds, array $meta, array $sync): void
    {
        $redis = $this->redisConnection();
        $tempSuffix = (string) Str::ulid();
        $tempRowsKey = self::ROWS_CACHE_KEY.':tmp:'.$tempSuffix;
        $tempOrderKey = self::ORDER_CACHE_KEY.':tmp:'.$tempSuffix;
        $tempMetaKey = self::META_CACHE_KEY.':tmp:'.$tempSuffix;
        $tempSyncKey = self::SYNC_CACHE_KEY.':tmp:'.$tempSuffix;

        $redis->pipeline(function ($pipe) use ($meta, $orderedScanIds, $rowsById, $sync, $tempMetaKey, $tempOrderKey, $tempRowsKey, $tempSyncKey): void {
            $pipe->del($tempRowsKey, $tempOrderKey, $tempMetaKey, $tempSyncKey);

            foreach ($rowsById as $scanId => $row) {
                $pipe->hset($tempRowsKey, $scanId, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
            }

            foreach ($orderedScanIds as $scanId) {
                if (! isset($rowsById[$scanId])) {
                    continue;
                }

                $pipe->zadd($tempOrderKey, $this->scoreForRow($rowsById[$scanId]), $scanId);
            }

            $pipe->set($tempMetaKey, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
            $pipe->set($tempSyncKey, json_encode($sync, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        });

        $redis->pipeline(function ($pipe) use ($orderedScanIds, $rowsById, $tempMetaKey, $tempOrderKey, $tempRowsKey, $tempSyncKey): void {
            $pipe->del(self::ROWS_CACHE_KEY, self::ORDER_CACHE_KEY, self::META_CACHE_KEY, self::SYNC_CACHE_KEY);

            if ($rowsById !== []) {
                $pipe->rename($tempRowsKey, self::ROWS_CACHE_KEY);
            }

            if ($orderedScanIds !== []) {
                $pipe->rename($tempOrderKey, self::ORDER_CACHE_KEY);
            }

            $pipe->rename($tempMetaKey, self::META_CACHE_KEY);
            $pipe->rename($tempSyncKey, self::SYNC_CACHE_KEY);
        });
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

        usort($rows, fn (array $left, array $right): int => $this->scoreForRow($right) <=> $this->scoreForRow($left));

        return $rows;
    }

    private function allRowsUsingCache(): array
    {
        $rowsById = Cache::get(self::ROWS_CACHE_KEY, []);
        $rows = array_values(array_filter($rowsById, static fn (mixed $row): bool => is_array($row)));

        usort($rows, fn (array $left, array $right): int => $this->scoreForRow($right) <=> $this->scoreForRow($left));

        return $rows;
    }

    private function canRefreshMetaInline(): bool
    {
        $maxRows = max(0, (int) config('admin.attendance.inline_meta_sync_max_rows', 2000));

        if ($maxRows === 0) {
            return false;
        }

        $storedRowCount = $this->storedRowCount();

        if ($storedRowCount === null) {
            return false;
        }

        return $storedRowCount <= $maxRows;
    }

    private function storedRowCount(): ?int
    {
        if ($this->usingRedisStorage()) {
            $count = $this->redisConnection()->zcard(self::ORDER_CACHE_KEY);

            return is_numeric($count) ? (int) $count : null;
        }

        $orderedScanIds = Cache::get(self::ORDER_CACHE_KEY);

        if (is_array($orderedScanIds)) {
            return count($orderedScanIds);
        }

        $rowsById = Cache::get(self::ROWS_CACHE_KEY);

        return is_array($rowsById) ? count($rowsById) : null;
    }

    private function upsertRow(array $row): void
    {
        $scanId = trim((string) ($row['scan_id'] ?? ''));

        if ($scanId === '') {
            return;
        }

        if ($this->usingRedisStorage()) {
            $this->redisConnection()->hset(
                self::ROWS_CACHE_KEY,
                $scanId,
                json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            );
            $this->redisConnection()->zadd(self::ORDER_CACHE_KEY, $this->scoreForRow($row), $scanId);

            return;
        }

        $rowsById = Cache::get(self::ROWS_CACHE_KEY, []);
        $rowsById[$scanId] = $row;
        Cache::forever(self::ROWS_CACHE_KEY, $rowsById);

        $orderedScanIds = array_values(array_unique(array_merge(
            array_keys(array_filter($rowsById, static fn (mixed $existingRow): bool => is_array($existingRow))),
            [$scanId],
        )));
        usort($orderedScanIds, function (string $leftScanId, string $rightScanId) use ($rowsById): int {
            return $this->scoreForRow($rowsById[$rightScanId] ?? []) <=> $this->scoreForRow($rowsById[$leftScanId] ?? []);
        });

        Cache::forever(self::ORDER_CACHE_KEY, $orderedScanIds);
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
        $scannedAt = trim((string) ($row['scanned_at'] ?? ''));

        if ($scannedAt === '') {
            return 0.0;
        }

        try {
            return (float) CarbonImmutable::parse($scannedAt)->utc()->format('U.u');
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
