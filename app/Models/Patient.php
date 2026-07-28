<?php

declare(strict_types=1);

namespace App\Models;

final class Patient
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $code,
        public readonly string $fullName,
        public readonly ?string $birthDate,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $status,
        public readonly bool $isActive,
        public readonly ?int $assignedProfessionalId = null,
        public readonly ?EmergencyContact $emergencyContact = null
    ) {
    }
}
