<?php

namespace App\Services\Firebase;

use Google\Cloud\Firestore\FirestoreClient;

class FirebaseClientFactory
{
    public function __construct(private readonly array $config)
    {
    }

    public function make(): ?FirestoreClient
    {
        $credentialsPath = FirebasePathResolver::credentialsPath(
            (string) ($this->config['credentials'] ?? '')
        );

        if (empty($this->config['project_id']) || $credentialsPath === null) {
            return null;
        }

        if (! file_exists($credentialsPath)) {
            return null;
        }

        return new FirestoreClient([
            'projectId' => $this->config['project_id'],
            'keyFilePath' => $credentialsPath,
            'database' => $this->config['database'] ?? FirestoreClient::DEFAULT_DATABASE,
        ]);
    }
}
