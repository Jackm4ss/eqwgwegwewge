<?php

namespace App\Services\Admin;

use Carbon\CarbonImmutable;

class AdminAnalyticsService
{
    private const COUNTRY_LABEL_FALLBACKS = [
        'AU' => 'Australia',
        'CN' => 'China',
        'DE' => 'Germany',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'ID' => 'Indonesia',
        'IN' => 'India',
        'JP' => 'Japan',
        'KR' => 'South Korea',
        'MY' => 'Malaysia',
        'PH' => 'Philippines',
        'SG' => 'Singapore',
        'TH' => 'Thailand',
        'US' => 'United States',
        'VN' => 'Vietnam',
    ];

    public function buildDashboard(
        array $users,
        array $scanLogs,
        int $days = 7,
        ?CarbonImmutable $referenceDate = null,
        array $filters = [],
    ): array {
        $referenceDate ??= CarbonImmutable::now();
        [$startDate, $endDate, $hasDateFilter] = $this->resolveDashboardDateRange($days, $referenceDate, $filters);
        $startDateString = $startDate->toDateString();
        $endDateString = $endDate->toDateString();
        $rangeDays = (int) max(1, $startDate->diffInDays($endDate) + 1);

        $chartBuckets = [];
        for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
            $dateString = $date->toDateString();
            $chartBuckets[$dateString] = [
                'label' => $date->format('d M'),
                'unique_keys' => [],
            ];
        }

        $rangeUniqueVisitors = [];
        $rangeStats = [
            'total_scans' => 0,
            'successful_scans' => 0,
            'unique_visitors' => 0,
            'duplicate_scans' => 0,
            'invalid_scans' => 0,
        ];

        foreach ($scanLogs as $scanLog) {
            $scanDate = $this->resolveScanDate($scanLog);
            $result = strtolower((string) ($scanLog['result'] ?? 'unknown'));
            $uniqueKey = $this->resolveAttendanceKey($scanLog);

            if ($scanDate < $startDateString || $scanDate > $endDateString) {
                continue;
            }

            $rangeStats['total_scans']++;

            if ($this->isSuccessfulScanResult($result)) {
                $rangeStats['successful_scans']++;

                if ($uniqueKey !== '') {
                    $rangeUniqueVisitors[$uniqueKey] = true;
                }
            } elseif ($result === 'duplicate') {
                $rangeStats['duplicate_scans']++;
            } else {
                $rangeStats['invalid_scans']++;
            }

            if ($this->isSuccessfulScanResult($result) && isset($chartBuckets[$scanDate]) && $uniqueKey !== '') {
                $chartBuckets[$scanDate]['unique_keys'][$uniqueKey] = true;
            }
        }

        $rangeStats['unique_visitors'] = count($rangeUniqueVisitors);

        $totalRegistrations = $hasDateFilter
            ? count(array_filter($users, function (array $user) use ($endDateString, $startDateString): bool {
                $registrationDate = $this->resolveUserRegistrationDate($user);

                return $registrationDate !== null
                    && $registrationDate >= $startDateString
                    && $registrationDate <= $endDateString;
            }))
            : count($users);

