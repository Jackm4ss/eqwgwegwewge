<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Services\Staff\StaffScannerService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\ScannerStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffScannerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.bootstrap_password' => 'LocalAdmin123!',
            'admin.seed_count' => 1,
            'scanner.bootstrap_password' => 'ScannerPass123!',
            'scanner.seed_count' => 1,
            'scanner.posts' => ['Gate A', 'Gate B'],
        ]);

        $this->seed(AdminSeeder::class);
        $this->seed(ScannerStaffSeeder::class);
    }

    public function test_scanner_can_fetch_session_and_available_posts(): void
    {
        $scanner = Admin::query()->where('role', 'scanner')->firstOrFail();

        $this->actingAs($scanner, 'admin')
            ->withSession(['staff.scanner_post' => 'Gate A'])
            ->getJson('/staff/session')
            ->assertOk()
            ->assertJson([
                'user' => [
                    'email' => 'scanner01@songkran.local',
                    'role' => 'scanner',
                ],
                'scanner_post' => 'Gate A',
                'available_posts' => ['Gate A', 'Gate B'],
            ]);
    }

    public function test_scanner_can_submit_qr_scan(): void
    {
        $scanner = Admin::query()->where('role', 'scanner')->firstOrFail();

        $this->mock(StaffScannerService::class, function ($mock) use ($scanner): void {
            $mock->shouldReceive('scan')
                ->once()
                ->withArgs(function (Admin $operator, string $scannerPost, string $payload, ?string $ipAddress) use ($scanner): bool {
                    return $operator->is($scanner)
                        && $scannerPost === 'Gate A'
                        && $payload === 'esf1:TICKET123:signedpayload'
                        && $ipAddress === '127.0.0.1';
                })
                ->andReturn([
                    'status' => 'success',
                    'message' => 'Participant check-in recorded.',
                    'scanner_post' => 'Gate A',
                    'participant' => [
                        'name' => 'Te*** Us**',
                        'email' => 'te**@example.com',
                        'ticket_code' => 'TICKET123',
                        'entry_code_display' => 'ABCD-2345',
                    ],
                    'stats' => [
                        'total_scans' => 1,
                        'successful_scans' => 1,
                        'duplicate_scans' => 0,
                        'invalid_scans' => 0,
                    ],
                ]);
        });

        $this->actingAs($scanner, 'admin')
            ->withSession(['staff.scanner_post' => 'Gate A'])
            ->postJson('/staff/scan', [
                'payload' => 'esf1:TICKET123:signedpayload',
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'scanner_post' => 'Gate A',
                'participant' => [
                    'ticket_code' => 'TICKET123',
                    'entry_code_display' => 'ABCD-2345',
                ],
            ]);
    }

    public function test_scanner_can_lookup_and_confirm_manual_entry(): void
    {
        $scanner = Admin::query()->where('role', 'scanner')->firstOrFail();

        $this->mock(StaffScannerService::class, function ($mock) use ($scanner): void {
            $mock->shouldReceive('manualLookup')
                ->once()
                ->withArgs(function (Admin $operator, string $scannerPost, string $entryCode) use ($scanner): bool {
                    return $operator->is($scanner)
                        && $scannerPost === 'Gate A'
                        && $entryCode === 'ABCD-2345';
                })
                ->andReturn([
                    'found' => true,
                    'message' => 'Participant found.',
                    'resolution_token' => 'resolution-token-123',
                    'participant' => [
                        'name' => 'Te*** Us**',
                        'email' => 'te**@example.com',
                        'ticket_code' => 'TICKET123',
                        'entry_code_display' => 'ABCD-2345',
                    ],
                ]);

            $mock->shouldReceive('manualConfirm')
                ->once()
                ->withArgs(function (Admin $operator, string $scannerPost, string $resolutionToken, ?string $ipAddress) use ($scanner): bool {
                    return $operator->is($scanner)
                        && $scannerPost === 'Gate A'
                        && $resolutionToken === 'resolution-token-123'
                        && $ipAddress === '127.0.0.1';
                })
                ->andReturn([
                    'status' => 'duplicate',
                    'message' => 'Participant was already checked in today.',
                    'participant' => [
                        'ticket_code' => 'TICKET123',
                        'entry_code_display' => 'ABCD-2345',
                    ],
                    'stats' => [
                        'total_scans' => 2,
                        'successful_scans' => 1,
                        'duplicate_scans' => 1,
                        'invalid_scans' => 0,
                    ],
                ]);
        });

        $this->actingAs($scanner, 'admin')
            ->withSession(['staff.scanner_post' => 'Gate A'])
            ->postJson('/staff/manual-lookup', [
                'entry_code' => 'ABCD-2345',
            ])
            ->assertOk()
            ->assertJson([
                'found' => true,
                'resolution_token' => 'resolution-token-123',
                'participant' => [
                    'entry_code_display' => 'ABCD-2345',
                ],
            ]);

        $this->actingAs($scanner, 'admin')
            ->withSession(['staff.scanner_post' => 'Gate A'])
            ->postJson('/staff/manual-confirm', [
                'resolution_token' => 'resolution-token-123',
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'duplicate',
                'participant' => [
                    'ticket_code' => 'TICKET123',
                ],
            ]);
    }

    public function test_scanner_can_fetch_history_and_stats(): void
    {
        $scanner = Admin::query()->where('role', 'scanner')->firstOrFail();

        $this->mock(StaffScannerService::class, function ($mock) use ($scanner): void {
            $mock->shouldReceive('history')
                ->once()
                ->withArgs(function (Admin $operator, string $scannerPost) use ($scanner): bool {
                    return $operator->is($scanner) && $scannerPost === 'Gate A';
                })
                ->andReturn([
                    [
                        'status' => 'success',
                        'ticket_code' => 'TICKET123',
                        'entry_code_display' => 'ABCD-2345',
                    ],
                ]);

            $mock->shouldReceive('stats')
                ->once()
                ->withArgs(function (Admin $operator, string $scannerPost) use ($scanner): bool {
                    return $operator->is($scanner) && $scannerPost === 'Gate A';
                })
                ->andReturn([
                    'total_scans' => 3,
                    'successful_scans' => 2,
                    'duplicate_scans' => 1,
                    'invalid_scans' => 0,
                ]);
        });

        $this->actingAs($scanner, 'admin')
            ->withSession(['staff.scanner_post' => 'Gate A'])
            ->getJson('/staff/history')
            ->assertOk()
            ->assertJsonCount(1);

        $this->actingAs($scanner, 'admin')
            ->withSession(['staff.scanner_post' => 'Gate A'])
            ->getJson('/staff/stats')
            ->assertOk()
            ->assertJson([
                'total_scans' => 3,
                'successful_scans' => 2,
                'duplicate_scans' => 1,
                'invalid_scans' => 0,
            ]);
    }
}
