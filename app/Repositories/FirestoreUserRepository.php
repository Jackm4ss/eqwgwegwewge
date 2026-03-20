<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Services\Firebase\FirebaseClientFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class FirestoreUserRepository implements UserRepositoryInterface
{
    private string $fallbackPath;

    public function __construct(private readonly FirebaseClientFactory $factory)
    {
        $this->fallbackPath = storage_path('app/local_firestore_users.json');
    }

    public function create(array $data): array
    {
        $data['user_id'] = $data['user_id'] ?? (string) str()->uuid();
        $data['created_at'] = now()->toISOString();
        $data['updated_at'] = now()->toISOString();

        $client = $this->factory->make();
        if ($client) {
            $client->collection(config('firebase.users_collection'))->document($data['user_id'])->set($data);
            return $data;
        }

        if (! $this->shouldUseFallback()) {
            Log::error('Firestore unavailable and local fallback is disabled.');
            throw new \RuntimeException('User storage unavailable.');
        }

        $users = $this->loadFallback();
        $users[$data['user_id']] = $data;
        $this->saveFallback($users);
        Log::warning('Firestore unavailable, using fallback file storage.');

        return $data;
    }

    public function findByEmail(string $email): ?array
    {
        $client = $this->factory->make();
        if ($client) {
            $documents = $client->collection(config('firebase.users_collection'))->where('email', '=', strtolower($email))->documents();
            foreach ($documents as $document) {
                if ($document->exists()) {
                    return $document->data();
                }
            }

            return null;
        }

        if (! $this->shouldUseFallback()) {
            return null;
        }

        foreach ($this->loadFallback() as $user) {
            if (($user['email'] ?? null) === strtolower($email)) {
                return $user;
            }
        }

        return null;
    }

    public function findByIdentityNumber(string $identityNumber): ?array
    {
        $client = $this->factory->make();
        if ($client) {
            $documents = $client->collection(config('firebase.users_collection'))->where('identity_number', '=', $identityNumber)->documents();
            foreach ($documents as $document) {
                if ($document->exists()) {
                    return $document->data();
                }
            }

            return null;
        }

        if (! $this->shouldUseFallback()) {
            return null;
        }

        foreach ($this->loadFallback() as $user) {
            if (($user['identity_number'] ?? null) === $identityNumber) {
                return $user;
            }
        }

        return null;
    }

    public function findById(string $id): ?array
    {
        $client = $this->factory->make();
        if ($client) {
            $snapshot = $client->collection(config('firebase.users_collection'))->document($id)->snapshot();
            return $snapshot->exists() ? $snapshot->data() : null;
        }

        if (! $this->shouldUseFallback()) {
            return null;
        }

        return $this->loadFallback()[$id] ?? null;
    }

    public function update(string $id, array $data): array
    {
        $existing = $this->findById($id);
        if (! $existing) {
            throw new \RuntimeException('User not found');
        }

        $payload = array_merge($existing, Arr::except($data, ['user_id', 'created_at']));
        $payload['updated_at'] = now()->toISOString();

        $client = $this->factory->make();
        if ($client) {
            $client->collection(config('firebase.users_collection'))->document($id)->set($payload);
            return $payload;
        }

        if (! $this->shouldUseFallback()) {
            Log::error('Firestore unavailable and local fallback is disabled.');
            throw new \RuntimeException('User storage unavailable.');
        }

        $users = $this->loadFallback();
        $users[$id] = $payload;
        $this->saveFallback($users);

        return $payload;
    }

    private function loadFallback(): array
    {
        if (! file_exists($this->fallbackPath)) {
            return [];
        }

        return json_decode(file_get_contents($this->fallbackPath), true) ?: [];
    }

    private function saveFallback(array $users): void
    {
        file_put_contents($this->fallbackPath, json_encode($users, JSON_PRETTY_PRINT));
    }

    private function shouldUseFallback(): bool
    {
        return (bool) config('firebase.fallback_local', false);
    }
}
