<?php

namespace App\Contracts;

interface UserRepositoryInterface
{
    public function create(array $data): array;

    public function findByEmail(string $email): ?array;

    public function findById(string $id): ?array;

    public function findByIdentityDocument(string $identityType, string $identityCountry, string $identityNumber): ?array;

    public function update(string $id, array $data): array;

    public function activateAndIssueTicket(string $id, array $ticketData): array;

    public function findTicketById(string $ticketId): ?array;

    public function findTicketByUserId(string $userId): ?array;
}
