<?php

declare(strict_types=1);

namespace App\Models;

final class ClinicalSession
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $patientId,
        public readonly string $sessionDate,
        public readonly string $modality,
        public readonly ?string $reason,
        public readonly ?string $subjectiveNote,
        public readonly ?string $objectiveNote,
        public readonly ?string $clinicalImpression,
        public readonly string $riskLevel,
        public readonly ?string $agreements,
        public readonly ?string $nextSteps,
        public readonly bool $isActive,
        public readonly ?string $updatedAt,
        public readonly array $participants = [],
        public readonly array $topics = []
    ) {
    }
}
