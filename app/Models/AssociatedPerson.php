<?php

declare(strict_types=1);

namespace App\Models;

final class AssociatedPerson
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $patientId,
        public readonly ?int $createdByUserId,
        public readonly string $participantType,
        public readonly string $displayName,
        public readonly bool $isActive,
        public readonly bool $hasParticipated = false
    ) {
    }
}
