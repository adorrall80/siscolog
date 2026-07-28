<?php

declare(strict_types=1);

namespace App\Models;

final class PsychometricResult
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $patientId,
        public readonly int $instrumentId,
        public readonly string $instrumentCode,
        public readonly string $instrumentName,
        public readonly int $minScore,
        public readonly int $maxScore,
        public readonly ?int $cautionCutoff,
        public readonly ?int $criticalCutoff,
        public readonly ?int $professionalId,
        public readonly ?string $professionalName,
        public readonly string $appliedAt,
        public readonly int $score,
        public readonly ?string $interpretation,
        public readonly ?string $professionalNotes,
        public readonly bool $isActive,
        public readonly ?string $createdAt
    ) {
    }

    public function severityTone(): string
    {
        if ($this->criticalCutoff !== null && $this->score >= $this->criticalCutoff) {
            return 'red';
        }

        if ($this->cautionCutoff !== null && $this->score >= $this->cautionCutoff) {
            return 'amber';
        }

        return 'green';
    }
}
