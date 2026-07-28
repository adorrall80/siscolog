<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PatientConsent;
use Core\Database;
use PDO;

final class PatientConsentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return PatientConsent[]
     */
    public function byPatient(int $patientId, ?int $createdByUserId = null, bool $includeAll = false): array
    {
        $sql = 'SELECT * FROM patient_consents WHERE patient_id = :patient_id';
        $params = ['patient_id' => $patientId];

        if (!$includeAll) {
            $sql .= ' AND created_by_user_id = :created_by_user_id';
            $params['created_by_user_id'] = $createdByUserId;
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return array_map(
            fn (array $row): PatientConsent => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function create(int $patientId, array $data): void
    {
        $accepted = (bool) $data['accepted'];
        $statement = $this->db->prepare(
            'INSERT INTO patient_consents (
                patient_id,
                created_by_user_id,
                consent_type,
                accepted,
                is_active,
                accepted_at,
                revoked_at,
                document_version,
                created_at,
                updated_at
             )
             VALUES (
                :patient_id,
                :created_by_user_id,
                :consent_type,
                :accepted,
                1,
                :accepted_at,
                :revoked_at,
                :document_version,
                NOW(),
                NOW()
             )'
        );

        $statement->execute([
            'patient_id' => $patientId,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'consent_type' => $data['consent_type'],
            'accepted' => $accepted ? 1 : 0,
            'accepted_at' => $accepted ? ($data['accepted_at'] ?: date('Y-m-d H:i:s')) : null,
            'revoked_at' => $accepted ? null : ($data['revoked_at'] ?: date('Y-m-d H:i:s')),
            'document_version' => $data['document_version'],
        ]);
    }

    public function findForPatient(int $patientId, int $consentId, ?int $createdByUserId = null, bool $includeAll = false): ?PatientConsent
    {
        $sql = 'SELECT * FROM patient_consents
                WHERE id = :id AND patient_id = :patient_id';
        $params = [
            'id' => $consentId,
            'patient_id' => $patientId,
        ];

        if (!$includeAll) {
            $sql .= ' AND created_by_user_id = :created_by_user_id';
            $params['created_by_user_id'] = $createdByUserId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row ? $this->map($row) : null;
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->db->prepare(
            'UPDATE patient_consents SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    private function map(array $row): PatientConsent
    {
        return new PatientConsent(
            (int) $row['id'],
            (int) $row['patient_id'],
            $row['created_by_user_id'] === null ? null : (int) $row['created_by_user_id'],
            (string) $row['consent_type'],
            (bool) $row['accepted'],
            (bool) $row['is_active'],
            $row['accepted_at'],
            $row['revoked_at'],
            (string) $row['document_version']
        );
    }
}
