<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AssociatedPerson;
use Core\Database;
use PDO;

final class AssociatedPersonRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return AssociatedPerson[]
     */
    public function byPatient(int $patientId, ?int $createdByUserId = null, bool $includeAll = false): array
    {
        if (!$includeAll && $createdByUserId === null) {
            return [];
        }

        $sessionScopeSql = $includeAll ? '' : ' AND cs.professional_id = :session_user_id';
        $whereScopeSql = '';
        $params = ['patient_id' => $patientId];

        if (!$includeAll) {
            $whereScopeSql = ' AND (
                    pap.created_by_user_id = :created_by_user_id
                    OR EXISTS (
                        SELECT 1
                        FROM clinical_session_participants csp_scope
                        INNER JOIN clinical_sessions cs_scope ON cs_scope.id = csp_scope.clinical_session_id
                        WHERE csp_scope.associated_person_id = pap.id
                          AND csp_scope.patient_id = pap.patient_id
                          AND csp_scope.is_active = 1
                          AND cs_scope.professional_id = :scope_user_id
                    )
                )';
            $params['created_by_user_id'] = $createdByUserId;
            $params['scope_user_id'] = $createdByUserId;
            $params['session_user_id'] = $createdByUserId;
        }

        $statement = $this->db->prepare(
            'SELECT pap.*,
                    (
                        SELECT COUNT(*)
                        FROM clinical_session_participants csp
                        INNER JOIN clinical_sessions cs ON cs.id = csp.clinical_session_id
                        WHERE csp.associated_person_id = pap.id
                          AND csp.patient_id = pap.patient_id
                          AND csp.is_active = 1
                          ' . $sessionScopeSql . '
                    ) AS session_count
             FROM patient_associated_people pap
             WHERE pap.patient_id = :patient_id AND pap.is_active = 1
             ' . $whereScopeSql . '
             ORDER BY pap.participant_type ASC, pap.display_name ASC'
        );
        $statement->execute($params);

        return array_map(
            fn (array $row): AssociatedPerson => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function findForPatient(int $patientId, int $id, ?int $createdByUserId = null, bool $includeAll = false): ?AssociatedPerson
    {
        if (!$includeAll && $createdByUserId === null) {
            return null;
        }

        $sql = 'SELECT * FROM patient_associated_people
             WHERE id = :id AND patient_id = :patient_id AND is_active = 1';
        $params = [
            'id' => $id,
            'patient_id' => $patientId,
        ];

        if (!$includeAll) {
            $sql .= ' AND (
                    created_by_user_id = :created_by_user_id
                    OR EXISTS (
                        SELECT 1
                        FROM clinical_session_participants csp_scope
                        INNER JOIN clinical_sessions cs_scope ON cs_scope.id = csp_scope.clinical_session_id
                        WHERE csp_scope.associated_person_id = patient_associated_people.id
                          AND csp_scope.patient_id = patient_associated_people.patient_id
                          AND csp_scope.is_active = 1
                          AND cs_scope.professional_id = :scope_user_id
                    )
                )';
            $params['created_by_user_id'] = $createdByUserId;
            $params['scope_user_id'] = $createdByUserId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare(
            $sql
        );
        $statement->execute($params);
        $row = $statement->fetch();

        return $row ? $this->map($row) : null;
    }

    public function findByNormalized(int $patientId, string $participantType, string $normalizedName, ?int $createdByUserId = null): ?AssociatedPerson
    {
        $statement = $this->db->prepare(
            'SELECT * FROM patient_associated_people
             WHERE patient_id = :patient_id
               AND created_by_user_id <=> :created_by_user_id
               AND participant_type = :participant_type
               AND normalized_name = :normalized_name
             LIMIT 1'
        );
        $statement->execute([
            'patient_id' => $patientId,
            'created_by_user_id' => $createdByUserId,
            'participant_type' => $participantType,
            'normalized_name' => $normalizedName,
        ]);
        $row = $statement->fetch();

        return $row ? $this->map($row) : null;
    }

    public function create(int $patientId, string $participantType, string $displayName, string $normalizedName, ?int $createdByUserId = null): int
    {
        $existing = $this->findByNormalized($patientId, $participantType, $normalizedName, $createdByUserId);

        if ($existing !== null) {
            return (int) $existing->id;
        }

        $statement = $this->db->prepare(
            'INSERT INTO patient_associated_people (
                patient_id,
                created_by_user_id,
                participant_type,
                display_name,
                normalized_name,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :patient_id,
                :created_by_user_id,
                :participant_type,
                :display_name,
                :normalized_name,
                1,
                NOW(),
                NOW()
             )'
        );
        $statement->execute([
            'patient_id' => $patientId,
            'created_by_user_id' => $createdByUserId,
            'participant_type' => $participantType,
            'display_name' => $displayName,
            'normalized_name' => $normalizedName,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function map(array $row): AssociatedPerson
    {
        return new AssociatedPerson(
            (int) $row['id'],
            (int) $row['patient_id'],
            $row['created_by_user_id'] === null ? null : (int) $row['created_by_user_id'],
            (string) $row['participant_type'],
            (string) $row['display_name'],
            (bool) $row['is_active'],
            (int) ($row['session_count'] ?? 0) > 0
        );
    }
}
