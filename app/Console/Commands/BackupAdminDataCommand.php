<?php

namespace App\Console\Commands;

use App\Services\Admin\AdminBackupService;
use Illuminate\Console\Command;

class BackupAdminDataCommand extends Command
{
    protected $signature = 'admin:backup-data {--no-upload : Skip cloud upload and keep backup locally only}';

    protected $description = 'Create an admin data backup snapshot and upload it to cloud storage when configured.';

    public function handle(AdminBackupService $backupService): int
    {
        $result = $backupService->createBackup(
            admin: null,
            ipAddress: null,
            uploadToCloud: ! $this->option('no-upload'),
        );

        $this->info('Admin backup created: '.$result['archive_path']);

        if (($result['upload']['uploaded'] ?? false) === true) {
            $this->info('Uploaded to cloud object: '.$result['upload']['object']);
        } else {
            $this->warn('Cloud upload skipped: '.($result['upload']['reason'] ?? 'unknown'));
        }

        return self::SUCCESS;
    }
}
