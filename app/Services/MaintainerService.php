<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaintainerOption;
use App\Repositories\MaintainerRepository;
use InvalidArgumentException;
use RuntimeException;

final class MaintainerService
{
    public const PATIENT_STATUSES = 'patient_statuses';
    public const SESSION_MODALITIES = 'session_modalities';
    public const RISK_LEVELS = 'risk_levels';
    public const CONSENT_TYPES = 'consent_types';
    public const SESSION_PARTICIPANT_TYPES = 'session_participant_types';
    public const SESSION_TOPIC_TYPES = 'session_topic_types';
    public const USER_ROLES = 'user_roles';
    public const USER_STATUSES = 'user_statuses';
    public const AI_REVIEW_STATUSES = 'ai_review_statuses';

    private MaintainerRepository $maintainers;

    public function __construct()
    {
        $this->maintainers = new MaintainerRepository();
    }

    /**
     * @return MaintainerOption[]
     */
    public function all(string $table): array
    {
        return $this->maintainers->all($table);
    }

    /**
     * @return MaintainerOption[]
     */
    public function filtered(string $table, string $filter, string $search = ''): array
    {
        $items = $this->all($table);

        $filtered = match ($filter) {
            'active' => array_values(array_filter($items, fn (MaintainerOption $item): bool => $item->isActive)),
            'inactive' => array_values(array_filter($items, fn (MaintainerOption $item): bool => !$item->isActive)),
            default => $items,
        };

        $needle = $this->normalizeSearch($search);

        if ($needle === '') {
            return $filtered;
        }

        return array_values(array_filter($filtered, function (MaintainerOption $item) use ($needle): bool {
            $haystack = strtolower($item->code . ' ' . $item->name . ' ' . (string) $item->description);

            return str_contains($haystack, $needle);
        }));
    }

    public function normalizeFilter(string $filter): string
    {
        $aliases = [
            'todos' => 'all',
            'activos' => 'active',
            'inactivos' => 'inactive',
        ];

        $filter = $aliases[$filter] ?? $filter;

        return in_array($filter, ['all', 'active', 'inactive'], true) ? $filter : 'all';
    }

    public function normalizeSearch(string $search): string
    {
        return strtolower(trim($search));
    }

    /**
     * @return MaintainerOption[]
     */
    public function active(string $table): array
    {
        return $this->maintainers->active($table);
    }

    /**
     * @return array<string, MaintainerOption[]>
     */
    public function allGrouped(): array
    {
        $grouped = [];

        foreach ($this->tables() as $table) {
            $grouped[$table] = $this->all($table);
        }

        return $grouped;
    }

    /**
     * @return array<string, array{total: int, active: int, inactive: int}>
     */
    public function summaries(): array
    {
        $summaries = [];

        foreach ($this->tables() as $table) {
            $summaries[$table] = $this->summaryFor($table);
        }

        return $summaries;
    }

    /**
     * @return array{total: int, active: int, inactive: int}
     */
    public function summaryFor(string $table): array
    {
        $items = $this->all($table);
        $active = 0;

        foreach ($items as $item) {
            if ($item->isActive) {
                $active++;
            }
        }

        $total = count($items);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
        ];
    }

    /**
     * @return string[]
     */
    public function tables(): array
    {
        return $this->maintainers->tables();
    }

    public function isValid(string $table, string $code): bool
    {
        return $this->maintainers->existsActive($table, $code);
    }

    public function defaultCode(string $table): string
    {
        return $this->maintainers->defaultCode($table) ?? '';
    }

    public function create(string $table, array $data): void
    {
        $this->validate($table, $data);
        $this->maintainers->create($table, $data);
    }

    public function findOrCreateSessionTopicType(string $name): MaintainerOption
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('El tipo de tema es obligatorio si registras un subtipo.');
        }

        $code = $this->slug($name);
        $existing = $this->maintainers->findByCode(self::SESSION_TOPIC_TYPES, $code);

        if ($existing !== null) {
            return $existing;
        }

        foreach ($this->all(self::SESSION_TOPIC_TYPES) as $option) {
            if ($this->slug($option->name) === $code) {
                return $option;
            }
        }

        $id = $this->maintainers->create(self::SESSION_TOPIC_TYPES, [
            'code' => $code,
            'name' => $name,
            'description' => '',
            'color' => 'blue',
            'sort_order' => 0,
            'is_active' => 1,
        ]);

        return $this->find(self::SESSION_TOPIC_TYPES, $id);
    }

    public function find(string $table, int $id): MaintainerOption
    {
        $item = $this->maintainers->find($table, $id);

        if ($item === null) {
            throw new RuntimeException('Valor mantenedor no encontrado.');
        }

        return $item;
    }

    public function update(string $table, int $id, array $data): void
    {
        $this->validate($table, $data, $id);
        $this->maintainers->update($table, $id, $data);
    }

    public function setActive(string $table, int $id, bool $active): void
    {
        $this->find($table, $id);
        $this->maintainers->setActive($table, $id, $active);
    }

    public function labelFor(string $table): string
    {
        return $this->labels()[$table] ?? $table;
    }

    public function resolveTable(string $tableOrSlug): string
    {
        return array_flip($this->slugs())[$tableOrSlug] ?? $tableOrSlug;
    }

    public function slugFor(string $table): string
    {
        return $this->slugs()[$table] ?? $table;
    }

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return [
            self::PATIENT_STATUSES => 'Estados de paciente',
            self::SESSION_MODALITIES => 'Modalidades de sesion',
            self::RISK_LEVELS => 'Niveles de riesgo',
            self::CONSENT_TYPES => 'Tipos de consentimiento',
            self::SESSION_PARTICIPANT_TYPES => 'Participantes de sesion',
            self::SESSION_TOPIC_TYPES => 'Tipos de tema de sesion',
            self::USER_ROLES => 'Roles de usuario',
            self::USER_STATUSES => 'Estados de usuario',
            self::AI_REVIEW_STATUSES => 'Estados de revision IA',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function slugs(): array
    {
        return [
            self::PATIENT_STATUSES => 'estados-paciente',
            self::SESSION_MODALITIES => 'modalidades-sesion',
            self::RISK_LEVELS => 'niveles-riesgo',
            self::CONSENT_TYPES => 'tipos-consentimiento',
            self::SESSION_PARTICIPANT_TYPES => 'participantes-sesion',
            self::SESSION_TOPIC_TYPES => 'tipos-tema-sesion',
            self::USER_ROLES => 'roles-usuario',
            self::USER_STATUSES => 'estados-usuario',
            self::AI_REVIEW_STATUSES => 'estados-revision-ia',
        ];
    }

    private function validate(string $table, array $data, ?int $exceptId = null): void
    {
        if (!in_array($table, $this->tables(), true)) {
            throw new InvalidArgumentException('La tabla mantenedora no es valida.');
        }

        if (trim((string) ($data['code'] ?? '')) === '') {
            throw new InvalidArgumentException('El codigo es obligatorio.');
        }

        if (!preg_match('/^[a-z0-9_\\-]+$/', (string) $data['code'])) {
            throw new InvalidArgumentException('El codigo solo puede usar minusculas, numeros, guion y guion bajo.');
        }

        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new InvalidArgumentException('El nombre es obligatorio.');
        }

        if ($this->maintainers->existsCode($table, (string) $data['code'], $exceptId)) {
            throw new InvalidArgumentException('Ya existe un valor con ese codigo en este mantenedor.');
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

        return preg_replace('/[^a-z0-9_]+/', '', $value) ?: 'valor';
    }
}
