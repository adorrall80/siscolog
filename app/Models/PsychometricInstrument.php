<?php

declare(strict_types=1);

namespace App\Models;

final class PsychometricInstrument
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly int $minScore,
        public readonly int $maxScore,
        public readonly ?int $cautionCutoff,
        public readonly ?int $criticalCutoff,
        public readonly bool $isActive
    ) {
    }
}
