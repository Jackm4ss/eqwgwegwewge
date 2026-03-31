<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ScannerGate;
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

    public function test_scanner_can_fetch_session(): void
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
            ]);
    }

    public function test_scanner_cannot_switch_gate_from_session_endpoint_after_login(): void
    {
        $scanner = Admin::query()->where('role', 'scanner')->firstOrFail();

        $this->actingAs($scanner, 'admin')
            ->withSession(['staff.scanner_post' => 'Gate A', '_token' => 'csrf-token'])
            ->postJson('/staff/session/scanner-post', [
                'scanner_post' => 'Gate B',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ])
            ->assertForbidden()
            ->assertJson([
                'message' => 'Scanner gate is locked after sign-in. Log out and sign in again to use another gate.',
            ])
            ->assertSessionHas('staff.scanner_post', 'Gate A');
    }

    public function test_scanner_session_clears_gate_when_it_is_no_longer_available(): void
    {
        $scanner = Admin::query()->where('role', 'scanner')->firstOrFail();

        ScannerGate::query()->where('name', 'Gate B')->delete();

        $this->actingAs($scanner, 'admin')
            ->withSession(['staff.scanner_post' => 'Gate B'])
            ->getJson('/staff/session')
            ->assertOk()
            ->assertJson([
                'scanner_post' => null,
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
                    'activity_item' => [
                        'scan_id' => 'scan-123',
                        'status' => 'success',
                        'ticket_code' => 'TICKET123',
                        'entry_code_display' => 'ABCD-2345',
                        'scanner_post' => 'Gate A',
                        'scanned_at' => '2026-03-31T10:00:00Z',
                        'participant' => [
                            'full_name' => 'Te*** Us**',
                            'email' => 'te**@example.com',
                        ],
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
            ->withSession(['staff.scanner_post' => 'Gate A', '_token' => 'csrf-token'])
            ->postJson('/staff/scan', [
                'payload' => 'esf1:TICKET123:signedpayload',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'scanner_post' => 'Gate A',
                'activity_item' => [
                    'scan_id' => 'scan-123',
                    'ticket_code' => 'TICKET123',
                ],
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
                    'activity_item' => [
                        'scan_id' => 'scan-456',
                        'status' => 'duplicate',
                        'ticket_code' => 'TICKET123',
                        'entry_code_display' => 'ABCD-2345',
                        'scanner_post' => 'Gate A',
                        'scanned_at' => '2026-03-31T10:05:00Z',
                        'participant' => [
                            'full_name' => 'Te*** Us**',
                            'email' => 'te**@example.com',
                        ],
                    ],
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
            ->withSession(['staff.scanner_post' => 'Gate A', '_token' => 'csrf-token'])
            ->postJson('/staff/manual-lookup', [
                'entry_code' => 'ABCD-2345',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
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
            ->withSession(['staff.scanner_post' => 'Gate A', '_token' => 'csrf-token'])
            ->postJson('/staff/manual-confirm', [
                'resolution_token' => 'resolution-token-123',
            ], [
                'X-CSRF-TOKEN' => 'csrf-token',
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'duplicate',
                'activity_item' => [
                    'scan_id' => 'scan-456',
                    'ticket_code' => 'TICKET123',
                ],
                'participant' => [
                    'ticket_code' => 'TICKET123',
                ],
            ]);
    }

    public function test_scanner_can_fetch_dashboard_paginated_history_and_stats(): void
    {
        $scanner = Admin::query()->where('role', 'scanner')->firstOrFail();

        $this->mock(StaffScannerService::class, function ($mock) use ($scanner): void {
            $mock->shouldReceive('dashboard')
                ->once()
                ->withArgs(function (Admin $operator, string $scannerPost, int $page, int $perPage) use ($scanner): bool {
                    return $operator->is($scanner)
                        && $scannerPost === 'Gate A'
                        && $page === 1
                        && $perPage === 20;
                })
                ->andReturn([
                    'stats' => [
                        'total_scans' => 3,
                        'successful_scans' => 2,
                        'duplicate_scans' => 1,
                        'invalid_scans' => 0,
                    ],
                    'history' => [
                        'items' => [
                            [
                                'scan_id' => 'scan-123',
                                'status' => 'success',
                                'ticket_code' => 'TICKET123',
                                'entry_code_display' => 'ABCD-2345',
                                'scanner_post' => 'Gate A',
                                'scanned_at' => '2026-03-31T10:00:00Z',
                                'participant' => null,
                            ],
                        ],
                        'meta' => [
                            'page' => 1,
                            'per_page' => 20,
                            'total' => 21,
                            'has_more' => true,
                            'scope_date' => '2026-03-31',
                        ],
                    ],
                ]);

            $mock->shouldReceive('history')
                ->once()
                ->withArgs(function (Admin $operator, string $scannerPost, int $page, int $perPage) use ($scanner): bool {
                    return $operator->is($scanner)
                        && $scannerPost === 'Gate A'
                        && $page === 2
                        && $perPage === 20;
                })
                ->andReturn([
                    'items' => [
                        [
                            'scan_id' => 'scan-456',
                            'status' => 'duplicate',
                            'ticket_code' => 'TICKET456',
                            'entry_code_display' => 'WXYZ-6789',
                            'scanner_post' => 'Gate A',
                            'scanned_at' => '2026-03-31T10:05:00Z',
                            'participant' => null,
                        ],
                    ],
                    'meta' => [
                        'page' => 2,
                        'per_page' => 20,
                        'total' => 21,
                        'has_more' => false,
                        'scope_date' => '2026-03-31',
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
            ->getJson('/staff/dashboard?per_page=20')
            ->assertOk()
            ->assertJson([
                'stats' => [
                    'total_scans' => 3,
                    'successful_scans' => 2,
                    'duplicate_scans' => 1,
                    'invalid_scans' => 0,
                ],
                'history' => [
                    'items' => [
                        [
                            'scan_id' => 'scan-123',
                            'ticket_code' => 'TICKET123',
                        ],
                    ],
                    'meta' => [
                        'page' => 1,
                        'per_page' => 20,
                        'total' => 21,
                        'has_more' => true,
                        'scope_date' => '2026-03-31',
                    ],
                ],
            ]);

        $this->actingAs($scanner, 'admin')
            ->withSession(['staff.scanner_post' => 'Gate A'])
            ->getJson('/staff/history?page=2&per_page=20')
            ->assertOk()
            ->assertJson([
                'items' => [
                    [
                        'scan_id' => 'scan-456',
                        'ticket_code' => 'TICKET456',
                    ],
                ],
                'meta' => [
                    'page' => 2,
                    'per_page' => 20,
                    'total' => 21,
                    'has_more' => false,
                    'scope_date' => '2026-03-31',
                ],
            ]);

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
