<?php

declare(strict_types=1);

namespace App\Models;

final class PatientConsent
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $patientId,
        public readonly ?int $createdByUserId,
        public readonly string $consentType,
        public readonly bool $accepted,
        public readonly bool $isActive,
        public readonly ?string $acceptedAt,
        public readonly ?string $revokedAt,
        public readonly string $documentVersion
    ) {
    }
}
