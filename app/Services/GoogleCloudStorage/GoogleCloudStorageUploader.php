<?php

namespace App\Services\GoogleCloudStorage;

use App\Services\Firebase\FirebasePathResolver;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCloudStorageUploader
{
    public function upload(string $localFilePath, string $objectName, string $contentType = 'application/zip'): array
    {
        $bucket = trim((string) config('admin.backup.bucket', ''));

        if ($bucket === '') {
            return [
                'uploaded' => false,
                'reason' => 'bucket-not-configured',
            ];
        }

        if (! is_file($localFilePath)) {
            throw new RuntimeException('Backup archive file was not found.');
        }

        $response = Http::withToken($this->accessToken())
            ->withHeaders([
                'Content-Type' => $contentType,
            ])
            ->send(
                'POST',
                sprintf(
                    'https://storage.googleapis.com/upload/storage/v1/b/%s/o?uploadType=media&name=%s',
                    rawurlencode($bucket),
                    rawurlencode($objectName),
                ),
                [
                    'body' => (string) file_get_contents($localFilePath),
                ],
            );

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'error.message', 'Unknown upload error.');
            throw new RuntimeException('Failed to upload backup to cloud storage. '.$message);
        }

        return [
            'uploaded' => true,
            'bucket' => $bucket,
            'object' => (string) data_get($response->json(), 'name', $objectName),
            'media_link' => (string) data_get($response->json(), 'mediaLink', ''),
        ];
    }

    private function accessToken(): string
    {
        $credentialsPath = FirebasePathResolver::credentialsPath(
            (string) config('firebase.credentials')
        );

        if ($credentialsPath === null || ! is_file($credentialsPath)) {
            throw new RuntimeException('Google Cloud credentials are not configured.');
        }

        $decoded = json_decode((string) file_get_contents($credentialsPath), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Google Cloud credentials file is invalid.');
        }

        $credentials = new ServiceAccountCredentials(
            [
                'https://www.googleapis.com/auth/devstorage.read_write',
                'https://www.googleapis.com/auth/cloud-platform',
            ],
            $decoded,
        );

        $token = $credentials->fetchAuthToken();
        $accessToken = (string) ($token['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Unable to fetch Google Cloud access token.');
        }

        return $accessToken;
    }
}
