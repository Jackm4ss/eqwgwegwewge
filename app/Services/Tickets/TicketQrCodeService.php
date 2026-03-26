<?php

namespace App\Services\Tickets;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
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
            'qr_version' => 'v1',
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public function regenerateTicketAttributes(array $ticket): array
    {
        $version = (string) ($ticket['qr_version'] ?? 'v1');
        $numericVersion = (int) preg_replace('/\D+/', '', $version);
        $nextVersion = $numericVersion > 0 ? $numericVersion + 1 : 2;
        $now = now()->toISOString();

        return array_merge($ticket, [
            'ticket_code' => strtoupper((string) Str::ulid()),
            'qr_version' => 'v'.$nextVersion,
            'status' => 'active',
            'attendance_status' => 'not_checked_in',
            'checked_in_at' => null,
            'last_scanned_at' => null,
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
            'qr_reset_at' => $now,
            'qr_reset_count' => ((int) ($ticket['qr_reset_count'] ?? 0)) + 1,
            'updated_at' => $now,
        ]);
    }

    public function payloadForTicket(array $ticket): string
    {
        return $this->payloadForTicketCode((string) $ticket['ticket_code']);
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

    public function signedTicketUrl(string $ticketId): string
    {
        return URL::signedRoute('ticket.show', ['ticketId' => $ticketId]);
    }

    public function signedTicketDownloadUrl(string $ticketId): string
    {
        return URL::signedRoute('ticket.download', ['ticketId' => $ticketId]);
    }

    public function signTicketCode(string $ticketCode): string
    {
        $key = $this->resolveAppKey();
        $signature = hash_hmac('sha256', $ticketCode, $key, true);

        return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
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
}
