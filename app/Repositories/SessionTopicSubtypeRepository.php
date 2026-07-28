<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SessionTopicSubtype;
use Core\Database;
use PDO;

final class SessionTopicSubtypeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return SessionTopicSubtype[]
     */
    public function all(): array
    {
        $statement = $this->db->query(
            'SELECT * FROM session_topic_subtypes
             ORDER BY sort_order ASC, name ASC'
        );

        return array_map(
            fn (array $row): SessionTopicSubtype => $this->mapSubtype($row),
            $statement->fetchAll()
        );
    }

    /**
     * @return SessionTopicSubtype[]
     */
    public function availableForType(int $topicTypeId, ?int $createdByUserId = null, bool $includeAll = false): array
    {
        $scopeSql = '';
        $params = ['topic_type_id' => $topicTypeId];

        if (!$includeAll) {
            $scopeSql = ' AND (r.is_public = 1 OR r.created_by_user_id = :created_by_user_id)';
            $params['created_by_user_id'] = $createdByUserId ?? 0;
        }

        $statement = $this->db->prepare(
            'SELECT s.*
             FROM session_topic_subtypes s
             WHERE s.is_active = 1
               AND NOT EXISTS (
                    SELECT 1
                    FROM session_topic_type_subtypes r
                    WHERE r.session_topic_type_id = :topic_type_id
                      AND r.session_topic_subtype_id = s.id
                      ' . $scopeSql . '
               )
             ORDER BY s.name ASC'
        );
        $statement->execute($params);

        return array_map(
            fn (array $row): SessionTopicSubtype => $this->mapSubtype($row),
            $statement->fetchAll()
        );
    }

    /**
     * @return array<string, SessionTopicSubtype[]>
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->allRelated() as $subtype) {
            $typeName = $subtype->topicTypeName ?? 'Sin tipo';
            $grouped[$typeName] ??= [];
            $grouped[$typeName][] = $subtype;
        }

        return $grouped;
    }

    /**
     * @return SessionTopicSubtype[]
     */
    public function allRelated(): array
    {
        $statement = $this->db->query(
            'SELECT
                s.*,
                t.id AS topic_type_id,
                t.code AS topic_type_code,
                t.name AS topic_type_name,
                r.id AS relation_id,
                r.is_active AS relation_is_active,
                r.created_by_user_id AS relation_created_by_user_id,
                r.is_public AS relation_is_public,
                ru.name AS relation_created_by_user_name
             FROM session_topic_type_subtypes r
             INNER JOIN session_topic_subtypes s ON s.id = r.session_topic_subtype_id
             INNER JOIN session_topic_types t ON t.id = r.session_topic_type_id
             LEFT JOIN users ru ON ru.id = r.created_by_user_id
             ORDER BY t.sort_order ASC, t.name ASC, s.sort_order ASC, s.name ASC'
        );

        return array_map(
            fn (array $row): SessionTopicSubtype => $this->mapRelated($row),
            $statement->fetchAll()
        );
    }

    /**
     * @return SessionTopicSubtype[]
     */
    public function byType(int $topicTypeId, ?int $createdByUserId = null, bool $includeAll = false): array
    {
        $scopeSql = '';
        $params = ['topic_type_id' => $topicTypeId];

        if (!$includeAll) {
            $scopeSql = ' AND (r.is_public = 1 OR r.created_by_user_id = :created_by_user_id)';
            $params['created_by_user_id'] = $createdByUserId ?? 0;
        }

        $statement = $this->db->prepare(
            'SELECT
                s.*,
                t.id AS topic_type_id,
                t.code AS topic_type_code,
                t.name AS topic_type_name,
                r.id AS relation_id,
                r.is_active AS relation_is_active,
                r.created_by_user_id AS relation_created_by_user_id,
                r.is_public AS relation_is_public,
                ru.name AS relation_created_by_user_name
             FROM session_topic_type_subtypes r
             INNER JOIN session_topic_subtypes s ON s.id = r.session_topic_subtype_id
             INNER JOIN session_topic_types t ON t.id = r.session_topic_type_id
             LEFT JOIN users ru ON ru.id = r.created_by_user_id
             WHERE r.session_topic_type_id = :topic_type_id
             ' . $scopeSql . '
             ORDER BY r.is_public DESC, s.sort_order ASC, s.name ASC'
        );
        $statement->execute($params);

        return array_map(
            fn (array $row): SessionTopicSubtype => $this->mapRelated($row),
            $statement->fetchAll()
        );
    }

    public function findByCode(string $code): ?SessionTopicSubtype
    {
        $statement = $this->db->prepare('SELECT * FROM session_topic_subtypes WHERE code = :code LIMIT 1');
        $statement->execute(['code' => $code]);
        $row = $statement->fetch();

        return $row ? $this->mapSubtype($row) : null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO session_topic_subtypes (
                created_by_user_id,
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
                :created_by_user_id,
                :code,
                :name,
                :description,
                :color,
                :sort_order,
                1,
                NOW(),
                NOW()
             )'
        );
        $statement->execute([
            'created_by_user_id' => ($data['created_by_user_id'] ?? null) ?: null,
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'color' => $data['color'] ?: null,
            'sort_order' => (int) $data['sort_order'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function relate(
        int $topicTypeId,
        int $subtypeId,
        ?int $createdByUserId = null,
        bool $isPublic = false
    ): int
    {
        $relationId = $this->visibleRelationId($topicTypeId, $subtypeId, $createdByUserId);

        if ($relationId > 0) {
            $this->setRelationActive($topicTypeId, $relationId, true);

            return $relationId;
        }

        $statement = $this->db->prepare(
            'INSERT INTO session_topic_type_subtypes (
                created_by_user_id,
                session_topic_type_id,
                session_topic_subtype_id,
                is_public,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :created_by_user_id,
                :topic_type_id,
                :subtype_id,
                :is_public,
                1,
                NOW(),
                NOW()
             )'
        );
        $statement->execute([
            'created_by_user_id' => $createdByUserId,
            'topic_type_id' => $topicTypeId,
            'subtype_id' => $subtypeId,
            'is_public' => $isPublic ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function setRelationActive(int $topicTypeId, int $relationId, bool $active): void
    {
        $statement = $this->db->prepare(
            'UPDATE session_topic_type_subtypes
             SET is_active = :is_active, updated_at = NOW()
             WHERE id = :id AND session_topic_type_id = :topic_type_id'
        );
        $statement->execute([
            'id' => $relationId,
            'topic_type_id' => $topicTypeId,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public function deleteRelation(int $topicTypeId, int $relationId): void
    {
        $statement = $this->db->prepare(
            'DELETE FROM session_topic_type_subtypes
             WHERE id = :id AND session_topic_type_id = :topic_type_id'
        );
        $statement->execute([
            'id' => $relationId,
            'topic_type_id' => $topicTypeId,
        ]);
    }

    public function setRelationPublic(int $topicTypeId, int $relationId, bool $isPublic): void
    {
        $statement = $this->db->prepare(
            'UPDATE session_topic_type_subtypes
             SET is_public = :is_public, updated_at = NOW()
             WHERE id = :id AND session_topic_type_id = :topic_type_id'
        );
        $statement->execute([
            'id' => $relationId,
            'topic_type_id' => $topicTypeId,
            'is_public' => $isPublic ? 1 : 0,
        ]);
    }

    public function relationId(int $topicTypeId, int $subtypeId, ?int $createdByUserId = null): int
    {
        return $this->visibleRelationId($topicTypeId, $subtypeId, $createdByUserId);
    }

    private function exactRelationId(int $topicTypeId, int $subtypeId, ?int $createdByUserId): int
    {
        $statement = $this->db->prepare(
            'SELECT id
             FROM session_topic_type_subtypes
             WHERE session_topic_type_id = :topic_type_id
               AND session_topic_subtype_id = :subtype_id
               AND created_by_user_id <=> :created_by_user_id
             LIMIT 1'
        );
        $statement->execute([
            'topic_type_id' => $topicTypeId,
            'subtype_id' => $subtypeId,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int) $statement->fetchColumn();
    }

    private function visibleRelationId(int $topicTypeId, int $subtypeId, ?int $createdByUserId): int
    {
        $statement = $this->db->prepare(
            'SELECT id
             FROM session_topic_type_subtypes
             WHERE session_topic_type_id = :topic_type_id
               AND session_topic_subtype_id = :subtype_id
               AND (is_public = 1 OR created_by_user_id = :created_by_user_id)
             ORDER BY is_public DESC
             LIMIT 1'
        );
        $statement->execute([
            'topic_type_id' => $topicTypeId,
            'subtype_id' => $subtypeId,
            'created_by_user_id' => $createdByUserId ?? 0,
        ]);

        return (int) $statement->fetchColumn();
    }

    private function mapSubtype(array $row): SessionTopicSubtype
    {
        return new SessionTopicSubtype(
            (int) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            $row['description'],
            $row['color'],
            (int) $row['sort_order'],
            (bool) $row['is_active'],
            isset($row['created_by_user_id']) ? (int) $row['created_by_user_id'] : null
        );
    }

    private function mapRelated(array $row): SessionTopicSubtype
    {
        return new SessionTopicSubtype(
            (int) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            $row['description'],
            $row['color'],
            (int) $row['sort_order'],
            (bool) $row['is_active'],
            (int) $row['topic_type_id'],
            (string) $row['topic_type_code'],
            (string) $row['topic_type_name'],
            (int) $row['relation_id'],
            (bool) $row['relation_is_active'],
            isset($row['created_by_user_id']) ? (int) $row['created_by_user_id'] : null,
            isset($row['relation_created_by_user_id']) ? (int) $row['relation_created_by_user_id'] : null,
            array_key_exists('relation_is_public', $row) ? (bool) $row['relation_is_public'] : null,
            isset($row['relation_created_by_user_name']) ? (string) $row['relation_created_by_user_name'] : null
        );
    }
}
