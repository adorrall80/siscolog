<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Database;
use PDO;

final class PatientFamilyNodePositionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return array<string, array{x: float, y: float}>
     */
    public function byPatient(int $patientId, ?int $createdByUserId = null, bool $includeAll = false): array
    {
        if (!$includeAll && $createdByUserId === null) {
            return [];
        }

        $sql = 'SELECT node_key, pos_x, pos_y
             FROM patient_family_node_positions
             WHERE patient_id = :patient_id AND is_active = 1';
        $params = ['patient_id' => $patientId];

        if (!$includeAll) {
            $sql .= ' AND created_by_user_id = :created_by_user_id';
            $params['created_by_user_id'] = $createdByUserId;
        }

        $statement = $this->db->prepare(
            $sql
        );
        $statement->execute($params);

        $positions = [];

        foreach ($statement->fetchAll() as $row) {
            $positions[(string) $row['node_key']] = [
                'x' => (float) $row['pos_x'],
                'y' => (float) $row['pos_y'],
            ];
        }

        return $positions;
    }

    public function save(int $patientId, string $nodeKey, float $x, float $y, ?int $createdByUserId = null): void
    {
        if ($createdByUserId === null) {
            return;
        }

        $statement = $this->db->prepare(
            'INSERT INTO patient_family_node_positions (
                patient_id,
                created_by_user_id,
                node_key,
                pos_x,
                pos_y,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :patient_id,
                :created_by_user_id,
                :node_key,
                :pos_x,
                :pos_y,
                1,
                NOW(),
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                pos_x = VALUES(pos_x),
                pos_y = VALUES(pos_y),
                is_active = 1,
                updated_at = NOW()'
        );
        $statement->execute([
            'patient_id' => $patientId,
            'created_by_user_id' => $createdByUserId,
            'node_key' => $nodeKey,
            'pos_x' => max(1.5, min(98.5, $x)),
            'pos_y' => max(2.5, min(97.5, $y)),
        ]);
    }
}
