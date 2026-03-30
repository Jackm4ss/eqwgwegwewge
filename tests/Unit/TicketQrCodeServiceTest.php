<?php

namespace Tests\Unit;

use App\Services\Tickets\TicketQrCodeService;
use Tests\TestCase;

class TicketQrCodeServiceTest extends TestCase
{
    public function test_payload_uses_expected_format_and_signature(): void
    {
        $service = app(TicketQrCodeService::class);

        $payload = $service->payloadForTicketCode('ABC123ULID');
        [$version, $ticketCode, $signature] = explode(':', $payload);

        $this->assertSame('esf1', $version);
        $this->assertSame('ABC123ULID', $ticketCode);
        $this->assertSame($service->signTicketCode('ABC123ULID'), $signature);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $signature);
    }

    public function test_render_svg_returns_svg_markup(): void
    {
        $service = app(TicketQrCodeService::class);

        $svg = $service->renderSvg($service->payloadForTicketCode('ABC123ULID'));

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }

    public function test_signed_ticket_qr_url_targets_qr_route(): void
    {
        $service = app(TicketQrCodeService::class);

        $url = $service->signedTicketQrUrl('01TESTULID');

        $this->assertStringContainsString('/ticket/01TESTULID/qr', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_render_download_card_jpeg_returns_jpeg_binary_with_extra_card_space(): void
    {
        $service = app(TicketQrCodeService::class);

        $binary = $service->renderDownloadCardJpeg(
            $service->payloadForTicketCode('ABC123ULID'),
            'LKR6-WANK',
        );

        $this->assertStringStartsWith("\xFF\xD8\xFF", $binary);

        $image = imagecreatefromstring($binary);

        $this->assertNotFalse($image);
        $this->assertGreaterThan(320, imagesy($image));

        if ($image instanceof \GdImage) {
            imagedestroy($image);
        }
    }

    public function test_make_ticket_attributes_include_entry_code_fields(): void
    {
        $service = app(TicketQrCodeService::class);

        $attributes = $service->makeTicketAttributes('user-123');

        $this->assertArrayHasKey('entry_code', $attributes);
        $this->assertArrayHasKey('entry_code_display', $attributes);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $attributes['entry_code']);
        $this->assertSame(
            substr($attributes['entry_code'], 0, 4).'-'.substr($attributes['entry_code'], 4, 4),
            $attributes['entry_code_display'],
        );
    }

    public function test_regenerate_ticket_attributes_rotates_entry_code(): void
    {
        $service = app(TicketQrCodeService::class);

        $original = [
            'ticket_id' => 'ticket-123',
            'user_id' => 'user-123',
            'ticket_code' => 'OLDCODE123',
            'entry_code' => 'ABCD2345',
            'entry_code_display' => 'ABCD-2345',
            'qr_version' => 'v1',
            'status' => 'active',
            'attendance_status' => 'checked_in',
            'checked_in_at' => '2026-03-29T08:00:00Z',
            'last_scanned_at' => '2026-03-29T08:05:00Z',
        ];

        $regenerated = $service->regenerateTicketAttributes($original);

        $this->assertNotSame($original['ticket_code'], $regenerated['ticket_code']);
        $this->assertNotSame($original['entry_code'], $regenerated['entry_code']);
        $this->assertNotSame($original['entry_code_display'], $regenerated['entry_code_display']);
        $this->assertSame('v2', $regenerated['qr_version']);
        $this->assertSame('not_checked_in', $regenerated['attendance_status']);
        $this->assertNull($regenerated['checked_in_at']);
        $this->assertNull($regenerated['last_scanned_at']);
    }
}
