<?php

namespace App\Services\Admin;

use App\Services\Scanner\ScannerGateService;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AdminPanelService
{
    public const DASHBOARD_CACHE_VERSION_KEY = 'admin:dashboard:version';
    public const USER_MANAGEMENT_META_CACHE_KEY = 'admin:user-management:meta:v6';
    public const USER_MANAGEMENT_META_STALE_KEY = 'admin:user-management:meta:stale:v1';
    public const USER_MANAGEMENT_DIRECTORY_CACHE_KEY = 'admin:user-management:directory:v2';
    public const USER_MANAGEMENT_DIRECTORY_STALE_KEY = 'admin:user-management:directory:stale:v1';
    public const ATTENDANCE_DIRECTORY_CACHE_KEY = 'admin:attendance:directory:v1';
    public const ATTENDANCE_DIRECTORY_STALE_KEY = 'admin:attendance:directory:stale:v1';
    private const DASHBOARD_CACHE_KEY_PREFIX = 'admin:dashboard:v2';
    private const DASHBOARD_CACHE_TTL_SECONDS = 30;
    private const USER_MANAGEMENT_REFRESH_LOCK_KEY = 'admin:user-management:refreshing:v2';
    private const USER_MANAGEMENT_REFRESH_LOCK_SECONDS = 300;
    private const ATTENDANCE_REFRESH_LOCK_KEY = 'admin:attendance:refreshing:v1';
    private const ATTENDANCE_REFRESH_LOCK_SECONDS = 300;
    private const ACTIVITY_LOG_MAX_PER_PAGE = 100;

    public function __construct(
        private readonly AdminFirestoreRepository $repository,
        private readonly AdminAnalyticsService $analytics,
        private readonly AdminParticipantNotificationService $participantNotifications,
        private readonly ScannerGateService $scannerGates,
    ) {}

    public function firestoreAvailable(): bool
    {
        return $this->repository->available();
    }

    public function dashboardData(array $filters = []): array
    {
        $cacheKey = $this->dashboardCacheKey($filters);

        return Cache::remember(
            $cacheKey,
            now()->addSeconds(self::DASHBOARD_CACHE_TTL_SECONDS),
            fn (): array => $this->buildDashboardSnapshot($filters),
        );
    }

    public function userListing(array $filters = []): LengthAwarePaginator
    {
        return $this->userManagementPage($filters)['users'];
    }

    public function userManagementPage(array $filters = []): array
    {
        if ($this->shouldUseOptimizedUserManagementQuery($filters)) {
            try {
                return $this->optimizedUserManagementPage($filters);
            } catch (\Throwable) {
                // Fall back to the legacy in-memory implementation if the
                // Firestore query path is not available yet (for example,
                // missing composite indexes in a new project).
            }
        }

        try {
            return $this->cachedDirectoryUserManagementPage($filters);
        } catch (\Throwable) {
            return $this->legacyUserManagementPage($filters);
        }
    }

    public function flushUserManagementCache(): void
    {
        Cache::forget(self::USER_MANAGEMENT_META_STALE_KEY);
        Cache::forget(self::USER_MANAGEMENT_DIRECTORY_STALE_KEY);
        Cache::forget(self::USER_MANAGEMENT_REFRESH_LOCK_KEY);
        Cache::forget(self::USER_MANAGEMENT_META_CACHE_KEY);
        Cache::forget(self::USER_MANAGEMENT_DIRECTORY_CACHE_KEY);
    }

    public function markUserManagementCacheStale(): void
    {
        Cache::forever(self::USER_MANAGEMENT_META_STALE_KEY, true);
        Cache::forever(self::USER_MANAGEMENT_DIRECTORY_STALE_KEY, true);
    }

    public function warmUserManagementCache(): array
    {
        return $this->refreshUserManagementCaches();
    }

    public function flushAttendanceMonitoringCache(): void
    {
        Cache::forget(self::ATTENDANCE_DIRECTORY_STALE_KEY);
        Cache::forget(self::ATTENDANCE_REFRESH_LOCK_KEY);
        Cache::forget(self::ATTENDANCE_DIRECTORY_CACHE_KEY);
    }

    public function markAttendanceMonitoringCacheStale(): void
    {
        Cache::forever(self::ATTENDANCE_DIRECTORY_STALE_KEY, true);
    }

    public function warmAttendanceMonitoringCache(): array
    {
        return $this->refreshAttendanceDirectoryCache();
    }

    public function flushDashboardCache(): void
    {
        Cache::forever(
            self::DASHBOARD_CACHE_VERSION_KEY,
            $this->dashboardCacheVersion() + 1,
        );
    }

    public function findUser(string $userId): ?array
    {
        $user = $this->repository->findUser($userId);

        if ($user === null) {
            return null;
        }

        return $this->hydrateUser($user);
    }

    public function updateUserByAdmin(string $userId, array $attributes): array
    {
        $previousUser = $this->findUser($userId);
        $user = $this->hydrateUser($this->repository->updateUserByAdmin($userId, $attributes));
        $ticket = is_array($user['ticket'] ?? null) ? $user['ticket'] : null;

        $this->dispatchAfterResponseSafely(
            function () use ($previousUser, $ticket, $user): void {
                $this->participantNotifications->sendProfileUpdated(
                    $previousUser ?? [],
                    $user,
                    $ticket,
                );
            },
            'Failed to send participant profile update notification.',
            [
                'user_id' => $userId,
                'email' => (string) ($user['email'] ?? ''),
            ],
        );
        $this->markUserManagementCacheStale();
        $this->scheduleUserManagementCacheRefreshAfterResponse();

        return $user;
    }

    public function deleteUserByAdmin(string $userId): array
    {
        $result = $this->repository->deleteUserByAdmin($userId);
        $this->participantNotifications->sendParticipantDeleted(
            $result['user'] ?? [],
            is_array($result['ticket'] ?? null) ? $result['ticket'] : null,
        );
        $this->markUserManagementCacheStale();
        $this->scheduleUserManagementCacheRefreshAfterResponse();
        $this->flushDashboardCache();

        return $result;
    }

    public function resetQrCode(string $userId): array
    {
        $result = $this->repository->resetQrCode($userId);
        $this->markUserManagementCacheStale();
        $this->scheduleUserManagementCacheRefreshAfterResponse();

        return $result;
    }

    public function regenerateQrCode(string $userId): array
    {
        $result = $this->repository->regenerateQrCode($userId);
        $result['user'] = $this->hydrateUser(
            $result['user'],
            is_array($result['ticket'] ?? null) ? $result['ticket'] : null,
        );
        $this->participantNotifications->sendQrRegenerated(
            $result['user'],
            is_array($result['ticket'] ?? null) ? $result['ticket'] : [],
        );
        $this->markUserManagementCacheStale();
        $this->scheduleUserManagementCacheRefreshAfterResponse();

        return $result;
    }

    public function attendanceData(array $filters = []): array
    {
        $filters = $this->normalizeAttendanceFilters($filters);

        if ($this->shouldUseOptimizedAttendanceQuery($filters)) {
            try {
                return $this->optimizedAttendanceData($filters);
            } catch (RuntimeException) {
                // Fall back to the legacy in-memory implementation if Firestore
                // indexes are not ready yet in a new project.
            }
        }

        try {
            return $this->cachedAttendanceData($filters);
        } catch (\Throwable) {
            // Fall back to the legacy in-memory implementation if the cached
            // attendance directory is unavailable for any reason.
        }

        return $this->legacyAttendanceData($filters);
    }

    public function activityLogs(array $filters = []): LengthAwarePaginator
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = $this->sanitizeActivityLogPerPage(
            (int) ($filters['per_page'] ?? config('admin.per_page', 10)),
        );

        if ($this->shouldUseOptimizedActivityLogQuery($filters)) {
            try {
                $pageResult = $this->repository->paginateAdminActivityLogs($filters, $page, $perPage);

                return new LengthAwarePaginator(
                    $pageResult['items'] ?? [],
                    (int) ($pageResult['total'] ?? 0),
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                        'pageName' => 'page',
                    ],
                );
            } catch (RuntimeException) {
                // Fall back to the in-memory implementation if query indexes
                // are not ready yet in a new Firestore project.
            }
        }

        $rows = $this->analytics->buildActivityLogRows(
            $this->repository->queryAdminActivityLogs($filters),
            $filters,
        );

        return $this->makePaginator(
            $rows,
            $page,
            $perPage,
            request()->url(),
            request()->query(),
        );
    }

    public function reports(array $filters = []): array
    {
        return $this->analytics->buildReports(
            [],
            [],
            $this->repository->queryScanLogs($filters),
            $filters,
        );
    }

    public function exportRows(string $type, array $filters = []): array
    {
        return match ($type) {
            'users' => $this->analytics->filterUserRows(
                $this->cachedUserManagementDirectory(),
                $filters,
            ),
            'attendance' => $this->analytics->buildExportDataset(
                $type,
                [],
                [],
                $this->repository->queryScanLogs($filters),
                [],
                $filters,
            ),
            'admin-logs' => $this->analytics->buildExportDataset(
                $type,
                [],
                [],
                [],
                $this->repository->queryAdminActivityLogs($filters),
                $filters,
            ),
            'daily-report', 'overall-report' => $this->analytics->buildExportDataset(
                $type,
                [],
                [],
                $this->repository->queryScanLogs($filters),
                [],
                $filters,
            ),
            default => [],
        };
    }

    public function backupSnapshot(): array
    {
        return $this->repository->snapshotCollections();
    }

    private function legacyAttendanceData(array $filters = []): array
    {
        $scanLogs = $this->repository->allScanLogs();
        $overview = $this->analytics->buildAttendanceOverview($scanLogs, $filters);
        $overview['history'] = $this->hydrateAttendanceHistory(
            $overview['history'],
            $this->repository->allUsers(),
            $this->repository->allTickets(),
        );

        $historyPaginator = $this->makePaginator(
            $overview['history'],
            (int) ($filters['page'] ?? 1),
            (int) ($filters['per_page'] ?? config('admin.per_page', 10)),
            request()->url(),
            request()->query(),
        );

        return [
            'history' => $historyPaginator,
            'daily_attendance' => $overview['daily_attendance'],
            'scanner_activity' => $overview['scanner_activity'],
            'scan_post_options' => $this->buildAttendanceScanPostOptions(
                $overview['history'],
                [$filters['scanner_post'] ?? null],
            ),
        ];
    }

    private function optimizedAttendanceData(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? config('admin.per_page', 10)));
        $filteredCachedRows = $this->filterCachedAttendanceRows(
            $this->cachedAttendanceDirectory(),
            $filters,
        );
        $overview = $this->analytics->buildAttendanceOverview($filteredCachedRows);
        $pageResult = $this->repository->paginateScanLogs($filters, $page, $perPage);
        $historyItems = $this->hydrateAttendanceHistoryPage(
            $this->mergeCachedAttendanceRows(
                $pageResult['items'] ?? [],
                $overview['history'],
            ),
        );
        $historyPaginator = new LengthAwarePaginator(
            $historyItems,
            (int) ($pageResult['total'] ?? 0),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => 'page',
            ],
        );

        return [
            'history' => $historyPaginator,
            'daily_attendance' => $overview['daily_attendance'],
            'scanner_activity' => $overview['scanner_activity'],
            'scan_post_options' => $this->buildAttendanceScanPostOptions(
                $overview['history'],
                [$filters['scanner_post'] ?? null],
            ),
        ];
    }

    private function buildDashboardSnapshot(array $filters = []): array
    {
        $days = (int) config('admin.dashboard_days', 7);
        $dashboardRange = $this->analytics->dashboardRange($days, filters: $filters);
        $dashboardQueryFilters = [
            'from' => $dashboardRange['from'],
            'to' => $dashboardRange['to'],
        ];
        $totalRegistrations = $dashboardRange['is_filtered']
            ? $this->repository->countUsersByRegistrationDate($dashboardQueryFilters)
            : $this->repository->countUsers();

        return $this->analytics->buildDashboardFromRegistrationCount(
            $totalRegistrations,
            $this->repository->queryScanLogs($dashboardQueryFilters),
            $days,
            filters: $filters,
        );
    }

    private function legacyUserManagementPage(array $filters = []): array
    {
        $allRows = $this->analytics->buildUserRows(
            $this->repository->allUsers(),
            $this->repository->allTickets(),
        );
        $allRows = $this->analytics->attachAttendanceProgress(
            $allRows,
            $this->repository->allScanLogs(),
        );
        $filteredRows = $this->analytics->filterUserRows($allRows, $filters);

        return [
            'users' => $this->makePaginator(
                $filteredRows,
                (int) ($filters['page'] ?? 1),
                (int) ($filters['per_page'] ?? config('admin.per_page', 10)),
                request()->url(),
                request()->query(),
            ),
            'overview' => $this->analytics->buildUserManagementOverview($filteredRows),
            'filter_options' => $this->analytics->buildUserFilterOptions($allRows),
        ];
    }

    private function optimizedUserManagementPage(array $filters = []): array
    {
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? config('admin.per_page', 10));
        $pageResult = $this->repository->paginateUsers($filters, $page, $perPage);
        $ticketRows = $this->repository->findTicketsByIds(array_map(
            fn (array $user): string => (string) ($user['ticket_id'] ?? ''),
            $pageResult['items'],
        ));
        $scanLogs = $this->repository->findScanLogsByUserIds(array_map(
            fn (array $user): string => (string) ($user['user_id'] ?? ''),
            $pageResult['items'],
        ));
        $rows = $this->analytics->attachAttendanceProgress(
            $this->analytics->buildUserRows($pageResult['items'], $ticketRows),
            $scanLogs,
        );
        $meta = $this->cachedUserManagementMeta();
        $overview = is_array($meta['overview'] ?? null) ? $meta['overview'] : [];
        $overview['total_users'] = (int) ($pageResult['total'] ?? 0);

        return [
            'users' => new LengthAwarePaginator(
                $rows,
                (int) ($pageResult['total'] ?? 0),
                max(1, $perPage),
                max(1, $page),
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                    'pageName' => 'page',
                ],
            ),
            'overview' => $overview,
            'filter_options' => $meta['filter_options'],
        ];
    }

    private function cachedAttendanceData(array $filters = []): array
    {
        $filteredRows = $this->filterCachedAttendanceRows(
            $this->cachedAttendanceDirectory(),
            $filters,
        );
        $overview = $this->analytics->buildAttendanceOverview($filteredRows);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? config('admin.per_page', 10)));
        $offset = ($page - 1) * $perPage;
        $pageRows = array_values(array_slice($overview['history'], $offset, $perPage));

        return [
            'history' => new LengthAwarePaginator(
                $pageRows,
                count($overview['history']),
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                    'pageName' => 'page',
                ],
            ),
            'daily_attendance' => $overview['daily_attendance'],
            'scanner_activity' => $overview['scanner_activity'],
            'scan_post_options' => $this->buildAttendanceScanPostOptions(
                $overview['history'],
                [$filters['scanner_post'] ?? null],
            ),
        ];
    }

    private function cachedDirectoryUserManagementPage(array $filters = []): array
    {
        $allRows = $this->cachedUserManagementDirectory();
        $filteredRows = $this->analytics->filterUserRows($allRows, $filters);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? config('admin.per_page', 10)));
        $offset = ($page - 1) * $perPage;
        $pageRows = array_values(array_slice($filteredRows, $offset, $perPage));
        $scanLogs = $this->repository->findScanLogsByUserIds(array_map(
            fn (array $row): string => (string) ($row['user_id'] ?? ''),
            $pageRows,
        ));
        $meta = $this->cachedUserManagementMeta();
        $rows = $this->analytics->decorateEmailTypoRows(
            $this->analytics->attachAttendanceProgress($pageRows, $scanLogs),
        );

        return [
            'users' => new LengthAwarePaginator(
                $rows,
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
            'filter_options' => $meta['filter_options'],
        ];
    }

    private function cachedUserManagementMeta(): array
    {
        $cachedSnapshot = Cache::get(self::USER_MANAGEMENT_META_CACHE_KEY);

        if (is_array($cachedSnapshot)) {
            return $cachedSnapshot;
        }

        return $this->refreshUserManagementMetaCache();
    }

    private function cachedUserManagementDirectory(): array
    {
        $cachedRows = Cache::get(self::USER_MANAGEMENT_DIRECTORY_CACHE_KEY);

        if (is_array($cachedRows)) {
            return $cachedRows;
        }

        return $this->refreshUserManagementDirectoryCache();
    }

    private function cachedAttendanceDirectory(): array
    {
        $cachedRows = Cache::get(self::ATTENDANCE_DIRECTORY_CACHE_KEY);

        if (is_array($cachedRows)) {
            if (Cache::has(self::ATTENDANCE_DIRECTORY_STALE_KEY)) {
                $this->scheduleAttendanceCacheRefreshAfterResponse();
            }

            return $cachedRows;
        }

        return $this->refreshAttendanceDirectoryCache();
    }

    private function refreshUserManagementMetaCache(): array
    {
        $rows = Cache::get(self::USER_MANAGEMENT_DIRECTORY_CACHE_KEY);

        if (! is_array($rows)) {
            $rows = $this->refreshUserManagementDirectoryCache();
        }

        return $this->refreshUserManagementMetaCacheFromRows($rows);
    }

    private function refreshUserManagementDirectoryCache(): array
    {
        $rows = $this->analytics->buildUserRows(
            $this->repository->allUsers(),
            $this->repository->allTickets(),
        );

        Cache::forever(self::USER_MANAGEMENT_DIRECTORY_CACHE_KEY, $rows);
        Cache::forget(self::USER_MANAGEMENT_DIRECTORY_STALE_KEY);

        return $rows;
    }

    private function refreshAttendanceDirectoryCache(): array
    {
        $userDirectory = $this->cachedUserManagementDirectory();
        $participantsByUserId = [];
        $participantsByTicketCode = [];

        foreach ($userDirectory as $row) {
            $participant = $this->attendanceParticipantFromUserDirectoryRow($row);

            if ($participant === null) {
                continue;
            }

            $userId = trim((string) ($row['user_id'] ?? ''));
            $ticketCode = strtoupper(trim((string) ($participant['ticket_code'] ?? '')));

            if ($userId !== '') {
                $participantsByUserId[$userId] = $participant;
            }

            if ($ticketCode !== '') {
                $participantsByTicketCode[$ticketCode] = $participant;
            }
        }

        $rows = array_map(
            fn (array $log): array => $this->buildAttendanceDirectoryRow(
                $log,
                $participantsByUserId,
                $participantsByTicketCode,
            ),
            $this->repository->allScanLogs(),
        );

        Cache::forever(self::ATTENDANCE_DIRECTORY_CACHE_KEY, $rows);
        Cache::forget(self::ATTENDANCE_DIRECTORY_STALE_KEY);

        return $rows;
    }

    private function refreshUserManagementCaches(): array
    {
        $directory = $this->refreshUserManagementDirectoryCache();

        return [
            'meta' => $this->refreshUserManagementMetaCacheFromRows($directory),
            'directory' => $directory,
        ];
    }

    private function refreshUserManagementMetaCacheFromRows(array $rows): array
    {
        $snapshot = [
            'overview' => $this->analytics->buildUserManagementOverview($rows),
            'filter_options' => $this->analytics->buildUserFilterOptions($rows),
        ];

        Cache::forever(self::USER_MANAGEMENT_META_CACHE_KEY, $snapshot);
        Cache::forget(self::USER_MANAGEMENT_META_STALE_KEY);

        return $snapshot;
    }

    private function scheduleUserManagementCacheRefreshAfterResponse(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (! Cache::add(
            self::USER_MANAGEMENT_REFRESH_LOCK_KEY,
            now()->toIso8601String(),
            now()->addSeconds(self::USER_MANAGEMENT_REFRESH_LOCK_SECONDS),
        )) {
            return;
        }

        app()->terminating(function (): void {
            try {
                $this->refreshUserManagementCaches();
            } catch (\Throwable) {
                // Keep serving the last known snapshot and retry later rather
                // than slowing down the current admin page load.
            } finally {
                Cache::forget(self::USER_MANAGEMENT_REFRESH_LOCK_KEY);
            }
        });
    }

    private function scheduleAttendanceCacheRefreshAfterResponse(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (! Cache::add(
            self::ATTENDANCE_REFRESH_LOCK_KEY,
            now()->toIso8601String(),
            now()->addSeconds(self::ATTENDANCE_REFRESH_LOCK_SECONDS),
        )) {
            return;
        }

        app()->terminating(function (): void {
            try {
                $this->refreshAttendanceDirectoryCache();
            } catch (\Throwable) {
                // Keep serving the last known scan directory and retry later
                // instead of slowing down the current admin request.
            } finally {
                Cache::forget(self::ATTENDANCE_REFRESH_LOCK_KEY);
            }
        });
    }

    private function dispatchAfterResponseSafely(callable $callback, string $message, array $context = []): void
    {
        $runner = function () use ($callback, $context, $message): void {
            try {
                $callback();
            } catch (\Throwable $exception) {
                Log::warning($message, array_merge($context, [
                    'exception' => $exception::class,
                    'error' => $exception->getMessage(),
                ]));
            }
        };

        if (app()->environment('testing')) {
            $runner();

            return;
        }

        app()->terminating($runner);
    }

    private function buildAttendanceDirectoryRow(
        array $log,
        array $participantsByUserId,
        array $participantsByTicketCode,
    ): array {
        $participant = $this->attendanceParticipantFromSnapshot($log);
        $userId = trim((string) ($log['user_id'] ?? ''));
        $ticketCode = strtoupper(trim((string) ($log['ticket_code'] ?? '')));

        if ($participant === null && $userId !== '') {
            $participant = $participantsByUserId[$userId] ?? null;
        }

        if ($participant === null && $ticketCode !== '') {
            $participant = $participantsByTicketCode[$ticketCode] ?? null;
        }

        $entryCodeDisplay = trim((string) ($log['entry_code_display'] ?? ''));
        if ($entryCodeDisplay === '' && is_array($participant)) {
            $entryCodeDisplay = trim((string) ($participant['entry_code_display'] ?? ''));
        }

        $row = [
            'scan_id' => (string) ($log['scan_id'] ?? ''),
            'ticket_id' => (string) ($log['ticket_id'] ?? ''),
            'ticket_code' => (string) ($log['ticket_code'] ?? ''),
            'user_id' => (string) ($log['user_id'] ?? ''),
            'scanner_id' => (string) ($log['scanner_id'] ?? ''),
            'scanner_name' => (string) ($log['scanner_name'] ?? ''),
            'scanner_role' => (string) ($log['scanner_role'] ?? ''),
            'scanned_at' => (string) ($log['scanned_at'] ?? ''),
            'scan_date' => (string) ($log['scan_date'] ?? ''),
            'result' => (string) ($log['result'] ?? ''),
            'entry_code_display' => $entryCodeDisplay,
            'participant' => $participant,
        ];

        $row['search_blob'] = $this->buildAttendanceSearchBlob($row);

        return $row;
    }

    private function attendanceParticipantFromSnapshot(array $log): ?array
    {
        $snapshot = $log['participant_snapshot'] ?? null;

        if (! is_array($snapshot)) {
            return null;
        }

        $fullName = trim((string) ($snapshot['full_name'] ?? $snapshot['name'] ?? ''));
        $country = strtoupper(trim((string) ($snapshot['country'] ?? '')));
        $countryLabel = trim((string) ($snapshot['country_label'] ?? ''));

        if ($countryLabel === '' && $country !== '') {
            $countryLabel = $this->analytics->countryLabel($country);
        }

        return [
            'name' => $fullName,
            'full_name' => $fullName,
            'email' => trim((string) ($snapshot['email'] ?? '')),
            'phone_number' => trim((string) ($snapshot['phone_number'] ?? '')),
            'country' => $country,
            'country_label' => $countryLabel,
            'ticket_code' => (string) ($snapshot['ticket_code'] ?? $log['ticket_code'] ?? ''),
            'entry_code_display' => (string) ($snapshot['entry_code_display'] ?? $log['entry_code_display'] ?? ''),
        ];
    }

    private function attendanceParticipantFromUserDirectoryRow(array $row): ?array
    {
        $fullName = trim((string) ($row['full_name'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $phoneNumber = $this->participantPhoneNumber($row);
        $country = strtoupper(trim((string) ($row['country'] ?? '')));
        $ticketCode = (string) ($row['ticket_code'] ?? '');

        if ($fullName === '' && $email === '' && $phoneNumber === '' && $ticketCode === '') {
            return null;
        }

        return [
            'name' => $fullName,
            'full_name' => $fullName,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'country' => $country,
            'country_label' => (string) ($row['country_label'] ?? ($country !== '' ? $this->analytics->countryLabel($country) : '')),
            'ticket_code' => $ticketCode,
            'entry_code_display' => (string) ($row['entry_code_display'] ?? ''),
        ];
    }

    private function buildAttendanceSearchBlob(array $row): string
    {
        $participant = is_array($row['participant'] ?? null) ? $row['participant'] : null;

        return $this->normalizeAttendanceSearchText(implode(' ', array_filter([
            (string) ($row['scan_id'] ?? ''),
            (string) ($row['ticket_code'] ?? ''),
            (string) ($row['user_id'] ?? ''),
            (string) ($row['scanner_id'] ?? ''),
            (string) ($row['scanner_name'] ?? ''),
            (string) ($row['result'] ?? ''),
            (string) ($row['entry_code_display'] ?? ''),
            (string) ($participant['full_name'] ?? $participant['name'] ?? ''),
            (string) ($participant['email'] ?? ''),
            (string) ($participant['phone_number'] ?? ''),
            (string) ($participant['country'] ?? ''),
            (string) ($participant['country_label'] ?? ''),
        ])));
    }

    private function filterCachedAttendanceRows(array $rows, array $filters): array
    {
        $query = $this->normalizeAttendanceSearchText((string) ($filters['q'] ?? ''));
        $fromDate = $this->normalizeAttendanceFilterDate($filters['from'] ?? null);
        $toDate = $this->normalizeAttendanceFilterDate($filters['to'] ?? null);
        $scannerPost = trim((string) ($filters['scanner_post'] ?? ''));

        return array_values(array_filter($rows, function (array $row) use ($fromDate, $query, $scannerPost, $toDate): bool {
            $scanDate = trim((string) ($row['scan_date'] ?? ''));

            if ($fromDate !== null && $scanDate < $fromDate) {
                return false;
            }

            if ($toDate !== null && $scanDate > $toDate) {
                return false;
            }

            if ($scannerPost !== '' && (string) ($row['scanner_name'] ?? '') !== $scannerPost) {
                return false;
            }

            if ($query !== '' && ! str_contains((string) ($row['search_blob'] ?? ''), $query)) {
                return false;
            }

            return true;
        }));
    }

    private function mergeCachedAttendanceRows(array $rows, array $cachedRows): array
    {
        $cachedByScanId = [];

        foreach ($cachedRows as $cachedRow) {
            $scanId = trim((string) ($cachedRow['scan_id'] ?? ''));

            if ($scanId === '') {
                continue;
            }

            $cachedByScanId[$scanId] = $cachedRow;
        }

        return array_map(function (array $row) use ($cachedByScanId): array {
            $scanId = trim((string) ($row['scan_id'] ?? ''));
            $cachedRow = $scanId !== '' ? ($cachedByScanId[$scanId] ?? null) : null;

            if (! is_array($cachedRow)) {
                return $row;
            }

            if (! filled($row['entry_code_display'] ?? null) && filled($cachedRow['entry_code_display'] ?? null)) {
                $row['entry_code_display'] = $cachedRow['entry_code_display'];
            }

            if (is_array($cachedRow['participant'] ?? null)) {
                $row['participant'] = $cachedRow['participant'];
            }

            return $row;
        }, $rows);
    }

    private function normalizeAttendanceSearchText(string $value): string
    {
        return str_replace(["\r", "\n"], ' ', mb_strtolower(trim($value)));
    }

    private function normalizeAttendanceFilterDate(mixed $value): ?string
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

    private function shouldUseOptimizedUserManagementQuery(array $filters): bool
    {
        return trim((string) ($filters['q'] ?? '')) === ''
            && trim((string) ($filters['attendance_status'] ?? '')) === ''
            && trim((string) ($filters['email_typo'] ?? '')) === ''
            && trim((string) ($filters['verification_status'] ?? '')) !== 'pending_verification';
    }

    private function shouldUseOptimizedActivityLogQuery(array $filters): bool
    {
        return trim((string) ($filters['q'] ?? '')) === '';
    }

    private function shouldUseOptimizedAttendanceQuery(array $filters): bool
    {
        return trim((string) ($filters['q'] ?? '')) === ''
            && trim((string) ($filters['scanner_post'] ?? '')) === '';
    }

    private function hydrateUser(array $user, ?array $ticketOverride = null): array
    {
        $ticket = $ticketOverride;

        if ($ticket === null) {
            $ticketId = (string) ($user['ticket_id'] ?? '');
            $ticket = $ticketId !== '' ? $this->repository->findTicket($ticketId) : null;
        }

        return $this->analytics->decorateTrafficAttribution(array_merge($user, [
            'ticket' => $ticket,
            'country_label' => $this->analytics->countryLabel($user['country'] ?? null),
        ]));
    }

    private function normalizeAttendanceFilters(array $filters): array
    {
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));

        if ($from === '' && $to === '') {
            $latestDate = $this->resolveAttendanceDefaultToDate();
            $filters['from'] = $latestDate;
            $filters['to'] = $latestDate;

            return $filters;
        }

        if ($from === '' && $to !== '') {
            $filters['from'] = $to;
        }

        if ($to === '' && $from !== '') {
            $filters['to'] = $from;
        }

        return $filters;
    }

    private function resolveAttendanceDefaultToDate(): string
    {
        $timezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));
        $today = CarbonImmutable::now($timezone)->toDateString();

        return $this->repository->latestNonFutureScanLogDate() ?? $today;
    }

    private function sanitizeActivityLogPerPage(int $perPage): int
    {
        return min(max(1, $perPage), self::ACTIVITY_LOG_MAX_PER_PAGE);
    }

    private function dashboardCacheKey(array $filters): string
    {
        $dashboardRange = $this->analytics->dashboardRange(
            (int) config('admin.dashboard_days', 7),
            filters: $filters,
        );

        return implode(':', [
            self::DASHBOARD_CACHE_KEY_PREFIX,
            (string) $this->dashboardCacheVersion(),
            $dashboardRange['from'],
            $dashboardRange['to'],
            $dashboardRange['is_filtered'] ? 'filtered' : 'default',
        ]);
    }

    private function dashboardCacheVersion(): int
    {
        return max(1, (int) Cache::get(self::DASHBOARD_CACHE_VERSION_KEY, 1));
    }

    private function makePaginator(
        array $items,
        int $page,
        int $perPage,
        string $path,
        array $query,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            array_slice($items, $offset, $perPage),
            count($items),
            $perPage,
            $page,
            [
                'path' => $path,
                'query' => $query,
                'pageName' => 'page',
            ],
        );
    }

    private function buildAttendanceScanPostOptions(array $scanLogs, array $extraNames = []): array
    {
        $options = [];
        $scannerNames = array_values(array_unique(array_filter(array_map(
            static fn (mixed $name): string => trim((string) $name),
            array_merge($this->scannerGates->names(), $extraNames),
        ))));

        foreach ($scannerNames as $scannerName) {
            $options[$scannerName] = [
                'value' => $scannerName,
                'label' => $scannerName,
                'scanner_role' => '',
                'scanner_id' => '',
            ];
        }

        foreach ($scanLogs as $scanLog) {
            $scannerName = trim((string) ($scanLog['scanner_name'] ?? ''));
            if ($scannerName === '') {
                continue;
            }

            $options[$scannerName] ??= [
                'value' => $scannerName,
                'label' => $scannerName,
                'scanner_role' => trim((string) ($scanLog['scanner_role'] ?? '')),
                'scanner_id' => trim((string) ($scanLog['scanner_id'] ?? '')),
            ];
        }

        uasort($options, fn (array $left, array $right): int => strcasecmp(
            (string) ($left['label'] ?? ''),
            (string) ($right['label'] ?? ''),
        ));

        return array_values($options);
    }

    private function hydrateAttendanceHistoryPage(array $history): array
    {
        $rowsNeedingHydration = array_values(array_filter(
            $history,
            fn (array $log): bool => ! is_array($log['participant'] ?? null),
        ));

        if ($rowsNeedingHydration === []) {
            return $this->hydrateAttendanceHistory($history, [], []);
        }

        $ticketIds = array_values(array_unique(array_filter(array_map(
            fn (array $log): string => trim((string) ($log['ticket_id'] ?? '')),
            $rowsNeedingHydration,
        ))));
        $ticketRows = $this->repository->findTicketsByIds($ticketIds);

        $ticketUserIds = array_filter(array_map(
            fn (array $ticket): string => trim((string) ($ticket['user_id'] ?? '')),
            $ticketRows,
        ));
        $logUserIds = array_filter(array_map(
            fn (array $log): string => trim((string) ($log['user_id'] ?? '')),
            $rowsNeedingHydration,
        ));
        $userRows = $this->repository->findUsersByIds(array_values(array_unique(array_merge(
            $logUserIds,
            $ticketUserIds,
        ))));

        return $this->hydrateAttendanceHistory($history, $userRows, $ticketRows);
    }

    private function buildAttendanceDailySummary(array $filters): array
    {
        $rows = [];

        foreach ($this->attendanceFilterDates($filters) as $scanDate) {
            $dayFilters = array_merge($filters, [
                'from' => $scanDate,
                'to' => $scanDate,
            ]);
            $totalScans = $this->repository->countScanLogs($dayFilters);

            if ($totalScans <= 0) {
                continue;
            }

            $successfulAttendance = $this->repository->countScanLogs(array_merge($dayFilters, [
                'result' => 'success',
            ]));
            $duplicateScans = $this->repository->countScanLogs(array_merge($dayFilters, [
                'result' => 'duplicate',
            ]));

            $rows[] = [
                'scan_date' => $scanDate,
                'total_scans' => $totalScans,
                'successful_attendance' => $successfulAttendance,
                'duplicate_scans' => $duplicateScans,
                'invalid_scans' => max(0, $totalScans - $successfulAttendance - $duplicateScans),
            ];
        }

        usort($rows, fn (array $left, array $right): int => strcmp(
            (string) ($right['scan_date'] ?? ''),
            (string) ($left['scan_date'] ?? ''),
        ));

        return $rows;
    }

    private function buildAttendanceScannerActivity(array $filters, array $history): array
    {
        $rows = [];

        foreach ($this->resolveAttendanceScannerNames($filters, $history) as $scannerName) {
            $scannerFilters = array_merge($filters, ['scanner_post' => $scannerName]);
            $totalScans = $this->repository->countScanLogs($scannerFilters);

            if ($totalScans <= 0) {
                continue;
            }

            $successfulScans = $this->repository->countScanLogs(array_merge($scannerFilters, [
                'result' => 'success',
            ]));
            $duplicateScans = $this->repository->countScanLogs(array_merge($scannerFilters, [
                'result' => 'duplicate',
            ]));
            $latestLog = $this->repository->latestScanLog($scannerFilters);

            $rows[] = [
                'scanner_id' => (string) ($latestLog['scanner_id'] ?? ''),
                'scanner_name' => (string) ($latestLog['scanner_name'] ?? $scannerName),
                'scanner_role' => (string) ($latestLog['scanner_role'] ?? ''),
                'total_scans' => $totalScans,
                'successful_scans' => $successfulScans,
                'duplicate_scans' => $duplicateScans,
                'invalid_scans' => max(0, $totalScans - $successfulScans - $duplicateScans),
                'last_scanned_at' => $latestLog['scanned_at'] ?? null,
            ];
        }

        usort($rows, function (array $left, array $right): int {
            $byLastScannedAt = strcmp(
                (string) ($right['last_scanned_at'] ?? ''),
                (string) ($left['last_scanned_at'] ?? ''),
            );

            if ($byLastScannedAt !== 0) {
                return $byLastScannedAt;
            }

            $byTotalScans = ($right['total_scans'] ?? 0) <=> ($left['total_scans'] ?? 0);
            if ($byTotalScans !== 0) {
                return $byTotalScans;
            }

            return strcmp(
                (string) ($left['scanner_name'] ?? ''),
                (string) ($right['scanner_name'] ?? ''),
            );
        });

        return $rows;
    }

    private function attendanceFilterDates(array $filters): array
    {
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));
        $timezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));

        if ($from === '' && $to === '') {
            return [];
        }

        try {
            $fromDate = CarbonImmutable::parse($from !== '' ? $from : $to, $timezone)->startOfDay();
            $toDate = CarbonImmutable::parse($to !== '' ? $to : $from, $timezone)->startOfDay();
        } catch (\Throwable) {
            return [];
        }

        if ($fromDate->greaterThan($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $dates = [];
        for ($cursor = $fromDate; $cursor->lessThanOrEqualTo($toDate); $cursor = $cursor->addDay()) {
            $dates[] = $cursor->toDateString();
        }

        return $dates;
    }

    private function resolveAttendanceScannerNames(array $filters, array $history): array
    {
        $selectedScannerPost = trim((string) ($filters['scanner_post'] ?? ''));

        if ($selectedScannerPost !== '') {
            return [$selectedScannerPost];
        }

        $historyScannerNames = array_map(
            fn (array $log): string => trim((string) ($log['scanner_name'] ?? '')),
            $history,
        );

        return array_values(array_unique(array_filter(array_merge(
            $this->scannerGates->names(),
            $historyScannerNames,
        ))));
    }

    private function hydrateAttendanceHistory(array $history, array $users, array $tickets): array
    {
        if ($history === []) {
            return [];
        }

        $usersById = [];
        $ticketsById = [];
        $ticketsByCode = [];

        foreach ($users as $user) {
            $userId = (string) ($user['user_id'] ?? '');
            if ($userId !== '') {
                $usersById[$userId] = $user;
            }
        }

        foreach ($tickets as $ticket) {
            $ticketId = (string) ($ticket['ticket_id'] ?? '');
            $ticketCode = strtoupper(trim((string) ($ticket['ticket_code'] ?? '')));

            if ($ticketId !== '') {
                $ticketsById[$ticketId] = $ticket;
            }

            if ($ticketCode !== '') {
                $ticketsByCode[$ticketCode] = $ticket;
            }
        }

        return array_map(function (array $log) use ($ticketsByCode, $ticketsById, $usersById): array {
            $ticketId = (string) ($log['ticket_id'] ?? '');
            $ticketCode = strtoupper(trim((string) ($log['ticket_code'] ?? '')));
            $userId = (string) ($log['user_id'] ?? '');
            $existingParticipant = is_array($log['participant'] ?? null)
                ? $log['participant']
                : null;

            $ticket = $ticketsById[$ticketId] ?? ($ticketCode !== '' ? ($ticketsByCode[$ticketCode] ?? null) : null);
            $user = $usersById[$userId] ?? null;

            if (! is_array($user) && is_array($ticket)) {
                $ticketUserId = (string) ($ticket['user_id'] ?? '');
                $user = $ticketUserId !== '' ? ($usersById[$ticketUserId] ?? null) : null;
            }

            $entryCodeDisplay = is_array($ticket)
                ? trim((string) ($ticket['entry_code_display'] ?? ($log['entry_code_display'] ?? '')))
                : trim((string) ($log['entry_code_display'] ?? ''));

            if ($entryCodeDisplay === '' && is_array($existingParticipant)) {
                $entryCodeDisplay = trim((string) ($existingParticipant['entry_code_display'] ?? ''));
            }

            $log['entry_code_display'] = $entryCodeDisplay;
            $log['participant'] = $existingParticipant ?? (is_array($ticket) && is_array($user)
                ? $this->attendanceParticipantSummary($user, $ticket)
                : null);

            return $log;
        }, $history);
    }

    private function attendanceParticipantSummary(array $user, array $ticket): array
    {
        $fullName = trim((string) ($user['full_name'] ?? ''));
        $country = strtoupper(trim((string) ($user['country'] ?? '')));

        return [
            'name' => $fullName,
            'full_name' => $fullName,
            'email' => trim((string) ($user['email'] ?? '')),
            'phone_number' => $this->participantPhoneNumber($user),
            'country' => $country,
            'country_label' => $this->analytics->countryLabel($country),
            'ticket_code' => (string) ($ticket['ticket_code'] ?? ''),
            'entry_code_display' => (string) ($ticket['entry_code_display'] ?? ''),
        ];
    }

    private function participantPhoneNumber(array $user): string
    {
        $phoneNumber = trim((string) ($user['phone_number'] ?? ''));
        if ($phoneNumber !== '') {
            return $phoneNumber;
        }

        $countryCode = trim((string) ($user['phone_country_code'] ?? ''));
        $nationalNumber = preg_replace('/\s+/', '', trim((string) ($user['phone_national_number'] ?? ''))) ?? '';

        return trim($countryCode.$nationalNumber);
    }
}
