<?php

namespace App\Services\Admin;

use App\Models\Admin;

class AdminAuditLogger
{
    public function __construct(
        private readonly AdminFirestoreRepository $repository,
    ) {}

    public function log(
        ?Admin $admin,
        string $actionType,
        ?string $targetType = null,
        ?string $targetId = null,
        array $metadata = [],
        ?string $ipAddress = null,
    ): void {
        try {
            $this->repository->appendAdminActivityLog([
                'admin_id' => $admin?->getKey(),
                'admin_email' => $admin?->email,
                'action_type' => $actionType,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'metadata' => $metadata,
                'ip_address' => $ipAddress,
            ]);
        } catch (\Throwable) {
            // Audit logging is best-effort and should not block the main workflow.
        }
    }
}
