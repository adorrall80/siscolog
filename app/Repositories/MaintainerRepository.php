<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\MaintainerOption;
use Core\Database;
use InvalidArgumentException;
use PDO;

final class MaintainerRepository
{
    private const TABLES = [
        'patient_statuses',
        'session_modalities',
        'risk_levels',
        'consent_types',
        'session_participant_types',
        'session_topic_types',
        'user_roles',
        'user_statuses',
        'ai_review_statuses',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return MaintainerOption[]
     */
    public function all(string $table): array
    {
        $this->guardTable($table);

        $statement = $this->db->query($table === 'session_topic_types'
            ? 'SELECT t.*, u.name AS created_by_user_name
               FROM session_topic_types t
               LEFT JOIN users u ON u.id = t.created_by_user_id
               ORDER BY t.sort_order ASC, t.name ASC'
            : "SELECT * FROM {$table} ORDER BY sort_order ASC, name ASC");

        return array_map(
            fn (array $row): MaintainerOption => $this->map($table, $row),
            $statement->fetchAll()
        );
    }

    /**
     * @return MaintainerOption[]
     */
    public function active(string $table): array
    {
        $this->guardTable($table);

        $statement = $this->db->query($table === 'session_topic_types'
            ? 'SELECT t.*, u.name AS created_by_user_name
               FROM session_topic_types t
               LEFT JOIN users u ON u.id = t.created_by_user_id
               WHERE t.is_active = 1
               ORDER BY t.sort_order ASC, t.name ASC'
            : "SELECT * FROM {$table} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");

        return array_map(
            fn (array $row): MaintainerOption => $this->map($table, $row),
            $statement->fetchAll()
        );
    }

    /**
     * @return MaintainerOption[]
     */
    public function activeVisible(string $table, ?int $createdByUserId, bool $includeAll = false): array
    {
        $this->guardTable($table);

        if ($table !== 'session_topic_types' || $includeAll) {
            return $this->active($table);
        }

        $statement = $this->db->prepare(
            'SELECT t.*, u.name AS created_by_user_name
             FROM session_topic_types t
             LEFT JOIN users u ON u.id = t.created_by_user_id
             WHERE t.is_active = 1
               AND (t.is_public = 1 OR t.created_by_user_id = :created_by_user_id)
             ORDER BY t.is_public DESC, t.sort_order ASC, t.name ASC'
        );
        $statement->execute(['created_by_user_id' => $createdByUserId ?? 0]);

        return array_map(
            fn (array $row): MaintainerOption => $this->map($table, $row),
            $statement->fetchAll()
        );
    }

    public function existsActive(string $table, string $code): bool
    {
        $this->guardTable($table);

        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE code = :code AND is_active = 1"
        );
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function existsCode(string $table, string $code, ?int $exceptId = null): bool
    {
        $this->guardTable($table);

        $sql = "SELECT COUNT(*) FROM {$table} WHERE code = :code";
        $params = ['code' => $code];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function defaultCode(string $table): ?string
    {
        $this->guardTable($table);

        $statement = $this->db->query(
            "SELECT code FROM {$table} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC LIMIT 1"
        );
        $value = $statement->fetchColumn();

        return $value ? (string) $value : null;
    }

    public function create(string $table, array $data): int
    {
        $this->guardTable($table);

        $this->db->beginTransaction();

        if ($table === 'session_topic_types') {
            $statement = $this->db->prepare(
                "INSERT INTO {$table} (
                    created_by_user_id,
                    code,
                    name,
                    description,
                    color,
                    sort_order,
                    is_public,
                    is_active,
                    created_at,
                    updated_at
                 )
                 VALUES (
                    :created_by_user_id,
                    :code,
                    :name,
                    :description,
                    :color,
                    :sort_order,
                    :is_public,
                    :is_active,
                    NOW(),
                    NOW()
                 )"
            );
        } else {
            $statement = $this->db->prepare(
                "INSERT INTO {$table} (
                code,
                name,
                description,
                color,
                sort_order,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :code,
                :name,
                :description,
                :color,
                :sort_order,
                :is_active,
                NOW(),
                NOW()
             )"
            );
        }

        $params = [
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'color' => $data['color'] ?: null,
            'sort_order' => (int) $data['sort_order'],
            'is_active' => (int) $data['is_active'],
        ];

        if ($table === 'session_topic_types') {
            $params['created_by_user_id'] = ($data['created_by_user_id'] ?? null) ?: null;
            $params['is_public'] = !empty($data['is_public']) ? 1 : 0;
        }

        $statement->execute($params);

        $id = (int) $this->db->lastInsertId();

        $this->db->commit();

        return $id;
    }

    public function find(string $table, int $id): ?MaintainerOption
    {
        $this->guardTable($table);

        $statement = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->map($table, $row) : null;
    }

    public function findByCode(string $table, string $code): ?MaintainerOption
    {
        $this->guardTable($table);

        $statement = $this->db->prepare("SELECT * FROM {$table} WHERE code = :code LIMIT 1");
        $statement->execute(['code' => $code]);
        $row = $statement->fetch();

        return $row ? $this->map($table, $row) : null;
    }

    public function update(string $table, int $id, array $data): void
    {
        $this->guardTable($table);

        $this->db->beginTransaction();

        $statement = $this->db->prepare(
            "UPDATE {$table}
             SET code = :code,
                 name = :name,
                 description = :description,
                 color = :color,
                 sort_order = :sort_order,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id"
        );

        $params = [
            'id' => $id,
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'color' => $data['color'] ?: null,
            'sort_order' => (int) $data['sort_order'],
            'is_active' => (int) $data['is_active'],
        ];

        $statement->execute($params);

        $this->db->commit();
    }

    public function setActive(string $table, int $id, bool $active): void
    {
        $this->guardTable($table);

        $statement = $this->db->prepare(
            "UPDATE {$table} SET is_active = :is_active, updated_at = NOW() WHERE id = :id"
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public function setTopicTypePublic(int $id, bool $isPublic): void
    {
        $statement = $this->db->prepare(
            'UPDATE session_topic_types
             SET is_public = :is_public, updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_public' => $isPublic ? 1 : 0,
        ]);
    }

    /**
     * @return string[]
     */
    public function tables(): array
    {
        return self::TABLES;
    }

    private function guardTable(string $table): void
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException('Tabla mantenedora no permitida.');
        }
    }

    private function map(string $table, array $row): MaintainerOption
    {
        return new MaintainerOption(
            (int) $row['id'],
            $table,
            (string) $row['code'],
            (string) $row['name'],
            $row['description'],
            $row['color'],
            (int) $row['sort_order'],
            (bool) $row['is_active'],
            isset($row['created_by_user_id']) ? (int) $row['created_by_user_id'] : null,
            array_key_exists('is_public', $row) ? (bool) $row['is_public'] : null,
            isset($row['created_by_user_name']) ? (string) $row['created_by_user_name'] : null
        );
    }
}
