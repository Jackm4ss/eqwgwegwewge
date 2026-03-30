<?php

namespace App\Services\Admin;

use App\Mail\ParticipantUpdateMail;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Mail;

class AdminParticipantNotificationService
{
    private const TRACKED_PROFILE_FIELDS = [
        'full_name',
        'email',
        'phone_number',
        'country',
        'identity_type',
        'identity_number',
        'account_status',
        'verification_status',
    ];

    public function __construct(
        private readonly AdminAnalyticsService $analytics,
        private readonly TicketQrCodeService $ticketQrCodeService,
    ) {}

    public function sendProfileUpdated(array $beforeUser, array $afterUser, ?array $ticket = null): void
    {
        $recipient = trim((string) ($afterUser['email'] ?? ''));

        if ($recipient === '') {
            return;
        }

        $changes = $this->buildProfileChanges($beforeUser, $afterUser);

        if ($changes === []) {
            return;
        }

        Mail::to($recipient)->send(new ParticipantUpdateMail(
            user: $this->decorateUser($afterUser),
            ticket: $ticket ?? [],
            updateType: 'profile_update',
            changes: $changes,
            ticketUrl: $this->ticketUrl($ticket),
        ));
    }

    public function sendQrRegenerated(array $user, array $ticket): void
    {
        $recipient = trim((string) ($user['email'] ?? ''));
        $ticketId = trim((string) ($ticket['ticket_id'] ?? ''));

        if ($recipient === '' || $ticketId === '') {
            return;
        }

        Mail::to($recipient)->send(new ParticipantUpdateMail(
            user: $this->decorateUser($user),
            ticket: $ticket,
            updateType: 'qr_regenerated',
            changes: [
                [
                    'label' => 'Update Type',
                    'value' => 'A new QR pass is now active for your ticket.',
                ],
                [
                    'label' => 'Ticket Status',
                    'value' => 'Please use the latest QR below or from your live ticket page before arriving at the venue.',
                ],
            ],
            ticketUrl: $this->ticketQrCodeService->signedTicketUrl($ticketId),
            qrPngBinary: $this->ticketQrCodeService->renderPngBinary(
                $this->ticketQrCodeService->payloadForTicket($ticket),
                240,
            ),
        ));
    }

    public function sendParticipantDeleted(array $user, ?array $ticket = null): void
    {
        $recipient = trim((string) ($user['email'] ?? ''));

        if ($recipient === '') {
            return;
        }

        Mail::to($recipient)->send(new ParticipantUpdateMail(
            user: $this->decorateUser($user),
            ticket: $ticket ?? [],
            updateType: 'participant_deleted',
            changes: [
                [
                    'label' => 'Registration Status',
                    'value' => 'Your participant record has been removed from the Songkran Festival 2026 system.',
                ],
                [
                    'label' => 'Festival Access',
                    'value' => 'Any existing QR pass or ticket linked to this registration is no longer active.',
                ],
            ],
        ));
    }

    private function buildProfileChanges(array $beforeUser, array $afterUser): array
    {
        $changes = [];

        foreach (self::TRACKED_PROFILE_FIELDS as $field) {
            $beforeValue = $this->normalizedComparisonValue($field, $beforeUser[$field] ?? null);
            $afterValue = $this->normalizedComparisonValue($field, $afterUser[$field] ?? null);

            if ($beforeValue === $afterValue) {
                continue;
            }

            $changes[] = [
                'label' => $this->fieldLabel($field),
                'value' => $this->displayValue($field, $afterUser[$field] ?? null),
            ];
        }

        return $changes;
    }

    private function normalizedComparisonValue(string $field, mixed $value): string
    {
        return match ($field) {
            'country' => strtoupper(trim((string) $value)),
            'email', 'identity_type', 'account_status', 'verification_status' => strtolower(trim((string) $value)),
            default => trim((string) $value),
        };
    }

    private function displayValue(string $field, mixed $value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '-';
        }

        return match ($field) {
            'country' => $this->analytics->countryLabel($normalized),
            'identity_type' => $this->identityTypeLabel($normalized),
            'account_status' => $this->accountStatusLabel($normalized),
            'verification_status' => $this->verificationStatusLabel($normalized),
            default => $normalized,
        };
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'full_name' => 'Participant Name',
            'email' => 'Email Address',
            'phone_number' => 'Phone Number',
            'country' => 'Country',
            'identity_type' => 'Identity Document',
            'identity_number' => 'Identity Number',
            'account_status' => 'Account Status',
            'verification_status' => 'Verification Status',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    private function identityTypeLabel(string $value): string
    {
        return match (strtolower(trim($value))) {
            'national_id' => 'IC / National ID',
            default => 'Passport',
        };
    }

    private function accountStatusLabel(string $value): string
    {
        return match (strtolower(trim($value))) {
            'active' => 'Active',
            'blocked' => 'Blocked',
            default => 'Pending Verification',
        };
    }

    private function verificationStatusLabel(string $value): string
    {
        return strtolower(trim($value)) === 'verified'
            ? 'Verified'
            : 'Unverified';
    }

    private function decorateUser(array $user): array
    {
        $user['country_label'] = $user['country_label'] ?? $this->analytics->countryLabel($user['country'] ?? null);

        return $user;
    }

    private function ticketUrl(?array $ticket): ?string
    {
        $ticketId = trim((string) ($ticket['ticket_id'] ?? ''));

        if ($ticketId === '') {
            return null;
        }

        return $this->ticketQrCodeService->signedTicketUrl($ticketId);
    }
}
