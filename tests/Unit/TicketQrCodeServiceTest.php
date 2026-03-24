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
}
