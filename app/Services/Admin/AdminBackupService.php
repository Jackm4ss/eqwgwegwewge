<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Services\GoogleCloudStorage\GoogleCloudStorageUploader;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class AdminBackupService
{
    public function __construct(
        private readonly AdminPanelService $adminPanel,
        private readonly GoogleCloudStorageUploader $uploader,
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function createBackup(?Admin $admin = null, ?string $ipAddress = null, bool $uploadToCloud = true): array
    {
        $diskName = (string) config('admin.backup.disk', 'admin_backups');
        $disk = Storage::disk($diskName);
        $timestamp = now()->format('Ymd-His');
        $basePath = trim((string) config('admin.backup.path', 'admin-backups'), '/').'/'.$timestamp;
        $collectionSnapshot = $this->adminPanel->backupSnapshot();
        $admins = Admin::query()
            ->orderBy('email')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'created_at', 'updated_at'])
            ->toArray();

        $manifest = [
            'generated_at' => now()->toISOString(),
            'collections' => array_map('count', $collectionSnapshot),
            'admins_count' => count($admins),
            'app_env' => config('app.env'),
        ];

        foreach ($collectionSnapshot as $collection => $rows) {
            $disk->put(
                $basePath.'/collections/'.$collection.'.json',
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        }

        $disk->put(
            $basePath.'/admins.json',
            json_encode($admins, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
        $disk->put(
            $basePath.'/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        $archiveRelativePath = $basePath.'.zip';
        $archiveAbsolutePath = $disk->path($archiveRelativePath);
        $archiveDirectory = dirname($archiveAbsolutePath);

        if (! is_dir($archiveDirectory) && ! mkdir($archiveDirectory, 0777, true) && ! is_dir($archiveDirectory)) {
            throw new RuntimeException('Unable to create backup archive directory.');
        }

        $zip = new ZipArchive;

        if ($zip->open($archiveAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create backup archive.');
        }

        foreach ($collectionSnapshot as $collection => $_rows) {
            $relativePath = $basePath.'/collections/'.$collection.'.json';
            $zip->addFile($disk->path($relativePath), 'collections/'.$collection.'.json');
        }

        $zip->addFile($disk->path($basePath.'/admins.json'), 'admins.json');
        $zip->addFile($disk->path($basePath.'/manifest.json'), 'manifest.json');
        $zip->close();

        $uploadResult = [
            'uploaded' => false,
            'reason' => 'upload-disabled',
        ];

        if ($uploadToCloud) {
            $objectName = trim((string) config('admin.backup.prefix', 'event-system'), '/').'/'.$timestamp.'/admin-backup.zip';
            $uploadResult = $this->uploader->upload($archiveAbsolutePath, $objectName);
        }

        $result = [
            'disk' => $diskName,
            'base_path' => $basePath,
            'archive_path' => $archiveRelativePath,
            'upload' => $uploadResult,
            'manifest' => $manifest,
        ];

        $this->auditLogger->log(
            $admin,
            'backup',
            'backup',
            $archiveRelativePath,
            $result,
            $ipAddress,
        );

        return $result;
    }
}
