<?php

namespace App\Services\Staff;

use App\Models\Admin;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class StaffScannerService
{
    private const DASHBOARD_CACHE_TTL_DAYS = 2;
    private const DASHBOARD_REFRESH_LOCK_SECONDS = 180;

    public function __construct(
        private readonly AdminFirestoreRepository $repository,
        private readonly AdminAnalyticsService $analytics,
        private readonly TicketQrCodeService $ticketQrCodeService,
    ) {}

    public function scan(Admin $operator, string $scannerPost, string $payload, ?string $ipAddress = null): array
    {
        $payload = trim($payload);

        if (! preg_match('/^esf1:([^:]+):([^:]+)$/', $payload, $matches)) {
            return $this->invalidScan($operator, $scannerPost, $payload, 'QR payload is invalid.', $ipAddress);
        }

        $ticketCode = strtoupper(trim((string) ($matches[1] ?? '')));
        $signature = trim((string) ($matches[2] ?? ''));
        $expectedSignature = $this->ticketQrCodeService->signTicketCode($ticketCode);

        if ($ticketCode === '' || ! hash_equals($expectedSignature, $signature)) {
            return $this->invalidScan($operator, $scannerPost, $payload, 'QR not found. Please scan a valid ticket QR.', $ipAddress, [
                'ticket_code' => $ticketCode !== '' ? $ticketCode : null,
            ]);
        }

        $ticket = $this->repository->findTicketByTicketCode($ticketCode);

        if (! $this->ticketIsActive($ticket)) {
            return $this->invalidScan($operator, $scannerPost, $payload, 'Ticket could not be verified.', $ipAddress, [
                'ticket_code' => $ticketCode,
                'ticket_id' => $ticket['ticket_id'] ?? null,
            ]);
        }

        $userId = (string) ($ticket['user_id'] ?? '');
        $user = $userId !== '' ? $this->repository->findUser($userId) : null;

        if (! is_array($user)) {
            return $this->invalidScan($operator, $scannerPost, $payload, 'Participant data could not be loaded.', $ipAddress, [
                'ticket_code' => $ticketCode,
                'ticket_id' => $ticket['ticket_id'] ?? null,
            ]);
        }

        $record = $this->repository->recordScannerAttendance(
            $ticket,
            $user,
            $this->makeScanEntry($operator, $scannerPost, [
                'scan_mode' => 'qr',
                'raw_payload' => $payload,
                'participant_snapshot' => $this->participantSummary($user, $ticket),
            ], $ipAddress),
        );

        return $this->buildScanResponse(
            (string) ($record['result'] ?? 'invalid'),
            is_array($record['ticket'] ?? null) ? $record['ticket'] : $ticket,
            $user,
            $scannerPost,
            is_array($record['log'] ?? null) ? $record['log'] : null,
        );
    }

    public function manualLookup(Admin $operator, string $scannerPost, string $entryCode): array
    {
        $normalizedEntryCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $entryCode) ?? '');
        $ticket = $this->repository->findTicketByEntryCode($normalizedEntryCode);

        if (! $this->ticketIsActive($ticket)) {
            return [
                'found' => false,
                'message' => 'Participant data was not found.',
            ];
        }

        $userId = (string) ($ticket['user_id'] ?? '');
        $user = $userId !== '' ? $this->repository->findUser($userId) : null;

        if (! is_array($user)) {
            return [
                'found' => false,
                'message' => 'Participant data was not found.',
            ];
        }

        $resolutionToken = (string) Str::ulid();

        Cache::put(
            $this->resolutionCacheKey($resolutionToken),
            [
                'operator_admin_id' => (string) $operator->getKey(),
                'scanner_post' => $scannerPost,
                'ticket_id' => (string) ($ticket['ticket_id'] ?? ''),
                'user_id' => (string) ($user['user_id'] ?? ''),
                'entry_code' => $normalizedEntryCode,
            ],
            now()->addSeconds(max(30, (int) config('scanner.manual_resolution_ttl_seconds', 120))),
        );

        return [
            'found' => true,
            'message' => 'Participant found.',
            'resolution_token' => $resolutionToken,
            'participant' => $this->participantSummary($user, $ticket),
        ];
    }

    public function manualConfirm(Admin $operator, string $scannerPost, string $resolutionToken, ?string $ipAddress = null): array
    {
        $resolution = Cache::pull($this->resolutionCacheKey($resolutionToken));

        if (! is_array($resolution)) {
            throw new RuntimeException('Manual lookup expired. Please search again.');
        }

        if ((string) ($resolution['operator_admin_id'] ?? '') !== (string) $operator->getKey()) {
            throw new RuntimeException('Manual lookup belongs to a different operator session.');
        }

        if ((string) ($resolution['scanner_post'] ?? '') !== $scannerPost) {
            throw new RuntimeException('Selected scanner post does not match the lookup session.');
        }

        $ticketId = (string) ($resolution['ticket_id'] ?? '');
        $userId = (string) ($resolution['user_id'] ?? '');
        $ticket = $ticketId !== '' ? $this->repository->findTicket($ticketId) : null;
        $user = $userId !== '' ? $this->repository->findUser($userId) : null;

        if (! $this->ticketIsActive($ticket) || ! is_array($user)) {
            return $this->invalidScan($operator, $scannerPost, null, 'Ticket could not be verified.', $ipAddress, [
                'ticket_id' => $ticket['ticket_id'] ?? $ticketId,
                'ticket_code' => $ticket['ticket_code'] ?? null,
            ]);
        }

        $record = $this->repository->recordScannerAttendance(
            $ticket,
            $user,
            $this->makeScanEntry($operator, $scannerPost, [
                'scan_mode' => 'manual',
                'raw_payload' => (string) ($resolution['entry_code'] ?? ''),
                'resolution_token' => $resolutionToken,
                'entry_code_display' => (string) ($ticket['entry_code_display'] ?? ''),
                'participant_snapshot' => $this->participantSummary($user, $ticket),
            ], $ipAddress),
        );

        return $this->buildScanResponse(
            (string) ($record['result'] ?? 'invalid'),
            is_array($record['ticket'] ?? null) ? $record['ticket'] : $ticket,
            $user,
            $scannerPost,
            is_array($record['log'] ?? null) ? $record['log'] : null,
        );
    }

    public function dashboard(Admin $operator, string $scannerPost, int $page = 1, int $perPage = 5): array
    {
        $snapshot = $this->cachedDashboardSnapshot($scannerPost);

        return [
            'stats' => $snapshot['stats'],
            'history' => $this->historyFromSnapshot($snapshot, $page, $perPage),
        ];
    }

    public function history(Admin $operator, string $scannerPost, int $page = 1, int $perPage = 5): array
    {
        return $this->historyFromSnapshot(
            $this->cachedDashboardSnapshot($scannerPost),
            $page,
            $perPage,
        );
    }

    public function stats(Admin $operator, string $scannerPost): array
    {
        return $this->cachedDashboardSnapshot($scannerPost)['stats'];
    }

    public function warmDashboardCache(string $scannerPost): array
    {
        return $this->refreshDashboardSnapshot($scannerPost);
    }

    private function buildScanResponse(
        string $status,
        array $ticket,
        array $user,
        string $scannerPost,
        ?array $log = null,
    ): array {
        $message = match ($status) {
            'success' => 'Participant check-in recorded.',
            'duplicate' => 'Participant was already checked in today.',
            default => 'Ticket could not be verified.',
        };

        return [
            'status' => $status,
            'message' => $message,
            'scanner_post' => $scannerPost,
            'participant' => $this->participantSummary($user, $ticket),
            'activity_item' => is_array($log) ? $this->historyItemFromLog($log, $ticket, $user) : null,
            'stats' => is_array($log)
                ? $this->syncDashboardStatsAfterScan($scannerPost, $log, $ticket, $user)
                : $this->stats($this->placeholderOperator(), $scannerPost),
        ];
    }

    private function invalidScan(
        Admin $operator,
        string $scannerPost,
        ?string $rawPayload,
        string $message,
        ?string $ipAddress = null,
        array $extra = [],
    ): array {
        $log = $this->repository->appendScanLog(array_merge(
            $this->makeScanEntry($operator, $scannerPost, [
                'scan_mode' => 'qr',
                'raw_payload' => $rawPayload,
                'result' => 'invalid',
            ], $ipAddress),
            $extra,
        ));

        return [
            'status' => 'invalid',
            'message' => $message,
            'scanner_post' => $scannerPost,
            'participant' => null,
            'activity_item' => $this->historyItemFromLog($log),
            'stats' => $this->syncDashboardStatsAfterScan($scannerPost, $log),
        ];
    }

    private function scannerActivityFilters(string $scannerPost, ?string $scopeDate = null): array
    {
        $scopeDate ??= $this->scannerScopeDate();

        return [
            'scanner_post' => $scannerPost,
            'from' => $scopeDate,
            'to' => $scopeDate,
        ];
    }

    private function cachedDashboardSnapshot(string $scannerPost): array
    {
        $scopeDate = $this->scannerScopeDate();
        $cacheKey = StaffScannerDashboardCache::snapshotKey($scannerPost, $scopeDate);
        $snapshot = Cache::get($cacheKey);

        if ($this->validDashboardSnapshot($snapshot)) {
            if (Cache::has(StaffScannerDashboardCache::staleKey($scannerPost, $scopeDate))) {
                $this->scheduleDashboardSnapshotRefreshAfterResponse($scannerPost, $scopeDate);
            }

            /** @var array{rows:array<int, array<string, mixed>>, stats:array<string, int>, scope_date:string} $snapshot */
            return $snapshot;
        }

        return $this->refreshDashboardSnapshot($scannerPost, $scopeDate);
    }

    private function refreshDashboardSnapshot(string $scannerPost, ?string $scopeDate = null): array
    {
        $scopeDate ??= $this->scannerScopeDate();
        $filters = $this->scannerActivityFilters($scannerPost, $scopeDate);
        $rows = array_map(
            fn (array $log): array => $this->dashboardActivityRow($log),
            array_values($this->repository->queryScanLogs($filters)),
        );
        $snapshot = [
            'scanner_post' => $scannerPost,
            'scope_date' => $scopeDate,
            'rows' => $rows,
            'stats' => $this->statsFromRows($rows),
            'generated_at' => now()->toIso8601String(),
        ];

        Cache::put(
            StaffScannerDashboardCache::snapshotKey($scannerPost, $scopeDate),
            $snapshot,
            now()->addDays(self::DASHBOARD_CACHE_TTL_DAYS),
        );
        Cache::forget(StaffScannerDashboardCache::staleKey($scannerPost, $scopeDate));

        return $snapshot;
    }

    private function historyFromSnapshot(array $snapshot, int $page = 1, int $perPage = 5): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));
        $rows = array_values(array_filter(
            $snapshot['rows'] ?? [],
            static fn (mixed $row): bool => is_array($row),
        ));
        $total = count($rows);
        $items = array_map(
            fn (array $log): array => $this->historyItemFromLog($log),
            array_slice($rows, ($page - 1) * $perPage, $perPage),
        );

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => ($page * $perPage) < $total,
                'scope_date' => (string) ($snapshot['scope_date'] ?? $this->scannerScopeDate()),
            ],
        ];
    }

    private function syncDashboardStatsAfterScan(
        string $scannerPost,
        array $log,
        ?array $ticket = null,
        ?array $user = null,
    ): array {
        $scopeDate = $this->scopeDateFromLog($log);
        $cacheKey = StaffScannerDashboardCache::snapshotKey($scannerPost, $scopeDate);
        $snapshot = Cache::get($cacheKey);

        if (! $this->validDashboardSnapshot($snapshot)) {
            return $this->refreshDashboardSnapshot($scannerPost, $scopeDate)['stats'];
        }

        $updatedRow = $this->dashboardActivityRow($log, $ticket, $user);
        $updatedRows = [$updatedRow];
        $updatedScanId = $this->scanIdFromLog($updatedRow);

        foreach ($snapshot['rows'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($this->scanIdFromLog($row) === $updatedScanId) {
                continue;
            }

            $updatedRows[] = $row;
        }

        $updatedSnapshot = [
            'scanner_post' => $scannerPost,
            'scope_date' => $scopeDate,
            'rows' => $updatedRows,
            'stats' => $this->statsFromRows($updatedRows),
            'generated_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $updatedSnapshot, now()->addDays(self::DASHBOARD_CACHE_TTL_DAYS));
        Cache::forget(StaffScannerDashboardCache::staleKey($scannerPost, $scopeDate));

        return $updatedSnapshot['stats'];
    }

    private function scheduleDashboardSnapshotRefreshAfterResponse(string $scannerPost, string $scopeDate): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $refreshLockKey = StaffScannerDashboardCache::refreshLockKey($scannerPost, $scopeDate);

        if (! Cache::add(
            $refreshLockKey,
            now()->toIso8601String(),
            now()->addSeconds(self::DASHBOARD_REFRESH_LOCK_SECONDS),
        )) {
            return;
        }

        app()->terminating(function () use ($refreshLockKey, $scannerPost, $scopeDate): void {
            try {
                $this->refreshDashboardSnapshot($scannerPost, $scopeDate);
            } catch (\Throwable) {
                // Keep serving the last known gate snapshot and retry later
                // rather than slowing down the current operator request.
            } finally {
                Cache::forget($refreshLockKey);
            }
        });
    }

    private function statsFromRows(array $rows): array
    {
        $stats = [
            'total_scans' => 0,
            'successful_scans' => 0,
            'duplicate_scans' => 0,
            'invalid_scans' => 0,
        ];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $stats['total_scans']++;

            match (strtolower(trim((string) ($row['result'] ?? 'invalid')))) {
                'success' => $stats['successful_scans']++,
                'duplicate' => $stats['duplicate_scans']++,
                default => $stats['invalid_scans']++,
            };
        }

        return $stats;
    }

    private function dashboardActivityRow(
        array $log,
        ?array $ticket = null,
        ?array $user = null,
    ): array {
        $participantSnapshot = $this->participantSnapshotFromLog($log);

        if ($participantSnapshot === null && is_array($ticket) && is_array($user)) {
            $participantSnapshot = $this->participantSummary($user, $ticket);
        }

        return [
            'scan_id' => $this->scanIdFromLog($log),
            'result' => strtolower(trim((string) ($log['result'] ?? 'invalid'))),
            'ticket_id' => (string) ($log['ticket_id'] ?? ($ticket['ticket_id'] ?? '')),
            'user_id' => (string) ($log['user_id'] ?? ($user['user_id'] ?? $ticket['user_id'] ?? '')),
            'ticket_code' => (string) ($log['ticket_code'] ?? ($ticket['ticket_code'] ?? '')),
            'entry_code_display' => trim((string) ($log['entry_code_display'] ?? ($ticket['entry_code_display'] ?? ''))),
            'scanner_name' => (string) ($log['scanner_name'] ?? ''),
            'scanned_at' => (string) ($log['scanned_at'] ?? ''),
            'participant_snapshot' => $participantSnapshot,
        ];
    }

    private function validDashboardSnapshot(mixed $snapshot): bool
    {
        return is_array($snapshot)
            && is_array($snapshot['rows'] ?? null)
            && is_array($snapshot['stats'] ?? null)
            && is_string($snapshot['scope_date'] ?? null);
    }

    private function scannerScopeDate(): string
    {
        return now($this->eventTimezone())->toDateString();
    }

    private function scopeDateFromLog(array $log): string
    {
        $scanDate = trim((string) ($log['scan_date'] ?? ''));

        if ($scanDate !== '') {
            return $scanDate;
        }

        $scannedAt = trim((string) ($log['scanned_at'] ?? ''));

        if ($scannedAt === '') {
            return $this->scannerScopeDate();
        }

        try {
            return Carbon::parse($scannedAt)
                ->setTimezone($this->eventTimezone())
                ->toDateString();
        } catch (\Throwable) {
            return $this->scannerScopeDate();
        }
    }

    private function ticketIsActive(?array $ticket): bool
    {
        return is_array($ticket)
            && (string) ($ticket['ticket_id'] ?? '') !== ''
            && strtolower((string) ($ticket['status'] ?? 'active')) === 'active';
    }

    private function makeScanEntry(Admin $operator, string $scannerPost, array $extra = [], ?string $ipAddress = null): array
    {
        $scannedAt = now()->toISOString();
        $eventTimezone = $this->eventTimezone();

        return array_merge([
            'scanner_id' => 'scanner-post:'.Str::slug($scannerPost),
            'scanner_name' => $scannerPost,
            'scanner_role' => 'staff',
            'operator_admin_id' => (string) $operator->getKey(),
            'operator_email' => (string) $operator->email,
            'ip_address' => $ipAddress,
            'scanned_at' => $scannedAt,
            'scan_date' => now($eventTimezone)->toDateString(),
        ], $extra);
    }

    private function historyItemFromLog(array $log, ?array $ticket = null, ?array $user = null): array
    {
        return [
            'scan_id' => $this->scanIdFromLog($log),
            'status' => (string) ($log['result'] ?? 'invalid'),
            'ticket_code' => (string) ($log['ticket_code'] ?? ''),
            'entry_code_display' => (string) ($log['entry_code_display'] ?? ''),
            'scanner_post' => (string) ($log['scanner_name'] ?? ''),
            'scanned_at' => (string) ($log['scanned_at'] ?? ''),
            'participant' => $this->participantSummaryFromLog($log, $ticket, $user),
        ];
    }

    private function participantSummaryFromLog(array $log, ?array $ticket = null, ?array $user = null): ?array
    {
        $snapshot = $this->participantSnapshotFromLog($log);
        if (is_array($snapshot)) {
            return $snapshot;
        }

        $ticket ??= $this->ticketFromLog($log);
        if (! is_array($ticket)) {
            return null;
        }

        $user ??= $this->userFromLog($log, $ticket);
        if (! is_array($user)) {
            return null;
        }

        return $this->participantSummary($user, $ticket);
    }

    private function participantSnapshotFromLog(array $log): ?array
    {
        $snapshot = $log['participant_snapshot'] ?? null;
        if (! is_array($snapshot)) {
            return null;
        }

        $fullName = trim((string) ($snapshot['full_name'] ?? $snapshot['name'] ?? ''));
        $country = strtoupper(trim((string) ($snapshot['country'] ?? '')));

        return [
            'name' => $fullName,
            'full_name' => $fullName,
            'email' => trim((string) ($snapshot['email'] ?? '')),
            'phone_number' => trim((string) ($snapshot['phone_number'] ?? '')),
            'country' => $country,
            'country_label' => trim((string) ($snapshot['country_label'] ?? '')),
            'ticket_code' => (string) ($snapshot['ticket_code'] ?? $log['ticket_code'] ?? ''),
            'entry_code_display' => (string) ($snapshot['entry_code_display'] ?? $log['entry_code_display'] ?? ''),
        ];
    }

    private function ticketFromLog(array $log): ?array
    {
        $ticketId = (string) ($log['ticket_id'] ?? '');
        if ($ticketId !== '') {
            $ticket = $this->repository->findTicket($ticketId);
            if (is_array($ticket)) {
                return $ticket;
            }
        }

        $ticketCode = strtoupper(trim((string) ($log['ticket_code'] ?? '')));

        return $ticketCode !== ''
            ? $this->repository->findTicketByTicketCode($ticketCode)
            : null;
    }

    private function userFromLog(array $log, array $ticket): ?array
    {
        $userId = (string) ($log['user_id'] ?? '');
        if ($userId !== '') {
            $user = $this->repository->findUser($userId);
            if (is_array($user)) {
                return $user;
            }
        }

        $ticketUserId = (string) ($ticket['user_id'] ?? '');

        return $ticketUserId !== ''
            ? $this->repository->findUser($ticketUserId)
            : null;
    }

    private function participantSummary(array $user, array $ticket): array
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

    private function scanIdFromLog(array $log): string
    {
        $scanId = trim((string) ($log['scan_id'] ?? ''));
        if ($scanId !== '') {
            return $scanId;
        }

        $documentId = trim((string) ($log['__id'] ?? ''));
        if ($documentId !== '') {
            return $documentId;
        }

        return hash('sha256', json_encode($log) ?: '');
    }

    private function eventTimezone(): string
    {
        return (string) config('admin.event.timezone', config('app.timezone', 'UTC'));
    }

    private function resolutionCacheKey(string $resolutionToken): string
    {
        return 'scanner:resolution:'.$resolutionToken;
    }

    private function placeholderOperator(): Admin
    {
        return new Admin([
            'id' => 0,
            'email' => '',
            'role' => 'scanner',
        ]);
    }
}
