<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PatientFamilyRelationship;
use Core\Database;
use PDO;

final class PatientFamilyRelationshipRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return PatientFamilyRelationship[]
     */
    public function byPatient(int $patientId, ?int $createdByUserId = null, bool $includeAll = false): array
    {
        $sql = 'SELECT * FROM patient_family_relationships
                WHERE patient_id = :patient_id AND is_active = 1';
        $params = ['patient_id' => $patientId];

        if (!$includeAll) {
            $sql .= ' AND created_by_user_id = :created_by_user_id';
            $params['created_by_user_id'] = $createdByUserId;
        }

        $sql .= ' ORDER BY created_at ASC, id ASC';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return array_map(
            fn (array $row): PatientFamilyRelationship => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function create(int $patientId, array $data): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO patient_family_relationships (
                patient_id,
                created_by_user_id,
                from_node_key,
                from_node_label,
                relationship_label,
                to_node_key,
                to_node_label,
                is_bidirectional,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :patient_id,
                :created_by_user_id,
                :from_node_key,
                :from_node_label,
                :relationship_label,
                :to_node_key,
                :to_node_label,
                :is_bidirectional,
                1,
                NOW(),
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                from_node_label = VALUES(from_node_label),
                to_node_label = VALUES(to_node_label),
                is_bidirectional = VALUES(is_bidirectional),
                created_by_user_id = VALUES(created_by_user_id),
                is_active = 1,
                updated_at = NOW()'
        );

        $statement->execute([
            'patient_id' => $patientId,
            'created_by_user_id' => $data['created_by_user_id'],
            'from_node_key' => $data['from_node_key'],
            'from_node_label' => $data['from_node_label'],
            'relationship_label' => $data['relationship_label'],
            'to_node_key' => $data['to_node_key'],
            'to_node_label' => $data['to_node_label'],
            'is_bidirectional' => !empty($data['is_bidirectional']) ? 1 : 0,
        ]);
    }

    public function activeBetween(int $patientId, string $fromNodeKey, string $toNodeKey, ?int $createdByUserId): ?PatientFamilyRelationship
    {
        $statement = $this->db->prepare(
            'SELECT * FROM patient_family_relationships
             WHERE patient_id = :patient_id
               AND created_by_user_id = :created_by_user_id
               AND (
                    (from_node_key = :from_node_key AND to_node_key = :to_node_key)
                    OR
                    (from_node_key = :reverse_from_node_key AND to_node_key = :reverse_to_node_key)
               )
               AND is_active = 1
             ORDER BY updated_at DESC, id DESC
             LIMIT 1'
        );
        $statement->execute([
            'patient_id' => $patientId,
            'created_by_user_id' => $createdByUserId,
            'from_node_key' => $fromNodeKey,
            'to_node_key' => $toNodeKey,
            'reverse_from_node_key' => $toNodeKey,
            'reverse_to_node_key' => $fromNodeKey,
        ]);
        $row = $statement->fetch();

        return $row ? $this->map($row) : null;
    }

    public function update(int $patientId, int $relationshipId, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE patient_family_relationships
             SET from_node_key = :from_node_key,
                 from_node_label = :from_node_label,
                 relationship_label = :relationship_label,
                 to_node_key = :to_node_key,
                 to_node_label = :to_node_label,
                 is_bidirectional = :is_bidirectional,
                 is_active = 1,
                 updated_at = NOW()
             WHERE id = :id AND patient_id = :patient_id'
             . ((int) ($data['created_by_user_id'] ?? 0) > 0 ? ' AND created_by_user_id = :created_by_user_id' : '')
        );
        $params = [
            'id' => $relationshipId,
            'patient_id' => $patientId,
            'from_node_key' => $data['from_node_key'],
            'from_node_label' => $data['from_node_label'],
            'relationship_label' => $data['relationship_label'],
            'to_node_key' => $data['to_node_key'],
            'to_node_label' => $data['to_node_label'],
            'is_bidirectional' => !empty($data['is_bidirectional']) ? 1 : 0,
        ];

        if ((int) ($data['created_by_user_id'] ?? 0) > 0) {
            $params['created_by_user_id'] = (int) $data['created_by_user_id'];
        }

        $statement->execute($params);
    }

    public function setActive(int $patientId, int $relationshipId, bool $active, ?int $createdByUserId = null, bool $includeAll = false): void
    {
        $sql = 'UPDATE patient_family_relationships
                SET is_active = :is_active, updated_at = NOW()
                WHERE id = :id AND patient_id = :patient_id';
        $params = [
            'id' => $relationshipId,
            'patient_id' => $patientId,
            'is_active' => $active ? 1 : 0,
        ];

        if (!$includeAll) {
            $sql .= ' AND created_by_user_id = :created_by_user_id';
            $params['created_by_user_id'] = $createdByUserId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    public function deactivateForNode(
        int $patientId,
        string $nodeKey,
        ?int $createdByUserId = null,
        bool $includeAll = false
    ): void {
        $sql = 'UPDATE patient_family_relationships
                SET is_active = 0, updated_at = NOW()
                WHERE patient_id = :patient_id
                  AND is_active = 1
                  AND (from_node_key = :from_node_key OR to_node_key = :to_node_key)';
        $params = [
            'patient_id' => $patientId,
            'from_node_key' => $nodeKey,
            'to_node_key' => $nodeKey,
        ];

        if (!$includeAll) {
            $sql .= ' AND created_by_user_id = :created_by_user_id';
            $params['created_by_user_id'] = $createdByUserId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    public function activateForNode(
        int $patientId,
        string $nodeKey,
        ?int $createdByUserId = null,
        bool $includeAll = false
    ): void {
        $sql = 'UPDATE patient_family_relationships
                SET is_active = 1, updated_at = NOW()
                WHERE patient_id = :patient_id
                  AND is_active = 0
                  AND (from_node_key = :from_node_key OR to_node_key = :to_node_key)';
        $params = [
            'patient_id' => $patientId,
            'from_node_key' => $nodeKey,
            'to_node_key' => $nodeKey,
        ];

        if (!$includeAll) {
            $sql .= ' AND created_by_user_id = :created_by_user_id';
            $params['created_by_user_id'] = $createdByUserId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    private function map(array $row): PatientFamilyRelationship
    {
        return new PatientFamilyRelationship(
            (int) $row['id'],
            (int) $row['patient_id'],
            $row['created_by_user_id'] === null ? null : (int) $row['created_by_user_id'],
            (string) $row['from_node_key'],
            (string) $row['from_node_label'],
            (string) $row['relationship_label'],
            (string) $row['to_node_key'],
            (string) $row['to_node_label'],
            (bool) ($row['is_bidirectional'] ?? false),
            (bool) $row['is_active']
        );
    }
}
