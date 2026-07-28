<?php

declare(strict_types=1);

namespace App\Models;

final class EmergencyContact
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $patientId,
        public readonly ?string $name,
        public readonly ?string $relationship,
        public readonly ?string $phone,
        public readonly ?string $email
    ) {
    }
}
