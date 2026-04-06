<?php

namespace App\Services\Admin;

use App\Contracts\UserRepositoryInterface;
use App\Services\Tickets\TicketQrCodeService;
use App\Support\CountryCatalog;
use Carbon\CarbonImmutable;

class AdminQrManagementLookupService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AdminFirestoreRepository $repository,
        private readonly AdminAnalyticsService $analytics,
        private readonly TicketQrCodeService $ticketQrCodeService,
    ) {
    }

    public function lookup(array $data): array
    {
        $user = match ((string) ($data['search_type'] ?? '')) {
            'email' => $this->users->findByEmail((string) $data['email']),
            'phone' => $this->users->findByPhoneNumber((string) $data['phone_number']),
            'passport', 'ic' => $this->users->findByIdentityDocument(
                (string) $data['identity_type'],
                (string) $data['country'],
                (string) $data['identity_number'],
            ),
            default => null,
        };

        if (! $user) {
            return $this->notFoundResponse();
        }

        $ticket = $this->resolveTicket($user);

        if (! $ticket || blank($ticket['ticket_id'] ?? null)) {
            return $this->notFoundResponse();
        }

        $countryCode = strtoupper((string) ($user['country'] ?? ''));
        $phoneParts = $this->resolvePhoneParts($user);
        $userId = (string) ($user['user_id'] ?? '');
        $attendanceProgress = $this->resolveAttendanceProgress($user, $ticket);
        $entryCodeDisplay = $this->resolveEntryCodeDisplay($ticket);

        return [
            'found' => true,
            'participant' => [
                'user_id' => $userId,
                'full_name' => (string) ($user['full_name'] ?? ''),
                'email' => (string) ($user['email'] ?? ''),
                'phone_country_code' => $phoneParts['phone_country_code'],
                'phone_national_number' => $phoneParts['phone_national_number'],
                'phone_number' => $phoneParts['phone_number'],
                'country' => $countryCode,
                'country_label' => CountryCatalog::nameFor($countryCode) ?? $countryCode,
                'identity_type' => strtolower((string) ($user['identity_type'] ?? '')),
                'identity_number' => (string) ($user['identity_number'] ?? ''),
                'account_status' => (string) ($user['account_status'] ?? ''),
                'verification_status' => (string) ($user['verification_status'] ?? ''),
                'attendance_days_count' => $attendanceProgress['attendance_days_count'],
                'attendance_total_days' => $attendanceProgress['attendance_total_days'],
                'attendance_progress_percent' => $attendanceProgress['attendance_progress_percent'],
            ],
            'ticket' => [
                'ticket_id' => (string) ($ticket['ticket_id'] ?? ''),
                'ticket_code' => (string) ($ticket['ticket_code'] ?? ''),
                'entry_code' => strtoupper(trim((string) ($ticket['entry_code'] ?? ''))),
                'entry_code_display' => $entryCodeDisplay,
                'attendance_status' => (string) ($ticket['attendance_status'] ?? ''),
                'qr_version' => (string) ($ticket['qr_version'] ?? ''),
                'created_at' => (string) ($ticket['created_at'] ?? ''),
                'updated_at' => (string) ($ticket['updated_at'] ?? ''),
                'regenerated_at' => (string) ($ticket['regenerated_at'] ?? ''),
            ],
            'ticket_url' => $this->ticketQrCodeService->signedTicketUrl((string) $ticket['ticket_id']),
        ];
    }

    private function notFoundResponse(): array
    {
        return [
            'found' => false,
            'message' => 'Participant data was not found.',
        ];
    }

    private function resolvePhoneParts(array $user): array
    {
        $phoneCountryCode = trim((string) ($user['phone_country_code'] ?? ''));
        $phoneNationalNumber = trim((string) ($user['phone_national_number'] ?? ''));
        $phoneNumber = $this->normalizePhoneNumber((string) ($user['phone_number'] ?? ''));
        $country = strtoupper((string) ($user['country'] ?? ''));

        if ($phoneNumber === '' && $phoneCountryCode !== '' && $phoneNationalNumber !== '') {
            $phoneNumber = $phoneCountryCode.$phoneNationalNumber;
        }

        if ($phoneCountryCode === '' && $country !== '') {
            foreach (CountryCatalog::dialCodesFor($country) as $dialCode) {
                if (str_starts_with($phoneNumber, (string) $dialCode)) {
                    $phoneCountryCode = (string) $dialCode;
                    break;
                }
            }
        }

        if ($phoneNationalNumber === '' && $phoneNumber !== '') {
            if ($phoneCountryCode !== '' && str_starts_with($phoneNumber, $phoneCountryCode)) {
                $phoneNationalNumber = ltrim(substr($phoneNumber, strlen($phoneCountryCode)), '0');
            } else {
                $phoneNationalNumber = ltrim(ltrim($phoneNumber, '+'), '0');
            }
        }

        return [
            'phone_country_code' => $phoneCountryCode,
            'phone_national_number' => $phoneNationalNumber,
            'phone_number' => $phoneNumber,
        ];
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        return $digits === '' ? '' : '+'.$digits;
    }

    private function resolveTicket(array $user): ?array
    {
        $ticketId = trim((string) ($user['ticket_id'] ?? ''));

        if ($ticketId !== '') {
            $ticket = $this->users->findTicketById($ticketId);

            if ($ticket !== null) {
                return $ticket;
            }
        }

        $userId = trim((string) ($user['user_id'] ?? ''));

        return $userId !== ''
            ? $this->users->findTicketByUserId($userId)
            : null;
    }

    private function resolveAttendanceProgress(array $user, array $ticket): array
    {
        $userId = trim((string) ($user['user_id'] ?? ''));

        if ($userId !== '') {
            $attendanceRow = $this->resolveAttendanceRowFromScanLogs($userId, $ticket);

            if ($attendanceRow !== []) {
                return [
                    'attendance_days_count' => max(0, (int) ($attendanceRow['attendance_days_count'] ?? 0)),
                    'attendance_total_days' => max(0, (int) ($attendanceRow['attendance_total_days'] ?? 0)),
                    'attendance_progress_percent' => max(0, min(100, (int) ($attendanceRow['attendance_progress_percent'] ?? 0))),
                ];
            }
        }

        $attendanceDaysCount = max(0, (int) ($user['attendance_days_count'] ?? 0));
        $attendanceTotalDays = max(
            0,
            (int) ($user['attendance_total_days'] ?? $this->eventTotalDays()),
        );
        $attendanceProgressPercent = $user['attendance_progress_percent'] ?? null;

        if ($attendanceProgressPercent === null && $attendanceTotalDays > 0) {
            $attendanceProgressPercent = (int) round(($attendanceDaysCount / $attendanceTotalDays) * 100);
        }

        return [
            'attendance_days_count' => $attendanceDaysCount,
            'attendance_total_days' => $attendanceTotalDays,
            'attendance_progress_percent' => max(0, min(100, (int) ($attendanceProgressPercent ?? 0))),
        ];
    }

    private function resolveAttendanceRowFromScanLogs(string $userId, array $ticket): array
    {
        $scanLogs = $this->repository->findScanLogsByUserIds([$userId]);

        if ($scanLogs === []) {
            return [];
        }

        $attendanceRows = $this->analytics->attachAttendanceProgress([[
            'user_id' => $userId,
            'ticket_id' => (string) ($ticket['ticket_id'] ?? ''),
            'ticket_code' => (string) ($ticket['ticket_code'] ?? ''),
        ]], $scanLogs);

        return is_array($attendanceRows[0] ?? null) ? $attendanceRows[0] : [];
    }

    private function resolveEntryCodeDisplay(array $ticket): string
    {
        $entryCodeDisplay = trim((string) ($ticket['entry_code_display'] ?? ''));

        if ($entryCodeDisplay !== '') {
            return strtoupper($entryCodeDisplay);
        }

        $entryCode = trim((string) ($ticket['entry_code'] ?? ''));

        return $this->formatEntryCodeDisplay($entryCode);
    }

    private function eventTotalDays(): int
    {
        $timezone = (string) config('admin.event.timezone', config('app.timezone', 'UTC'));
        $eventStartDate = CarbonImmutable::parse(
            (string) config('admin.event.start_date', '2026-04-09'),
            $timezone,
        )->startOfDay();
        $eventEndDate = CarbonImmutable::parse(
            (string) config('admin.event.end_date', '2026-04-19'),
            $timezone,
        )->startOfDay();

        if ($eventEndDate->lt($eventStartDate)) {
            [$eventStartDate, $eventEndDate] = [$eventEndDate, $eventStartDate];
        }

        return (int) max(1, $eventStartDate->diffInDays($eventEndDate) + 1);
    }

    private function formatEntryCodeDisplay(string $entryCode): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $entryCode) ?? '');

        if ($normalized === '') {
            return '';
        }

        if (strlen($normalized) <= 4) {
            return $normalized;
        }

        return substr($normalized, 0, 4).'-'.substr($normalized, 4, 4);
    }
}
