<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly string $status,
        public readonly bool $isActive,
        public readonly string $passwordHash = '',
        public readonly string $profession = '',
        public readonly string $specialty = '',
        public readonly string $professionalLicense = '',
        public readonly string $phone = '',
        public readonly string $clinicName = '',
        public readonly string $clinicAddress = '',
        public readonly string $professionalBio = ''
    ) {
    }
}
