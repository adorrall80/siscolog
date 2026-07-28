<?php

declare(strict_types=1);

namespace App\Models;

final class Appointment
{
    public function __construct(
        public readonly int $id,
        public readonly int $patientId,
        public readonly string $patientCode,
        public readonly string $patientName,
        public readonly string $sessionDate,
        public readonly string $modality,
        public readonly string $riskLevel,
        public readonly ?string $reason,
        public readonly bool $isActive
    ) {
    }
}
