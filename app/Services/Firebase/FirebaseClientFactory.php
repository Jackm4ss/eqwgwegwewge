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
        if (empty($this->config['project_id']) || empty($this->config['credentials'])) {
            return null;
        }

        if (! file_exists($this->config['credentials'])) {
            return null;
        }

        return new FirestoreClient([
            'projectId' => $this->config['project_id'],
            'keyFilePath' => $this->config['credentials'],
            'database' => $this->config['database'] ?? '(default)',
        ]);
    }
}
