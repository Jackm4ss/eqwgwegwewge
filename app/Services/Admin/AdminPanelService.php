<?php

namespace App\Services\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class AdminPanelService
{
    private const USER_MANAGEMENT_META_CACHE_KEY = 'admin:user-management:meta:v2';

    public function __construct(
        private readonly AdminFirestoreRepository $repository,
        private readonly AdminAnalyticsService $analytics,
        private readonly AdminParticipantNotificationService $participantNotifications,
    ) {}

    public function firestoreAvailable(): bool
    {
        return $this->repository->available();
    }

    public function normalizedScanLogFilters(array $filters = []): array
    {
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));

        if ($from === '' && $to === '') {
            $today = now()->toDateString();
            $filters['from'] = $today;
            $filters['to'] = $today;
        }

        return $filters;
    }

    public function dashboardData(array $filters = []): array
    {
        $filters = $this->normalizedScanLogFilters($filters);

        return $this->analytics->buildDashboard(
            $this->repository->allUsers(),
            $this->repository->queryScanLogs($filters),
            (int) config('admin.dashboard_days', 7),
            filters: $filters,
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
            } catch (RuntimeException) {
                // Fall back to the legacy in-memory implementation if the
                // Firestore query path is not available yet (for example,
                // missing composite indexes in a new project).
            }
        }

        return $this->legacyUserManagementPage($filters);
    }

    public function flushUserManagementCache(): void
    {
        Cache::forget(self::USER_MANAGEMENT_META_CACHE_KEY);
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
        $this->participantNotifications->sendProfileUpdated(
            $previousUser ?? [],
            $user,
            is_array($user['ticket'] ?? null) ? $user['ticket'] : null,
        );
        $this->flushUserManagementCache();

        return $user;
    }

    public function deleteUserByAdmin(string $userId): array
    {
        $result = $this->repository->deleteUserByAdmin($userId);
        $this->participantNotifications->sendParticipantDeleted(
            $result['user'] ?? [],
            is_array($result['ticket'] ?? null) ? $result['ticket'] : null,
        );
        $this->flushUserManagementCache();

        return $result;
    }

    public function resetQrCode(string $userId): array
    {
        $result = $this->repository->resetQrCode($userId);
        $this->flushUserManagementCache();

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
        $this->flushUserManagementCache();

        return $result;
    }

    public function attendanceData(array $filters = []): array
    {
        $filters = $this->normalizedScanLogFilters($filters);

        $overview = $this->analytics->buildAttendanceOverview(
            $this->repository->queryScanLogs($filters),
            $filters,
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
        ];
    }

    public function activityLogs(array $filters = []): LengthAwarePaginator
    {
        $rows = $this->analytics->buildActivityLogRows(
            $this->repository->allAdminActivityLogs(),
            $filters,
        );

        return $this->makePaginator(
            $rows,
            (int) ($filters['page'] ?? 1),
            (int) ($filters['per_page'] ?? config('admin.per_page', 10)),
            request()->url(),
            request()->query(),
        );
    }

    public function reports(array $filters = []): array
    {
        $filters = $this->normalizedScanLogFilters($filters);

        return $this->analytics->buildReports(
            $this->repository->allUsers(),
            $this->repository->allTickets(),
            $this->repository->queryScanLogs($filters),
            $filters,
        );
    }

    public function exportRows(string $type, array $filters = []): array
    {
        if (in_array($type, ['attendance', 'daily-report', 'overall-report'], true)) {
            $filters = $this->normalizedScanLogFilters($filters);
        }

        return $this->analytics->buildExportDataset(
            $type,
            $this->repository->allUsers(),
            $this->repository->allTickets(),
            in_array($type, ['attendance', 'daily-report', 'overall-report'], true)
                ? $this->repository->queryScanLogs($filters)
                : [],
            $this->repository->allAdminActivityLogs(),
            $filters,
        );
    }

    public function backupSnapshot(): array
    {
        return $this->repository->snapshotCollections();
    }

    private function legacyUserManagementPage(array $filters = []): array
    {
        $allRows = $this->analytics->buildUserRows(
            $this->repository->allUsers(),
            $this->repository->allTickets(),
        );
        $filteredRows = $this->analytics->filterUserRows($allRows, $filters);
        $filteredRows = $this->analytics->attachAttendanceProgress(
            $filteredRows,
            $this->repository->findScanLogsByUserIds(array_map(
                fn (array $row): string => (string) ($row['user_id'] ?? ''),
                $filteredRows,
            )),
        );

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
            'overview' => $meta['overview'],
            'filter_options' => $meta['filter_options'],
        ];
    }

    private function cachedUserManagementMeta(): array
    {
        return Cache::remember(
            self::USER_MANAGEMENT_META_CACHE_KEY,
            now()->addMinutes(5),
            fn (): array => $this->buildUserManagementMetaSnapshot(),
        );
    }

    private function buildUserManagementMetaSnapshot(): array
    {
        $totalUsers = $this->repository->countUsers();
        $verifiedUsers = $this->repository->countUsers(['verification_status' => 'verified']);
        $activeVerifiedUsers = $this->repository->countUsers([
            'verification_status' => 'verified',
            'account_status' => 'active',
        ]);
        $checkedInUsers = $this->repository->countTickets([
            'attendance_status' => 'checked_in',
        ]);

        $countryOptions = [];

        foreach ($this->analytics->supportedCountryCodes() as $countryCode) {
            $count = $this->repository->countUsers(['country' => $countryCode]);

            if ($count <= 0) {
                continue;
            }

            $countryOptions[] = [
                'value' => $countryCode,
                'label' => $this->analytics->countryLabel($countryCode),
                'count' => $count,
            ];
        }

        usort($countryOptions, fn (array $left, array $right): int => strcmp($left['label'], $right['label']));

        return [
            'overview' => [
                'total_users' => $totalUsers,
                'verified_users' => $verifiedUsers,
                'checked_in_users' => $checkedInUsers,
                'follow_up_users' => max(0, $totalUsers - $activeVerifiedUsers),
                'countries_count' => count($countryOptions),
                'verified_rate' => $this->calculateRate($verifiedUsers, $totalUsers),
                'checked_in_rate' => $this->calculateRate($checkedInUsers, $totalUsers),
                'follow_up_rate' => $this->calculateRate(max(0, $totalUsers - $activeVerifiedUsers), $totalUsers),
            ],
            'filter_options' => [
                'countries' => $countryOptions,
                'verification_statuses' => [
                    [
                        'value' => 'verified',
                        'label' => 'Verified',
                        'count' => $verifiedUsers,
                    ],
                    [
                        'value' => 'unverified',
                        'label' => 'Unverified',
                        'count' => max(0, $totalUsers - $verifiedUsers),
                    ],
                ],
                'attendance_statuses' => [
                    [
                        'value' => 'checked_in',
                        'label' => 'Checked In',
                        'count' => $checkedInUsers,
                    ],
                    [
                        'value' => 'not_checked_in',
                        'label' => 'Not Checked In',
                        'count' => max(0, $totalUsers - $checkedInUsers),
                    ],
                ],
            ],
        ];
    }

    private function shouldUseOptimizedUserManagementQuery(array $filters): bool
    {
        return trim((string) ($filters['q'] ?? '')) === ''
            && trim((string) ($filters['attendance_status'] ?? '')) === '';
    }

    private function hydrateUser(array $user, ?array $ticketOverride = null): array
    {
        $ticket = $ticketOverride;

        if ($ticket === null) {
            $ticketId = (string) ($user['ticket_id'] ?? '');
            $ticket = $ticketId !== '' ? $this->repository->findTicket($ticketId) : null;
        }

        return array_merge($user, [
            'ticket' => $ticket,
            'country_label' => $this->analytics->countryLabel($user['country'] ?? null),
        ]);
    }

    private function calculateRate(int $portion, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($portion / $total) * 100, 2);
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
}
