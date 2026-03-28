<?php

namespace App\Services\Tickets;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class TicketQrCodeService
{
    public function makeTicketAttributes(string $userId): array
    {
        $now = now()->toISOString();

        return [
            'ticket_id' => (string) Str::ulid(),
            'user_id' => $userId,
            'event_code' => (string) config('event.code', 'SONGKRAN2026'),
            'ticket_code' => strtoupper((string) Str::ulid()),
            'status' => 'active',
            'qr_token' => $this->makeQrToken(),
            'qr_version' => 'v2',
            'qr_format' => 'esf2',
            'qr_issued_at' => $now,
            'qr_expires_at' => $this->eventEndsAt()->toISOString(),
            'activated_at' => $now,
            'attendance_status' => 'not_checked_in',
            'checked_in_at' => null,
            'last_scanned_at' => null,
            'last_valid_scan_at' => null,
            'last_valid_scan_date' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public function regenerateTicketAttributes(array $ticket): array
    {
        $version = (string) ($ticket['qr_version'] ?? 'v2');
        $numericVersion = (int) preg_replace('/\D+/', '', $version);
        $nextVersion = $numericVersion > 0 ? $numericVersion + 1 : 2;
        $now = now()->toISOString();

        return array_merge($ticket, [
            'status' => 'active',
            'qr_token' => $this->makeQrToken(),
            'qr_version' => 'v'.$nextVersion,
            'qr_format' => 'esf2',
            'qr_issued_at' => $now,
            'qr_expires_at' => $this->eventEndsAt()->toISOString(),
            'attendance_status' => 'not_checked_in',
            'checked_in_at' => null,
            'last_scanned_at' => null,
            'last_valid_scan_at' => null,
            'last_valid_scan_date' => null,
            'regenerated_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function resetAttendanceAttributes(array $ticket): array
    {
        $now = now()->toISOString();

        return array_merge($ticket, [
            'status' => 'active',
            'attendance_status' => 'not_checked_in',
            'checked_in_at' => null,
            'last_scanned_at' => null,
            'last_valid_scan_at' => null,
            'last_valid_scan_date' => null,
            'qr_reset_at' => $now,
            'qr_reset_count' => ((int) ($ticket['qr_reset_count'] ?? 0)) + 1,
            'updated_at' => $now,
        ]);
    }

    public function payloadForTicket(array $ticket): string
    {
        return $this->payloadForUserToken(
            (string) ($ticket['user_id'] ?? ''),
            (string) ($ticket['qr_token'] ?? ''),
        );
    }

    public function payloadForUserToken(string $userId, string $qrToken): string
    {
        $userId = trim($userId);
        $qrToken = trim($qrToken);

        if ($userId === '' || $qrToken === '') {
            throw new RuntimeException('QR payload requires both user ID and token.');
        }

        return sprintf('esf2:%s:%s', $userId, $qrToken);
    }

    public function parsePayload(string $payload): ?array
    {
        return $this->inspectPayload($payload)['parsed'];
    }

    public function inspectPayload(string $payload): array
    {
        $normalized = $this->normalizePayload($payload);
        $debug = [
            'received_payload' => $payload,
            'received_payload_hex' => bin2hex($payload),
            'normalized_payload' => $normalized,
            'normalized_payload_hex' => bin2hex($normalized),
            'colon_count' => substr_count($normalized, ':'),
            'prefix_guess' => $this->prefixGuess($normalized),
        ];

        if ($normalized === '') {
            return [
                'parsed' => null,
                'debug' => array_merge($debug, [
                    'parser_status' => 'invalid',
                    'parser_reason' => 'empty_after_normalize',
                ]),
            ];
        }

        if (($debug['prefix_guess'] ?? '') === 'esf1') {
            return [
                'parsed' => null,
                'debug' => array_merge($debug, [
                    'parser_status' => 'invalid',
                    'parser_reason' => 'legacy_esf1_detected',
                ]),
            ];
        }

        if (! preg_match('/(?:^|[^A-Za-z0-9])(esf2):([^:\s]+):([A-Fa-f0-9]+)(?:$|[^A-Za-z0-9])/i', $normalized, $matches)) {
            return [
                'parsed' => null,
                'debug' => array_merge($debug, [
                    'parser_status' => 'invalid',
                    'parser_reason' => 'pattern_miss',
                ]),
            ];
        }

        $version = strtolower(trim((string) ($matches[1] ?? '')));
        $userId = trim((string) ($matches[2] ?? ''));
        $qrToken = strtolower(trim((string) ($matches[3] ?? '')));

        if ($version !== 'esf2' || $userId === '' || $qrToken === '') {
            return [
                'parsed' => null,
                'debug' => array_merge($debug, [
                    'parser_status' => 'invalid',
                    'parser_reason' => 'empty_segment',
                ]),
            ];
        }

        return [
            'parsed' => [
                'version' => $version,
                'user_id' => $userId,
                'qr_token' => $qrToken,
            ],
            'debug' => array_merge($debug, [
                'parser_status' => 'ok',
                'parser_reason' => 'matched_esf2_payload',
            ]),
        ];
    }

    public function payloadForTicketCode(string $ticketCode): string
    {
        return sprintf(
            'esf1:%s:%s',
            $ticketCode,
            $this->signTicketCode($ticketCode),
        );
    }

    public function renderSvg(string $payload, int $size = 320): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 2),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($payload);
    }

    public function renderPngBinary(string $payload, int $size = 320): string
    {
        $renderer = new GDLibRenderer($size, 2, 'png');

        return (new Writer($renderer))->writeString($payload);
    }

    public function renderJpegBinary(string $payload, int $size = 320, int $quality = 90): string
    {
        $renderer = new GDLibRenderer($size, 2, 'jpeg', $quality);

        return (new Writer($renderer))->writeString($payload);
    }

    public function signedTicketUrl(string $ticketId): string
    {
        return URL::signedRoute('ticket.show', ['ticketId' => $ticketId]);
    }

    public function signedTicketDownloadUrl(string $ticketId): string
    {
        return URL::signedRoute('ticket.download', ['ticketId' => $ticketId]);
    }

    public function signedTicketQrUrl(string $ticketId): string
    {
        return URL::signedRoute('ticket.qr', ['ticketId' => $ticketId]);
    }

    public function signTicketCode(string $ticketCode): string
    {
        $key = $this->resolveAppKey();
        $signature = hash_hmac('sha256', $ticketCode, $key, true);

        return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    public function eventStartsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            (string) config('event.start_date', '2026-04-09'),
            config('app.timezone')
        )->startOfDay();
    }

    public function eventEndsAt(): CarbonImmutable
    {
        $start = $this->eventStartsAt();
        $end = CarbonImmutable::parse(
            (string) config('event.end_date', '2026-04-19'),
            config('app.timezone')
        )->endOfDay();

        return $end->lt($start) ? $start->endOfDay() : $end;
    }

    public function isWithinEventWindow(CarbonImmutable|string|null $dateTime = null): bool
    {
        $moment = $dateTime instanceof CarbonImmutable
            ? $dateTime
            : CarbonImmutable::parse((string) ($dateTime ?? now()->toISOString()), config('app.timezone'));

        return $moment->betweenIncluded($this->eventStartsAt(), $this->eventEndsAt());
    }

    private function resolveAppKey(): string
    {
        $appKey = (string) config('app.key');

        if ($appKey === '') {
            throw new RuntimeException('APP_KEY is not configured.');
        }

        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);

            if ($decoded === false) {
                throw new RuntimeException('APP_KEY base64 payload is invalid.');
            }

            return $decoded;
        }

        return $appKey;
    }

    private function makeQrToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    private function normalizePayload(string $payload): string
    {
        $normalized = str_replace('：', ':', $payload);
        $normalized = preg_replace('/[\x00-\x1F\x7F]/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[\x{200B}-\x{200D}\x{2060}\x{FEFF}]/u', '', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function prefixGuess(string $payload): string
    {
        $segments = explode(':', $payload, 2);

        return strtolower(trim((string) ($segments[0] ?? '')));
    }
}
