<?php

declare(strict_types=1);

namespace App\Models;

final class PatientFamilyRelationship
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $patientId,
        public readonly ?int $createdByUserId,
        public readonly string $fromNodeKey,
        public readonly string $fromNodeLabel,
        public readonly string $relationshipLabel,
        public readonly string $toNodeKey,
        public readonly string $toNodeLabel,
        public readonly bool $isActive
    ) {
    }
}
