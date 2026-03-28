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

    public function test_v2_payload_uses_user_id_and_qr_token(): void
    {
        $service = app(TicketQrCodeService::class);

        $ticket = $service->makeTicketAttributes('user-123');
        $payload = $service->payloadForTicket($ticket);

        $this->assertSame(sprintf('esf2:user-123:%s', $ticket['qr_token']), $payload);
        $this->assertSame([
            'version' => 'esf2',
            'user_id' => 'user-123',
            'qr_token' => $ticket['qr_token'],
        ], $service->parsePayload($payload));
    }

    public function test_regenerate_rotates_token_and_bumps_version(): void
    {
        $service = app(TicketQrCodeService::class);

        $ticket = $service->makeTicketAttributes('user-123');
        $regenerated = $service->regenerateTicketAttributes($ticket);

        $this->assertSame('user-123', $regenerated['user_id']);
        $this->assertSame('esf2', $regenerated['qr_format']);
        $this->assertSame('v3', $regenerated['qr_version']);
        $this->assertNotSame($ticket['qr_token'], $regenerated['qr_token']);
        $this->assertNotNull($regenerated['regenerated_at']);
    }

    public function test_parse_payload_tolerates_invisible_characters_and_uppercase_prefix(): void
    {
        $service = app(TicketQrCodeService::class);

        $payload = "\u{FEFF}ESF2\u{FF1A}c44cb464-767f-4fc8-9ffe-7822a98c075f\u{FF1A}CC4D1DC44BB3EC22D1622696849502489C0B8A45CED9B43F\u{200B}";

        $this->assertSame([
            'version' => 'esf2',
            'user_id' => 'c44cb464-767f-4fc8-9ffe-7822a98c075f',
            'qr_token' => 'cc4d1dc44bb3ec22d1622696849502489c0b8a45ced9b43f',
        ], $service->parsePayload($payload));
    }

    public function test_inspect_payload_returns_debug_trace_for_invalid_payload(): void
    {
        $service = app(TicketQrCodeService::class);

        $inspection = $service->inspectPayload('hello-world');

        $this->assertNull($inspection['parsed']);
        $this->assertSame('invalid', $inspection['debug']['parser_status']);
        $this->assertSame('pattern_miss', $inspection['debug']['parser_reason']);
        $this->assertSame(0, $inspection['debug']['colon_count']);
        $this->assertSame('hello-world', $inspection['debug']['prefix_guess']);
    }

    public function test_inspect_payload_flags_legacy_esf1_qr(): void
    {
        $service = app(TicketQrCodeService::class);

        $inspection = $service->inspectPayload('esf1:01KMQX6V9JDBAXQ8DC0TVW1MWE:p1sCGesskVaZj1B9U4Fc01_8WG0ngokDA4mxQXGbhhk');

        $this->assertNull($inspection['parsed']);
        $this->assertSame('invalid', $inspection['debug']['parser_status']);
        $this->assertSame('legacy_esf1_detected', $inspection['debug']['parser_reason']);
        $this->assertSame('esf1', $inspection['debug']['prefix_guess']);
    }
}
