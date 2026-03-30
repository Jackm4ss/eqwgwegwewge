<?php

namespace App\Services\Staff;

use App\Models\Admin;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class StaffScannerService
{
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
            ], $ipAddress),
        );

        return $this->buildScanResponse(
            (string) ($record['result'] ?? 'invalid'),
            is_array($record['ticket'] ?? null) ? $record['ticket'] : $ticket,
            $user,
            $scannerPost,
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
            ], $ipAddress),
        );

        return $this->buildScanResponse(
            (string) ($record['result'] ?? 'invalid'),
            is_array($record['ticket'] ?? null) ? $record['ticket'] : $ticket,
            $user,
            $scannerPost,
        );
    }

    public function history(Admin $operator, string $scannerPost, int $limit = 12): array
    {
        $logs = $this->filteredScannerLogs($scannerPost);

        usort($logs, fn (array $left, array $right): int => strcmp(
            (string) ($right['scanned_at'] ?? ''),
            (string) ($left['scanned_at'] ?? ''),
        ));

        return array_slice(array_map(function (array $log): array {
            return [
                'status' => (string) ($log['result'] ?? 'invalid'),
                'ticket_code' => (string) ($log['ticket_code'] ?? ''),
                'entry_code_display' => (string) ($log['entry_code_display'] ?? ''),
                'scanner_post' => (string) ($log['scanner_name'] ?? ''),
                'scanned_at' => (string) ($log['scanned_at'] ?? ''),
            ];
        }, $logs), 0, max(1, $limit));
    }

    public function stats(Admin $operator, string $scannerPost): array
    {
        $attendance = $this->analytics->buildAttendanceOverview(
            $this->filteredScannerLogs($scannerPost),
            [],
        );
        $dailyRows = $attendance['daily_attendance'] ?? [];

        $stats = [
            'total_scans' => 0,
            'successful_scans' => 0,
            'duplicate_scans' => 0,
            'invalid_scans' => 0,
        ];

        foreach ($dailyRows as $row) {
            $stats['total_scans'] += (int) ($row['total_scans'] ?? 0);
            $stats['successful_scans'] += (int) ($row['successful_attendance'] ?? 0);
            $stats['duplicate_scans'] += (int) ($row['duplicate_scans'] ?? 0);
            $stats['invalid_scans'] += (int) ($row['invalid_scans'] ?? 0);
        }

        return $stats;
    }

    private function buildScanResponse(string $status, array $ticket, array $user, string $scannerPost): array
    {
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
            'stats' => $this->stats($this->placeholderOperator(), $scannerPost),
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
        $this->repository->appendScanLog(array_merge(
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
            'stats' => $this->stats($this->placeholderOperator(), $scannerPost),
        ];
    }

    private function filteredScannerLogs(string $scannerPost): array
    {
        return array_values(array_filter(
            $this->repository->allScanLogs(),
            fn (array $log): bool => (string) ($log['scanner_name'] ?? '') === $scannerPost,
        ));
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
        $eventTimezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));

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
