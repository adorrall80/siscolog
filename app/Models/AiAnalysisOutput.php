<?php

declare(strict_types=1);

namespace App\Models;

final class AiAnalysisOutput
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $patientId,
        public readonly ?int $requestedBy,
        public readonly string $sourceType,
        public readonly ?string $sourceIds,
        public readonly ?string $model,
        public readonly string $promptVersion,
        public readonly string $outputJson,
        public readonly string $reviewStatus,
        public readonly bool $isActive,
        public readonly ?string $professionalNotes,
        public readonly ?int $reviewedBy,
        public readonly ?string $reviewedAt,
        public readonly ?string $createdAt
    ) {
    }

    public function output(): array
    {
        $decoded = json_decode($this->outputJson, true);

        return is_array($decoded) ? $decoded : [];
    }
}
