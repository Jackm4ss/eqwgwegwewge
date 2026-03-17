<?php

namespace App\Contracts;

interface UserRepositoryInterface
{
    public function create(array $data): array;

    public function findByEmail(string $email): ?array;

    public function findById(string $id): ?array;

    public function update(string $id, array $data): array;
}
