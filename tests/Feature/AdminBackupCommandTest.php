<?php

namespace Tests\Feature;

use App\Services\Admin\AdminAuditLogger;
use App\Services\Admin\AdminPanelService;
use App\Services\GoogleCloudStorage\GoogleCloudStorageUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class AdminBackupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_command_runs_in_local_only_mode(): void
    {
        config([
            'admin.backup.disk' => 'local',
            'admin.backup.path' => 'test-admin-backups',
        ]);

        $panelService = Mockery::mock(AdminPanelService::class);
        $panelService->shouldReceive('backupSnapshot')
            ->once()
            ->andReturn([
                'users' => [
                    ['user_id' => 'user-1', 'email' => 'one@example.com'],
                ],
                'tickets' => [],
                'scan_logs' => [],
                'admin_activity_logs' => [],
                'user_email_index' => [],
                'user_identity_index' => [],
            ]);

        $uploader = Mockery::mock(GoogleCloudStorageUploader::class);
        $uploader->shouldNotReceive('upload');

        $auditLogger = Mockery::mock(AdminAuditLogger::class);
        $auditLogger->shouldReceive('log')->once();

        $this->app->instance(AdminPanelService::class, $panelService);
        $this->app->instance(GoogleCloudStorageUploader::class, $uploader);
        $this->app->instance(AdminAuditLogger::class, $auditLogger);

        Artisan::call('admin:backup-data', ['--no-upload' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('Admin backup created:', $output);
    }

    public function test_schedule_list_contains_admin_backup_command(): void
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('admin:backup-data', $output);
    }
}
