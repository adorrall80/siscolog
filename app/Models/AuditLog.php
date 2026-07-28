<?php

declare(strict_types=1);

namespace App\Models;

final class AuditLog
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $userId,
        public readonly ?string $userName,
        public readonly string $action,
        public readonly string $entityType,
        public readonly ?int $entityId,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly array $metadata,
        public readonly bool $isActive,
        public readonly ?string $createdAt
    ) {
    }
}
