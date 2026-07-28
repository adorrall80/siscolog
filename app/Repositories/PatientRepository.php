<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EmergencyContact;
use App\Models\Patient;
use Core\Database;
use PDO;

final class PatientRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return Patient[]
     */
    public function all(): array
    {
        $statement = $this->db->query('SELECT * FROM patients ORDER BY created_at DESC');

        return array_map(
            fn (array $row): Patient => $this->map($row),
            $statement->fetchAll()
        );
    }

    /**
     * @return Patient[]
     */
    public function allForProfessional(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT DISTINCT p.*
             FROM patients p
             LEFT JOIN clinical_sessions cs
                ON cs.patient_id = p.id
               AND cs.professional_id = :session_professional_id
             WHERE p.assigned_professional_id = :assigned_professional_id
                OR cs.id IS NOT NULL
             ORDER BY p.created_at DESC'
        );
        $statement->execute([
            'session_professional_id' => $userId,
            'assigned_professional_id' => $userId,
        ]);

        return array_map(
            fn (array $row): Patient => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function find(int $id): ?Patient
    {
        $statement = $this->db->prepare('SELECT * FROM patients WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return $this->map($row, $this->findEmergencyContact($id));
    }

    public function professionalCanAccess(int $patientId, int $userId): bool
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(DISTINCT p.id)
             FROM patients p
             LEFT JOIN clinical_sessions cs
                ON cs.patient_id = p.id
               AND cs.professional_id = :session_professional_id
             WHERE p.id = :patient_id
               AND (
                    p.assigned_professional_id = :assigned_professional_id
                    OR cs.id IS NOT NULL
               )'
        );
        $statement->execute([
            'patient_id' => $patientId,
            'session_professional_id' => $userId,
            'assigned_professional_id' => $userId,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO patients (
                code,
                full_name,
                birth_date,
                email,
                phone,
                status,
                is_active,
                assigned_professional_id,
                created_at,
                updated_at
             )
             VALUES (
                :code,
                :full_name,
                :birth_date,
                :email,
                :phone,
                :status,
                1,
                :assigned_professional_id,
                NOW(),
                NOW()
             )'
        );

        $statement->execute([
            'code' => $data['code'],
            'full_name' => $data['full_name'],
            'birth_date' => $data['birth_date'] ?: null,
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'status' => $data['status'],
            'assigned_professional_id' => ($data['assigned_professional_id'] ?? null) ?: null,
        ]);

        $patientId = (int) $this->db->lastInsertId();
        $this->saveEmergencyContact($patientId, $data);

        return $patientId;
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE patients
             SET code = :code,
                 full_name = :full_name,
                 birth_date = :birth_date,
                 email = :email,
                 phone = :phone,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'code' => $data['code'],
            'full_name' => $data['full_name'],
            'birth_date' => $data['birth_date'] ?: null,
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'status' => $data['status'],
        ]);

        $this->saveEmergencyContact($id, $data);
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->db->prepare(
            'UPDATE patients SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    private function findEmergencyContact(int $patientId): ?EmergencyContact
    {
        $statement = $this->db->prepare(
            'SELECT * FROM emergency_contacts WHERE patient_id = :patient_id AND is_active = 1 LIMIT 1'
        );
        $statement->execute(['patient_id' => $patientId]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return new EmergencyContact(
            (int) $row['id'],
            (int) $row['patient_id'],
            $row['name'],
            $row['relationship'],
            $row['phone'],
            $row['email']
        );
    }

    private function saveEmergencyContact(int $patientId, array $data): void
    {
        $hasContact = trim((string) ($data['emergency_name'] ?? '')) !== ''
            || trim((string) ($data['emergency_phone'] ?? '')) !== ''
            || trim((string) ($data['emergency_email'] ?? '')) !== '';

        if (!$hasContact) {
            return;
        }

        $statement = $this->db->prepare(
            'INSERT INTO emergency_contacts (patient_id, name, relationship, phone, email, is_active, created_at, updated_at)
             VALUES (:patient_id, :name, :relationship, :phone, :email, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                relationship = VALUES(relationship),
                phone = VALUES(phone),
                email = VALUES(email),
                is_active = 1,
                updated_at = NOW()'
        );

        $statement->execute([
            'patient_id' => $patientId,
            'name' => $data['emergency_name'] ?: null,
            'relationship' => $data['emergency_relationship'] ?: null,
            'phone' => $data['emergency_phone'] ?: null,
            'email' => $data['emergency_email'] ?: null,
        ]);
    }

    private function map(array $row, ?EmergencyContact $emergencyContact = null): Patient
    {
        return new Patient(
            (int) $row['id'],
            (string) $row['code'],
            (string) $row['full_name'],
            $row['birth_date'],
            $row['email'],
            $row['phone'],
            (string) $row['status'],
            (bool) $row['is_active'],
            $row['assigned_professional_id'] === null ? null : (int) $row['assigned_professional_id'],
            $emergencyContact
        );
    }
}
