<?php

namespace Tests\Unit;

use App\Contracts\UserRepositoryInterface;
use App\Models\ScannerStation;
use App\Services\Firebase\FirebaseClientFactory;
use App\Services\Firebase\FirestoreRestApi;
use App\Services\Firebase\FirestoreTimestampNormalizer;
use App\Services\Scanner\ScannerService;
use App\Services\Tickets\TicketQrCodeService;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ScannerServiceTest extends TestCase
{
    public function test_scan_includes_participant_details_when_ticket_record_is_missing(): void
    {
        config(['firebase.transport' => 'rest']);

        $users = Mockery::mock(UserRepositoryInterface::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);
        $factory = Mockery::mock(FirebaseClientFactory::class);
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);

        $ticketQrCodeService->shouldReceive('inspectPayload')
            ->once()
            ->with('esf2:user-123:token-abc')
            ->andReturn([
                'parsed' => [
                    'user_id' => 'user-123',
                    'qr_token' => 'token-abc',
                ],
                'debug' => [],
            ]);

        $users->shouldReceive('findById')
            ->once()
            ->with('user-123')
            ->andReturn([
                'user_id' => 'user-123',
                'full_name' => 'Alya Putri',
                'phone_number' => '+628123456789',
                'identity_type' => 'passport',
                'identity_number' => 'A1234567',
            ]);

        $users->shouldReceive('findTicketByUserId')
            ->once()
            ->with('user-123')
            ->andReturn(null);

        $timestamps->shouldReceive('prepareForStorage')
            ->once()
            ->andReturnUsing(static fn (array $payload): array => $payload);

        $restApi->shouldReceive('makeSetWrite')
            ->once()
            ->withArgs(static fn (string $path, array $payload, bool $merge): bool => str_contains($path, 'scan_logs/')
                && ($payload['result_reason'] ?? null) === 'ticket_not_found'
                && $merge === false)
            ->andReturn(['write' => 'ok']);

        $restApi->shouldReceive('commit')
            ->once()
            ->with(Mockery::type('array'));

        $service = new ScannerService(
            $users,
            $ticketQrCodeService,
            $factory,
            $restApi,
            $timestamps,
        );

        $operator = new \App\Models\StaffUser([
            'name' => 'Songkran Staff 01',
            'email' => 'staff01@songkran.local',
            'role' => 'operator',
            'is_active' => true,
        ]);
        $operator->id = 7;

        $station = new ScannerStation([
            'station_id' => 'east-a',
            'scanner_id' => 'scanner-east-a',
            'scanner_name' => 'East Gate A',
            'gate_id' => 'east',
            'gate_name' => 'East Gate',
            'is_active' => true,
        ]);

        $result = $service->scan('esf2:user-123:token-abc', $operator, $station);

        $this->assertSame('invalid', $result['status']);
        $this->assertSame('ticket_not_found', $result['reason']);
        $this->assertSame('Alya Putri', $result['participant_name']);
        $this->assertSame('+628123456789', $result['participant_phone_number']);
        $this->assertSame('Passport', $result['participant_identity_label']);
        $this->assertSame('A1234567', $result['participant_identity_number']);
    }

    public function test_today_stats_returns_blank_payload_when_firestore_query_fails(): void
    {
        config(['firebase.transport' => 'rest']);

        $users = Mockery::mock(UserRepositoryInterface::class);
        $ticketQrCodeService = Mockery::mock(TicketQrCodeService::class);
        $factory = Mockery::mock(FirebaseClientFactory::class);
        $restApi = Mockery::mock(FirestoreRestApi::class);
        $timestamps = Mockery::mock(FirestoreTimestampNormalizer::class);

        $restApi->shouldReceive('runQuery')
            ->once()
            ->andThrow(new RuntimeException('Failed to query Firestore documents.'));

        $service = new ScannerService(
            $users,
            $ticketQrCodeService,
            $factory,
            $restApi,
            $timestamps,
        );

        $station = new ScannerStation([
            'station_id' => 'north-a',
            'scanner_id' => 'scanner-north-a',
            'scanner_name' => 'North Gate A',
            'gate_id' => 'north',
            'gate_name' => 'North Gate',
            'is_active' => true,
        ]);

        $stats = $service->todayStats($station);

        $this->assertSame('north-a', $stats['station_id']);
        $this->assertSame('scanner-north-a', $stats['scanner_id']);
        $this->assertSame(0, $stats['total_scans']);
        $this->assertSame(0, $stats['valid_scans']);
        $this->assertSame(0, $stats['duplicate_scans']);
        $this->assertSame(0, $stats['invalid_scans']);
        $this->assertSame(0, $stats['expired_scans']);
        $this->assertFalse($stats['stats_available']);
        $this->assertNull($stats['last_scanned_at']);
        $this->assertNull($stats['last_result']);
    }
}
