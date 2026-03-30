<?php

namespace App\Services\Admin;

use App\Services\Scanner\ScannerGateService;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class AdminPanelService
{
    public const USER_MANAGEMENT_META_CACHE_KEY = 'admin:user-management:meta:v2';
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
        return $this->analytics->buildDashboard(
            $this->repository->allUsers(),
            $this->repository->allScanLogs(),
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
        $filters = $this->normalizeAttendanceFilters($filters);

        if ($this->shouldUseOptimizedAttendanceQuery($filters)) {
            try {
                return $this->optimizedAttendanceData($filters);
            } catch (RuntimeException) {
                // Fall back to the legacy in-memory implementation if Firestore
                // indexes are not ready yet in a new project.
            }
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
            $this->repository->allUsers(),
            $this->repository->allTickets(),
            $this->repository->allScanLogs(),
            $filters,
        );
    }

    public function exportRows(string $type, array $filters = []): array
    {
        return match ($type) {
            'users' => $this->analytics->buildExportDataset(
                $type,
                $this->repository->allUsers(),
                $this->repository->allTickets(),
                [],
                [],
                $filters,
            ),
            'attendance' => $this->analytics->buildExportDataset(
                $type,
                [],
                [],
                $this->repository->allScanLogs(),
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
                $this->repository->allUsers(),
                $this->repository->allTickets(),
                $this->repository->allScanLogs(),
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
        $pageResult = $this->repository->paginateScanLogs($filters, $page, $perPage);
        $historyItems = $this->hydrateAttendanceHistoryPage($pageResult['items'] ?? []);
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
            'daily_attendance' => $this->buildAttendanceDailySummary($filters),
            'scanner_activity' => $this->buildAttendanceScannerActivity($filters, $historyItems),
            'scan_post_options' => $this->buildAttendanceScanPostOptions(
                $historyItems,
                [$filters['scanner_post'] ?? null],
            ),
        ];
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

    private function shouldUseOptimizedActivityLogQuery(array $filters): bool
    {
        return trim((string) ($filters['q'] ?? '')) === '';
    }

    private function shouldUseOptimizedAttendanceQuery(array $filters): bool
    {
        return trim((string) ($filters['q'] ?? '')) === '';
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

    private function calculateRate(int $portion, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($portion / $total) * 100, 2);
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
        $ticketIds = array_values(array_unique(array_filter(array_map(
            fn (array $log): string => trim((string) ($log['ticket_id'] ?? '')),
            $history,
        ))));
        $ticketRows = $this->repository->findTicketsByIds($ticketIds);

        $ticketUserIds = array_filter(array_map(
            fn (array $ticket): string => trim((string) ($ticket['user_id'] ?? '')),
            $ticketRows,
        ));
        $logUserIds = array_filter(array_map(
            fn (array $log): string => trim((string) ($log['user_id'] ?? '')),
            $history,
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

            $ticket = $ticketsById[$ticketId] ?? ($ticketCode !== '' ? ($ticketsByCode[$ticketCode] ?? null) : null);
            $user = $usersById[$userId] ?? null;

            if (! is_array($user) && is_array($ticket)) {
                $ticketUserId = (string) ($ticket['user_id'] ?? '');
                $user = $ticketUserId !== '' ? ($usersById[$ticketUserId] ?? null) : null;
            }

            $entryCodeDisplay = is_array($ticket)
                ? trim((string) ($ticket['entry_code_display'] ?? ($log['entry_code_display'] ?? '')))
                : trim((string) ($log['entry_code_display'] ?? ''));

            $log['entry_code_display'] = $entryCodeDisplay;
            $log['participant'] = is_array($ticket) && is_array($user)
                ? $this->attendanceParticipantSummary($user, $ticket)
                : null;

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
