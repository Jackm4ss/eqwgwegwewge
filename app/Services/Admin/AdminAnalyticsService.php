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

    private const TRAFFIC_SOURCE_LABELS = [
        'instagram' => 'Instagram',
        'whatsapp' => 'WhatsApp',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'threads' => 'Threads',
        'x' => 'X',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'google' => 'Google',
        'website' => 'Website',
        'direct' => 'Direct',
        'other' => 'Other',
    ];

    public function buildDashboard(
        array $users,
        array $scanLogs,
        int $days = 7,
        ?CarbonImmutable $referenceDate = null,
        array $filters = [],
    ): array {
        $referenceDate = ($referenceDate ?? CarbonImmutable::now($this->eventTimezone()))
            ->setTimezone($this->eventTimezone());
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

            if ($result === 'success') {
                $rangeStats['successful_scans']++;

                if ($uniqueKey !== '') {
                    $rangeUniqueVisitors[$uniqueKey] = true;
                }
            } elseif ($result === 'duplicate') {
                $rangeStats['duplicate_scans']++;
            } else {
                $rangeStats['invalid_scans']++;
            }

            if ($result === 'success' && isset($chartBuckets[$scanDate]) && $uniqueKey !== '') {
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

            $row = $this->decorateTrafficAttribution(array_merge($user, [
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
            ]));

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
        $successfulScanLogsByUser = [];

        foreach ($scanLogs as $scanLog) {
            if (strtolower((string) ($scanLog['result'] ?? 'success')) !== 'success') {
                continue;
            }

            $userId = (string) ($scanLog['user_id'] ?? '');

            if ($userId === '') {
                continue;
            }

            $successfulScanLogsByUser[$userId][] = $scanLog;
        }

        return array_map(function (array $row) use ($eventEnd, $eventStart, $eventTotalDays, $successfulScanLogsByUser) {
            $userId = (string) ($row['user_id'] ?? '');
            $attendanceDaysWithinEvent = [];
            $attendanceDaysFallback = [];

            foreach ($successfulScanLogsByUser[$userId] ?? [] as $scanLog) {
                if (! $this->scanLogMatchesAttendanceRow($scanLog, $row)) {
                    continue;
                }

                $hasResolvableDate = trim((string) ($scanLog['scan_date'] ?? '')) !== ''
                    || trim((string) ($scanLog['scanned_at'] ?? '')) !== '';

                if (! $hasResolvableDate) {
                    continue;
                }

                $scanDate = $this->resolveScanDate($scanLog);

                if ($scanDate === '') {
                    continue;
                }

                $attendanceDaysFallback[$scanDate] = true;

                if ($scanDate >= $eventStart && $scanDate <= $eventEnd) {
                    $attendanceDaysWithinEvent[$scanDate] = true;
                }
            }

            $attendanceDays = array_keys(
                $attendanceDaysWithinEvent !== []
                    ? $attendanceDaysWithinEvent
                    : $attendanceDaysFallback
            );
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

    public function trafficSourceLabel(mixed $source): string
    {
        $normalizedSource = $this->normalizeTrafficSource($source, allowEmpty: true);

        if ($normalizedSource === '') {
            return 'Not Captured';
        }

        return self::TRAFFIC_SOURCE_LABELS[$normalizedSource] ?? $this->humanizeToken($normalizedSource, 'Other');
    }

    public function decorateTrafficAttribution(array $row): array
    {
        $trafficSource = $this->normalizeTrafficSource($row['traffic_source'] ?? null, allowEmpty: true);
        $trafficSourceDetail = trim((string) ($row['traffic_source_detail'] ?? ''));
        $trafficMedium = $this->normalizeTrafficToken($row['traffic_medium'] ?? null);
        $trafficCampaign = trim((string) ($row['traffic_campaign'] ?? ''));
        $trafficReferrerHost = strtolower(trim((string) ($row['traffic_referrer_host'] ?? '')));
        $trafficLandingPath = trim((string) ($row['traffic_landing_path'] ?? ''));
        $trafficCapturedAt = trim((string) ($row['traffic_captured_at'] ?? ''));

        $row['traffic_source'] = $trafficSource;
        $row['traffic_source_detail'] = $trafficSourceDetail;
        $row['traffic_medium'] = $trafficMedium;
        $row['traffic_medium_label'] = $this->trafficMediumLabel($trafficMedium);
        $row['traffic_campaign'] = $trafficCampaign;
        $row['traffic_referrer_host'] = $trafficReferrerHost;
        $row['traffic_landing_path'] = $trafficLandingPath;
        $row['traffic_captured_at'] = $trafficCapturedAt;
        $row['traffic_source_label'] = $this->trafficSourceLabel($trafficSource);
        $row['traffic_source_caption'] = $this->resolveTrafficSourceCaption(
            $trafficSource,
            $trafficSourceDetail,
            $trafficMedium,
            $trafficCampaign,
            $trafficReferrerHost,
            $trafficLandingPath,
        );

        return $row;
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

            if ($result === 'success' && $attendanceKey !== '') {
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
                'unique_success' => [],
            ];

            $scannerActivity[$scannerKey]['total_scans']++;

            if ($result === 'success' && $attendanceKey !== '') {
                $scannerActivity[$scannerKey]['unique_success'][$attendanceKey] = true;
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

        $scannerActivityRows = array_values(array_map(function (array $scanner): array {
            $scanner['successful_scans'] = count($scanner['unique_success']);
            unset($scanner['unique_success']);

            return $scanner;
        }, $scannerActivity));
        usort($scannerActivityRows, function (array $left, array $right): int {
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
            if (strtolower((string) ($scanLog['result'] ?? '')) !== 'success') {
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
                'total_scans' => count($history),
                'total_visitors' => count($uniqueAttendance),
                'attendance_statistics' => $attendance['daily_attendance'],
            ],
            'overall' => [
                'total_participants' => count($userRows),
                'total_attendance' => count($uniqueAttendance),
                'visitor_statistics' => array_map(
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
            'daily-report' => $this->buildReports($users, $tickets, $scanLogs, $filters)['daily']['attendance_statistics'],
            'overall-report' => $this->buildReports($users, $tickets, $scanLogs, $filters)['overall']['visitor_statistics'],
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
        $scannerPost = trim((string) ($filters['scanner_post'] ?? ''));

        return array_values(array_filter($scanLogs, function (array $scanLog) use ($query, $fromDate, $toDate, $scannerPost) {
            $scanDate = $this->resolveScanDate($scanLog);

            if ($fromDate !== null && $scanDate < $fromDate) {
                return false;
            }

            if ($toDate !== null && $scanDate > $toDate) {
                return false;
            }

            if ($scannerPost !== '' && (string) ($scanLog['scanner_name'] ?? '') !== $scannerPost) {
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
        $scannedAt = trim((string) ($scanLog['scanned_at'] ?? ''));
        if ($scannedAt !== '') {
            $parsed = $this->safeParseDate($scannedAt);

            if ($parsed !== null) {
                return $parsed->setTimezone($this->eventTimezone())->toDateString();
            }
        }

        $scanDate = trim((string) ($scanLog['scan_date'] ?? ''));
        if ($scanDate !== '') {
            return $scanDate;
        }

        $parsed = $this->safeParseDate($scannedAt);

        return $parsed?->setTimezone($this->eventTimezone())->toDateString()
            ?? CarbonImmutable::now($this->eventTimezone())->toDateString();
    }

    private function resolveAttendanceKey(array $scanLog): string
    {
        return (string) ($scanLog['user_id'] ?? $scanLog['ticket_id'] ?? $scanLog['ticket_code'] ?? $scanLog['scan_id'] ?? '');
    }

    private function scanLogMatchesAttendanceRow(array $scanLog, array $row): bool
    {
        $rowUserId = (string) ($row['user_id'] ?? '');
        $rowTicketId = (string) ($row['ticket_id'] ?? '');
        $rowTicketCode = (string) ($row['ticket_code'] ?? '');

        $scanUserId = (string) ($scanLog['user_id'] ?? '');
        $scanTicketId = (string) ($scanLog['ticket_id'] ?? '');
        $scanTicketCode = (string) ($scanLog['ticket_code'] ?? '');

        if ($rowUserId === '' || $scanUserId !== $rowUserId) {
            return false;
        }

        if ($rowTicketId !== '' && $scanTicketId !== '') {
            return $rowTicketId === $scanTicketId;
        }

        if ($rowTicketCode !== '' && $scanTicketCode !== '') {
            return $rowTicketCode === $scanTicketCode;
        }

        return $scanTicketId === '' && $scanTicketCode === '';
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
            (string) ($row['traffic_source_label'] ?? ''),
            (string) ($row['traffic_source_detail'] ?? ''),
            (string) ($row['traffic_campaign'] ?? ''),
            (string) ($row['traffic_medium_label'] ?? ''),
            (string) ($row['traffic_referrer_host'] ?? ''),
        ])));
    }

    private function normalizeTrafficSource(mixed $value, bool $allowEmpty = false): string
    {
        $normalized = $this->normalizeTrafficToken($value);

        if ($normalized === '') {
            return $allowEmpty ? '' : 'other';
        }

        return match (true) {
            $normalized === 'ig',
            $normalized === 'insta',
            str_contains($normalized, 'instagram'),
            str_contains($normalized, 'ig-story') => 'instagram',
            $normalized === 'wa',
            str_contains($normalized, 'whatsapp'),
            str_contains($normalized, 'wa-broadcast') => 'whatsapp',
            $normalized === 'fb',
            str_contains($normalized, 'facebook') => 'facebook',
            $normalized === 'tt',
            str_contains($normalized, 'tiktok') => 'tiktok',
            $normalized === 'thread',
            str_contains($normalized, 'threads') => 'threads',
            $normalized === 'twitter',
            $normalized === 'tweet',
            $normalized === 'x',
            str_contains($normalized, 'twitter') => 'x',
            str_contains($normalized, 'linkedin') => 'linkedin',
            str_contains($normalized, 'youtube') => 'youtube',
            str_contains($normalized, 'google') => 'google',
            $normalized === 'web',
            $normalized === 'website',
            $normalized === 'site',
            $normalized === 'homepage',
            $normalized === 'landing-page' => 'website',
            default => $normalized,
        };
    }

    private function normalizeTrafficToken(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';

        return trim($normalized, '-');
    }

    private function humanizeToken(string $value, string $fallback = '-'): string
    {
        if ($value === '') {
            return $fallback;
        }

        return ucwords(str_replace(['-', '_'], ' ', $value));
    }

    private function trafficMediumLabel(string $value): string
    {
        return match ($value) {
            'social' => 'Social Media',
            'search' => 'Search',
            'referral' => 'Website Referral',
            'website' => 'Website',
            'direct' => 'Direct',
            'other' => 'Other',
            default => $this->humanizeToken($value, '-'),
        };
    }

    private function resolveTrafficSourceCaption(
        string $trafficSource,
        string $trafficSourceDetail,
        string $trafficMedium,
        string $trafficCampaign,
        string $trafficReferrerHost,
        string $trafficLandingPath,
    ): string {
        if ($trafficCampaign !== '') {
            return 'Promo Link: '.$trafficCampaign;
        }

        if ($trafficSourceDetail !== '' && $trafficSourceDetail !== 'direct' && $trafficSourceDetail !== $trafficSource) {
            return 'Source Detail: '.$trafficSourceDetail;
        }

        if ($trafficReferrerHost !== '') {
            return 'Referred By: '.$trafficReferrerHost;
        }

        if ($trafficMedium !== '') {
            return 'Entry Method: '.$this->trafficMediumLabel($trafficMedium);
        }

        if ($trafficLandingPath !== '') {
            return 'First Page: '.$trafficLandingPath;
        }

        if ($trafficSource === 'direct') {
            return 'Opened the link directly';
        }

        return $trafficSource === '' ? 'Registrant source has not been captured yet' : 'Registrant source captured';
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

    private function eventWindow(): array
    {
        $timezone = $this->eventTimezone();
        $eventStartDate = CarbonImmutable::parse(
            (string) config('admin.event.start_date', '2026-04-09'),
            $timezone
        )->startOfDay();
        $eventEndDate = CarbonImmutable::parse(
            (string) config('admin.event.end_date', '2026-04-19'),
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
            ? CarbonImmutable::parse($normalizedTo, $this->eventTimezone())->startOfDay()
            : $referenceDate->startOfDay();
        $startDate = $normalizedFrom !== null
            ? CarbonImmutable::parse($normalizedFrom, $this->eventTimezone())->startOfDay()
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

    private function eventTimezone(): string
    {
        return (string) config('admin.event.timezone', config('app.timezone', 'UTC'));
    }
}
