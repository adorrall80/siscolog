<?php

declare(strict_types=1);

namespace App\Models;

final class SessionTopicSubtype
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $color,
        public readonly int $sortOrder,
        public readonly bool $isActive,
        public readonly ?int $topicTypeId = null,
        public readonly ?string $topicTypeCode = null,
        public readonly ?string $topicTypeName = null,
        public readonly ?int $relationId = null,
        public readonly ?bool $relationIsActive = null
    ) {
    }
}
