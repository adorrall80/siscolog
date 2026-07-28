<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SessionTopicSubtype;
use App\Repositories\SessionTopicSubtypeRepository;
use InvalidArgumentException;
use RuntimeException;

final class SessionTopicSubtypeService
{
    private SessionTopicSubtypeRepository $subtypes;

    public function __construct()
    {
        $this->subtypes = new SessionTopicSubtypeRepository();
    }

    /**
     * @return array<string, SessionTopicSubtype[]>
     */
    public function grouped(): array
    {
        return $this->subtypes->grouped();
    }

    public function count(): int
    {
        $total = 0;

        foreach ($this->grouped() as $items) {
            $total += count($items);
        }

        return $total;
    }

    /**
     * @return SessionTopicSubtype[]
     */
    public function allActive(): array
    {
        return array_values(array_filter(
            $this->subtypes->all(),
            fn (SessionTopicSubtype $subtype): bool => $subtype->isActive
        ));
    }

    /**
     * @return SessionTopicSubtype[]
     */
    public function byType(int $topicTypeId, ?array $user = null): array
    {
        return $this->subtypes->byType($topicTypeId, $this->userId($user), $this->canSeeAll($user));
    }

    /**
     * @return SessionTopicSubtype[]
     */
    public function availableForType(int $topicTypeId, ?array $user = null): array
    {
        return $this->subtypes->availableForType($topicTypeId, $this->userId($user), $this->canSeeAll($user));
    }

    /**
     * @return array<int, SessionTopicSubtype[]>
     */
    public function activeGroupedByType(array $topicTypes, ?array $user = null): array
    {
        $grouped = [];

        foreach ($topicTypes as $topicType) {
            $grouped[(int) $topicType->id] = array_values(array_filter(
                $this->byType((int) $topicType->id, $user),
                fn (SessionTopicSubtype $subtype): bool => $subtype->isActive && ($subtype->relationIsActive ?? false)
            ));
        }

        return $grouped;
    }

    public function createAndRelate(int $topicTypeId, array $data, ?array $user = null): int
    {
        $data = $this->normalize($data);
        $data['created_by_user_id'] = $this->userId($user);
        $existing = $this->subtypes->findByCode($data['code']);
        $subtypeId = $existing?->id;

        if ($subtypeId === null) {
            $subtypeId = $this->subtypes->create($data);
        }

        $this->subtypes->relate($topicTypeId, $subtypeId, $this->userId($user), false);

        return $subtypeId;
    }

    /**
     * @return array{subtype_id: int, relation_id: int}
     */
    public function createAndRelateWithRelation(int $topicTypeId, array $data, ?array $user = null): array
    {
        $subtypeId = $this->createAndRelate($topicTypeId, $data, $user);

        return [
            'subtype_id' => $subtypeId,
            'relation_id' => $this->subtypes->relationId(
                $topicTypeId,
                $subtypeId,
                $this->userId($user)
            ),
        ];
    }

    public function relateExisting(int $topicTypeId, int $subtypeId, ?array $user = null): int
    {
        if ($subtypeId <= 0) {
            throw new InvalidArgumentException('Debes seleccionar un subtipo para asociar.');
        }

        return $this->subtypes->relate($topicTypeId, $subtypeId, $this->userId($user), false);
    }

    public function setActive(int $topicTypeId, int $relationId, bool $active, ?array $user = null): void
    {
        $this->assertAdministrator($user);
        $this->subtypes->setRelationActive($topicTypeId, $relationId, $active);
    }

    public function deleteRelation(int $topicTypeId, int $relationId, ?array $user = null): void
    {
        $this->assertAdministrator($user);

        if ($relationId <= 0) {
            throw new InvalidArgumentException('Debes seleccionar una relacion para quitar.');
        }

        $this->subtypes->deleteRelation($topicTypeId, $relationId);
    }

    public function setPublic(int $topicTypeId, int $relationId, bool $isPublic, ?array $user = null): void
    {
        $this->assertAdministrator($user);

        if ($relationId <= 0) {
            throw new InvalidArgumentException('Debes seleccionar una relacion valida.');
        }

        $this->subtypes->setRelationPublic($topicTypeId, $relationId, $isPublic);
    }

    private function normalize(array $data): array
    {
        $data['code'] = trim((string) ($data['code'] ?? ''));
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['description'] = trim((string) ($data['description'] ?? ''));
        $data['color'] = trim((string) ($data['color'] ?? ''));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($data['code'] === '') {
            $data['code'] = $this->slug($data['name']);
        }

        if ($data['name'] === '') {
            throw new InvalidArgumentException('El nombre del subtipo es obligatorio.');
        }

        if (!preg_match('/^[a-z0-9_\\-]+$/', $data['code'])) {
            throw new InvalidArgumentException('El codigo del subtipo solo puede usar minusculas, numeros, guion y guion bajo.');
        }

        return $data;
    }

    private function userId(?array $user): ?int
    {
        $id = (int) ($user['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function canSeeAll(?array $user): bool
    {
        return (string) ($user['role'] ?? '') === 'administrador';
    }

    private function assertAdministrator(?array $user): void
    {
        if (!$this->canSeeAll($user)) {
            throw new RuntimeException('Solo un administrador puede modificar relaciones generales o ajenas.');
        }
    }

    private function slug(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace(
            [' ', '/', '(', ')', '.', ',', 'á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['_', '_', '', '', '', '', 'a', 'e', 'i', 'o', 'u', 'n'],
            $value
        );

        return preg_replace('/[^a-z0-9_]+/', '', $value) ?: 'subtipo';
    }
}