        return [
            'total_registrations' => $totalRegistrations,
            'daily_scan_statistics' => $rangeStats,
            'visitor_chart' => [
                'labels' => array_map(
                    fn (array $bucket) => $bucket['label'],
                    array_values($chartBuckets),
                ),
                'series' => array_map(
                    fn (array $bucket) => count($bucket['unique_keys']),
                    array_values($chartBuckets),
                ),
            ],
            'date_range' => [
                'from' => $startDateString,
                'to' => $endDateString,
                'days' => $rangeDays,
                'is_filtered' => $hasDateFilter,
                'label' => $this->formatDateRangeLabel($startDate, $endDate),
                'badge' => $hasDateFilter ? 'Selected Range' : 'Last '.$rangeDays.' Days',
                'registration_badge' => $hasDateFilter ? 'Selected Range' : 'All Time',
            ],
        ];
    }

    public function buildUserRows(array $users, array $tickets): array
    {
        $ticketsById = [];
        $ticketsByUserId = [];

        foreach ($tickets as $ticket) {
            $ticketId = (string) ($ticket['ticket_id'] ?? '');
            $userId = (string) ($ticket['user_id'] ?? '');

            if ($ticketId !== '') {
                $ticketsById[$ticketId] = $ticket;
            }

            if ($userId !== '') {
                $ticketsByUserId[$userId] = $ticket;
            }
        }

        $rows = [];

        foreach ($users as $user) {
            $ticketId = (string) ($user['ticket_id'] ?? '');
            $userId = (string) ($user['user_id'] ?? '');
            $ticket = $ticketsById[$ticketId] ?? $ticketsByUserId[$userId] ?? null;

            $row = array_merge($user, [
                'ticket_code' => (string) ($ticket['ticket_code'] ?? ''),
                'ticket_status' => (string) ($ticket['status'] ?? ''),
                'qr_version' => (string) ($ticket['qr_version'] ?? ''),
                'attendance_status' => $this->normalizeAttendanceStatus($ticket['attendance_status'] ?? null),
                'checked_in_at' => $ticket['checked_in_at'] ?? null,
                'ticket_created_at' => $ticket['created_at'] ?? null,
                'ticket_regenerated_at' => $ticket['regenerated_at'] ?? null,
                'ticket_updated_at' => $ticket['updated_at'] ?? null,
                'has_ticket' => $ticket !== null,
                'country_label' => $this->countryLabel($user['country'] ?? null),
            ]);

            $rows[] = $row;
        }

        usort($rows, function (array $left, array $right): int {
            return strcmp(
                (string) ($right['created_at'] ?? ''),
                (string) ($left['created_at'] ?? ''),
            );
        });

        return $rows;
    }

    public function attachAttendanceProgress(array $rows, array $scanLogs): array
    {
        [$eventStartDate, $eventEndDate, $eventTotalDays] = $this->eventWindow();
        $eventStart = $eventStartDate->toDateString();
        $eventEnd = $eventEndDate->toDateString();
        $attendanceDaysByUser = [];

        foreach ($scanLogs as $scanLog) {
            if (! $this->isSuccessfulScanResult((string) ($scanLog['result'] ?? 'success'))) {
                continue;
            }

            $userId = (string) ($scanLog['user_id'] ?? '');
            $hasResolvableDate = trim((string) ($scanLog['scan_date'] ?? '')) !== ''
                || trim((string) ($scanLog['scanned_at'] ?? '')) !== '';

            if (! $hasResolvableDate) {
                continue;
            }

            $scanDate = $this->resolveScanDate($scanLog);

            if ($userId === '' || $scanDate === '' || $scanDate < $eventStart || $scanDate > $eventEnd) {
                continue;
            }

            $attendanceDaysByUser[$userId][$scanDate] = true;
        }

        return array_map(function (array $row) use ($attendanceDaysByUser, $eventTotalDays) {
            $userId = (string) ($row['user_id'] ?? '');
            $attendanceDays = array_keys($attendanceDaysByUser[$userId] ?? []);
            sort($attendanceDays);
            $attendanceCount = count($attendanceDays);

            $row['attendance_days'] = $attendanceDays;
            $row['attendance_days_count'] = $attendanceCount;
            $row['attendance_total_days'] = $eventTotalDays;
            $row['attendance_progress_percent'] = $eventTotalDays > 0
                ? (int) round(($attendanceCount / $eventTotalDays) * 100)
                : 0;

            return $row;
        }, $rows);
    }

    public function filterUserRows(array $rows, array $filters = []): array
    {
        $query = $this->normalizeText((string) ($filters['q'] ?? ''));
        $country = strtoupper(trim((string) ($filters['country'] ?? '')));
        $verificationStatus = strtolower(trim((string) ($filters['verification_status'] ?? '')));
        $attendanceStatus = $this->normalizeAttendanceStatus($filters['attendance_status'] ?? null, allowEmpty: true);

        return array_values(array_filter($rows, function (array $row) use ($attendanceStatus, $country, $query, $verificationStatus) {
            if ($query !== '' && ! str_contains($this->userSearchHaystack($row), $query)) {
                return false;
            }

            if ($country !== '' && strtoupper((string) ($row['country'] ?? '')) !== $country) {
                return false;
            }

            if ($verificationStatus !== '' && strtolower((string) ($row['verification_status'] ?? '')) !== $verificationStatus) {
                return false;
            }

            if ($attendanceStatus !== '' && $this->normalizeAttendanceStatus($row['attendance_status'] ?? null) !== $attendanceStatus) {
                return false;
            }

            return true;
        }));
    }

    public function buildUserManagementOverview(array $rows): array
    {
        $totalUsers = count($rows);
        $verifiedUsers = 0;
        $checkedInUsers = 0;
        $followUpUsers = 0;
        $countries = [];

        foreach ($rows as $row) {
            $country = strtoupper((string) ($row['country'] ?? ''));
            if ($country !== '') {
                $countries[$country] = true;
            }

            if (strtolower((string) ($row['verification_status'] ?? '')) === 'verified') {
                $verifiedUsers++;
            }

            if ($this->isCheckedInAttendanceStatus($row['attendance_status'] ?? null)) {
                $checkedInUsers++;
            }

            if (
                strtolower((string) ($row['verification_status'] ?? '')) !== 'verified'
                || strtolower((string) ($row['account_status'] ?? '')) !== 'active'
            ) {
                $followUpUsers++;
            }
        }

        return [
            'total_users' => $totalUsers,
            'verified_users' => $verifiedUsers,
            'checked_in_users' => $checkedInUsers,
            'follow_up_users' => $followUpUsers,
            'countries_count' => count($countries),
            'verified_rate' => $this->calculateRate($verifiedUsers, $totalUsers),
            'checked_in_rate' => $this->calculateRate($checkedInUsers, $totalUsers),
            'follow_up_rate' => $this->calculateRate($followUpUsers, $totalUsers),
        ];
    }

    public function buildUserFilterOptions(array $rows): array
    {
        $countries = [];
        $verificationCounts = [
            'verified' => 0,
            'unverified' => 0,
        ];
        $attendanceCounts = [
            'checked_in' => 0,
            'not_checked_in' => 0,
        ];

        foreach ($rows as $row) {
            $country = strtoupper((string) ($row['country'] ?? ''));
            if ($country !== '') {
                $countries[$country] = ($countries[$country] ?? 0) + 1;
            }

            $verificationStatus = strtolower((string) ($row['verification_status'] ?? 'unverified'));
            if (isset($verificationCounts[$verificationStatus])) {
                $verificationCounts[$verificationStatus]++;
            }

            $attendanceStatus = $this->normalizeAttendanceStatus($row['attendance_status'] ?? null);
            if ($this->isCheckedInAttendanceStatus($attendanceStatus)) {
                $attendanceCounts['checked_in']++;
            } else {
                $attendanceCounts['not_checked_in']++;
            }
        }

        $countryOptions = array_map(
            fn (string $country) => [
                'value' => $country,
                'label' => $this->countryLabel($country),
                'count' => $countries[$country],
            ],
            array_keys($countries),
        );

        usort($countryOptions, fn (array $left, array $right) => strcmp($left['label'], $right['label']));

        return [
            'countries' => $countryOptions,
            'verification_statuses' => [
                [
                    'value' => 'verified',
                    'label' => 'Verified',
                    'count' => $verificationCounts['verified'],
                ],
                [
                    'value' => 'unverified',
                    'label' => 'Unverified',
                    'count' => $verificationCounts['unverified'],
                ],
            ],
            'attendance_statuses' => [
                [
                    'value' => 'checked_in',
                    'label' => 'Checked In',
                    'count' => $attendanceCounts['checked_in'],
                ],
                [
                    'value' => 'not_checked_in',
                    'label' => 'Not Checked In',
                    'count' => $attendanceCounts['not_checked_in'],
                ],
            ],
        ];
    }

    public function countryLabel(mixed $countryCode): string
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        if ($countryCode === '') {
            return '-';
        }

        if (class_exists(\Locale::class)) {
            $label = \Locale::getDisplayRegion('und_'.$countryCode, 'en');

            if (is_string($label) && trim($label) !== '' && strtoupper($label) !== $countryCode) {
                return $label;
            }
        }

        return self::COUNTRY_LABEL_FALLBACKS[$countryCode] ?? $countryCode;
    }

    public function supportedCountryCodes(): array
    {
        return array_keys(self::COUNTRY_LABEL_FALLBACKS);
    }

    public function buildAttendanceOverview(array $scanLogs, array $filters = []): array
    {
        $filtered = $this->filterScanLogs($scanLogs, $filters);

        usort($filtered, function (array $left, array $right): int {
            return strcmp(
                (string) ($right['scanned_at'] ?? ''),
                (string) ($left['scanned_at'] ?? ''),
            );
        });

        $byDay = [];
        $scannerActivity = [];

        foreach ($filtered as $scanLog) {
            $scanDate = $this->resolveScanDate($scanLog);
            $result = strtolower((string) ($scanLog['result'] ?? 'unknown'));
            $attendanceKey = $this->resolveAttendanceKey($scanLog);
            $scannerKey = (string) ($scanLog['scanner_id'] ?? $scanLog['scanner_name'] ?? 'unknown-scanner');

            $byDay[$scanDate] ??= [
                'scan_date' => $scanDate,
                'total_scans' => 0,
                'duplicate_scans' => 0,
                'invalid_scans' => 0,
                'unique_success' => [],
            ];

            $byDay[$scanDate]['total_scans']++;

            if ($this->isSuccessfulScanResult($result) && $attendanceKey !== '') {
                $byDay[$scanDate]['unique_success'][$attendanceKey] = true;
            } elseif ($result === 'duplicate') {
                $byDay[$scanDate]['duplicate_scans']++;
            } else {
                $byDay[$scanDate]['invalid_scans']++;
            }

            $scannerActivity[$scannerKey] ??= [
                'scanner_id' => (string) ($scanLog['scanner_id'] ?? ''),
                'scanner_name' => (string) ($scanLog['scanner_name'] ?? 'Unknown Scanner'),
                'scanner_role' => (string) ($scanLog['scanner_role'] ?? ''),
                'total_scans' => 0,
                'successful_scans' => 0,
                'duplicate_scans' => 0,
                'invalid_scans' => 0,
                'last_scanned_at' => null,
            ];

            $scannerActivity[$scannerKey]['total_scans']++;

            if ($this->isSuccessfulScanResult($result)) {
                $scannerActivity[$scannerKey]['successful_scans']++;
            } elseif ($result === 'duplicate') {
                $scannerActivity[$scannerKey]['duplicate_scans']++;
            } else {
                $scannerActivity[$scannerKey]['invalid_scans']++;
            }

            $scannedAt = $scanLog['scanned_at'] ?? null;
            if ($scannedAt !== null && (string) $scannedAt > (string) ($scannerActivity[$scannerKey]['last_scanned_at'] ?? '')) {
                $scannerActivity[$scannerKey]['last_scanned_at'] = $scannedAt;
            }
        }

        $dailyAttendance = array_values(array_map(function (array $day) {
            $day['successful_attendance'] = count($day['unique_success']);
            unset($day['unique_success']);

            return $day;
        }, $byDay));

        usort($dailyAttendance, fn (array $left, array $right) => strcmp($right['scan_date'], $left['scan_date']));

        $scannerActivityRows = array_values($scannerActivity);
        usort($scannerActivityRows, fn (array $left, array $right) => $right['total_scans'] <=> $left['total_scans']);

        return [
            'history' => $filtered,
            'daily_attendance' => $dailyAttendance,
            'scanner_activity' => $scannerActivityRows,
        ];
    }

    public function buildActivityLogRows(array $activityLogs, array $filters = []): array
    {
        $query = $this->normalizeText((string) ($filters['q'] ?? ''));
        $fromDate = $this->normalizeDateInput($filters['from'] ?? null);
        $toDate = $this->normalizeDateInput($filters['to'] ?? null);

        $rows = array_values(array_filter($activityLogs, function (array $log) use ($query, $fromDate, $toDate) {
            $createdAt = $this->safeParseDate((string) ($log['created_at'] ?? ''));

            if ($fromDate !== null && $createdAt?->toDateString() < $fromDate) {
                return false;
            }

            if ($toDate !== null && $createdAt?->toDateString() > $toDate) {
                return false;
            }

            if ($query === '') {
                return true;
            }

            $haystack = $this->normalizeText(implode(' ', array_filter([
                (string) ($log['admin_email'] ?? ''),
                (string) ($log['action_type'] ?? ''),
                (string) ($log['target_type'] ?? ''),
                (string) ($log['target_id'] ?? ''),
                json_encode($log['metadata'] ?? []),
            ])));

            return str_contains($haystack, $query);
        }));

        usort($rows, fn (array $left, array $right) => strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? '')));

        return $rows;
    }

    public function buildReports(
        array $users,
        array $tickets,
        array $scanLogs,
        array $filters = [],
    ): array {
        $userRows = $this->filterUserRows(
            $this->buildUserRows($users, $tickets),
            $filters,
        );
        $attendance = $this->buildAttendanceOverview($scanLogs, $filters);
        $history = $attendance['history'];

        $uniqueAttendance = [];
        $dailyVisitTrend = [];

        foreach ($history as $scanLog) {
            if (! $this->isSuccessfulScanResult((string) ($scanLog['result'] ?? ''))) {
                continue;
            }

            $key = $this->resolveAttendanceKey($scanLog);
            if ($key === '') {
                continue;
            }

            $uniqueAttendance[$key] = true;
            $scanDate = $this->resolveScanDate($scanLog);
            $dailyVisitTrend[$scanDate] ??= [];
            $dailyVisitTrend[$scanDate][$key] = true;
        }

        ksort($dailyVisitTrend);

        return [
            'daily' => [
                'jumlah_scan' => count($history),
                'jumlah_pengunjung' => count($uniqueAttendance),
                'statistik_kehadiran' => $attendance['daily_attendance'],
            ],
            'overall' => [
                'total_peserta' => count($userRows),
                'total_attendance' => count($uniqueAttendance),
                'statistik_kunjungan' => array_map(
                    fn (string $date, array $keys) => [
                        'scan_date' => $date,
                        'unique_visitors' => count($keys),
                    ],
                    array_keys($dailyVisitTrend),
                    array_values($dailyVisitTrend),
                ),
            ],
        ];
    }

    public function buildExportDataset(
        string $type,
        array $users,
        array $tickets,
        array $scanLogs,
        array $activityLogs,
        array $filters = [],
    ): array {
        return match ($type) {
            'users' => $this->filterUserRows(
                $this->buildUserRows($users, $tickets),
                $filters,
            ),
            'attendance' => $this->buildAttendanceOverview($scanLogs, $filters)['history'],
            'admin-logs' => $this->buildActivityLogRows($activityLogs, $filters),
            'daily-report' => $this->buildReports($users, $tickets, $scanLogs, $filters)['daily']['statistik_kehadiran'],
            'overall-report' => $this->buildReports($users, $tickets, $scanLogs, $filters)['overall']['statistik_kunjungan'],
            default => [],
        };
    }

    public function paginate(array $items, int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $total = count($items);
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($items, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    private function filterScanLogs(array $scanLogs, array $filters): array
    {
        $query = $this->normalizeText((string) ($filters['q'] ?? ''));
        $fromDate = $this->normalizeDateInput($filters['from'] ?? null);
        $toDate = $this->normalizeDateInput($filters['to'] ?? null);

        return array_values(array_filter($scanLogs, function (array $scanLog) use ($query, $fromDate, $toDate) {
            $scanDate = $this->resolveScanDate($scanLog);

            if ($fromDate !== null && $scanDate < $fromDate) {
                return false;
            }

            if ($toDate !== null && $scanDate > $toDate) {
                return false;
            }

            if ($query === '') {
                return true;
            }

            $haystack = $this->normalizeText(implode(' ', array_filter([
                (string) ($scanLog['ticket_code'] ?? ''),
                (string) ($scanLog['user_id'] ?? ''),
                (string) ($scanLog['scanner_id'] ?? ''),
                (string) ($scanLog['scanner_name'] ?? ''),
                (string) ($scanLog['result'] ?? ''),
            ])));

            return str_contains($haystack, $query);
        }));
    }

    private function resolveScanDate(array $scanLog): string
    {
        $scanDate = trim((string) ($scanLog['scan_date'] ?? ''));
        if ($scanDate !== '') {
            return $scanDate;
        }

        $parsed = $this->safeParseDate((string) ($scanLog['scanned_at'] ?? ''));

        return $parsed?->toDateString() ?? CarbonImmutable::now()->toDateString();
    }

    private function resolveAttendanceKey(array $scanLog): string
    {
        return (string) ($scanLog['user_id'] ?? $scanLog['ticket_id'] ?? $scanLog['ticket_code'] ?? $scanLog['scan_id'] ?? '');
    }

    private function userSearchHaystack(array $row): string
    {
        return $this->normalizeText(implode(' ', array_filter([
            (string) ($row['full_name'] ?? ''),
            (string) ($row['email'] ?? ''),
            (string) ($row['identity_number'] ?? ''),
            (string) ($row['ticket_code'] ?? ''),
            (string) ($row['country'] ?? ''),
            (string) ($row['country_label'] ?? ''),
            (string) ($row['user_id'] ?? ''),
        ])));
    }

    private function calculateRate(int $value, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($value / $total) * 100);
    }

    private function normalizeAttendanceStatus(mixed $value, bool $allowEmpty = false): string
    {
        $status = strtolower(trim((string) $value));

        if ($status === '') {
            return $allowEmpty ? '' : 'not_checked_in';
        }

        return $status;
    }

    private function isCheckedInAttendanceStatus(mixed $value): bool
    {
        return $this->normalizeAttendanceStatus($value) === 'checked_in';
    }

    private function normalizeText(string $value): string
    {
        return str_replace(["\r", "\n"], ' ', mb_strtolower(trim($value)));
    }

    private function isSuccessfulScanResult(string $result): bool
    {
        return in_array(strtolower(trim($result)), ['success', 'valid'], true);
    }

    private function eventWindow(): array
    {
        $timezone = config('app.timezone');
        $eventStartDate = CarbonImmutable::parse(
            (string) config('event.start_date', config('admin.event.start_date', '2026-04-09')),
            $timezone
        )->startOfDay();
        $eventEndDate = CarbonImmutable::parse(
            (string) config('event.end_date', config('admin.event.end_date', '2026-04-19')),
            $timezone
        )->startOfDay();

        if ($eventEndDate->lt($eventStartDate)) {
            [$eventStartDate, $eventEndDate] = [$eventEndDate, $eventStartDate];
        }

        return [
            $eventStartDate,
            $eventEndDate,
            (int) max(1, $eventStartDate->diffInDays($eventEndDate) + 1),
        ];
    }

    private function normalizeDateInput(mixed $value): ?string
    {
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return null;
        }

        $date = $this->safeParseDate($stringValue);

        return $date?->toDateString();
    }

    private function resolveDashboardDateRange(
        int $days,
        CarbonImmutable $referenceDate,
        array $filters = [],
    ): array {
        $days = max(1, $days);
        $normalizedFrom = $this->normalizeDateInput($filters['from'] ?? null);
        $normalizedTo = $this->normalizeDateInput($filters['to'] ?? null);
        $hasDateFilter = $normalizedFrom !== null || $normalizedTo !== null;

        $endDate = $normalizedTo !== null
            ? CarbonImmutable::parse($normalizedTo)->startOfDay()
            : $referenceDate->startOfDay();
        $startDate = $normalizedFrom !== null
            ? CarbonImmutable::parse($normalizedFrom)->startOfDay()
            : $endDate->subDays($days - 1);

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate, $hasDateFilter];
    }

    private function resolveUserRegistrationDate(array $user): ?string
    {
        foreach (['created_at', 'registered_at', 'updated_at'] as $field) {
            $date = $this->safeParseDate((string) ($user[$field] ?? ''));

            if ($date !== null) {
                return $date->toDateString();
            }
        }

        return null;
    }

    private function formatDateRangeLabel(CarbonImmutable $startDate, CarbonImmutable $endDate): string
    {
        if ($startDate->isSameDay($endDate)) {
            return $startDate->format('d M Y');
        }

        return $startDate->format('d M Y').' - '.$endDate->format('d M Y');
    }

    private function safeParseDate(string $value): ?CarbonImmutable
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
