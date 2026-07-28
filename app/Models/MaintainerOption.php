<?php

declare(strict_types=1);

namespace App\Models;

final class MaintainerOption
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $table,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $color,
        public readonly int $sortOrder,
        public readonly bool $isActive,
        public readonly ?int $createdByUserId = null,
        public readonly ?bool $isPublic = null,
        public readonly ?string $createdByUserName = null
    ) {
    }
}
