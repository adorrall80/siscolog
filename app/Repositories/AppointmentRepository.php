<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Appointment;
use Core\Database;
use PDO;

final class AppointmentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return Appointment[]
     */
    public function all(): array
    {
        $statement = $this->db->query(
            'SELECT
                cs.id,
                cs.patient_id,
                p.code AS patient_code,
                p.full_name AS patient_name,
                cs.session_date,
                cs.modality,
                cs.risk_level,
                cs.reason,
                cs.is_active
             FROM clinical_sessions cs
             INNER JOIN patients p ON p.id = cs.patient_id
             ORDER BY cs.session_date ASC, cs.id ASC'
        );

        return array_map(
            fn (array $row): Appointment => $this->map($row),
            $statement->fetchAll()
        );
    }

    /**
     * @return Appointment[]
     */
    public function allForProfessional(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT
                cs.id,
                cs.patient_id,
                p.code AS patient_code,
                p.full_name AS patient_name,
                cs.session_date,
                cs.modality,
                cs.risk_level,
                cs.reason,
                cs.is_active
             FROM clinical_sessions cs
             INNER JOIN patients p ON p.id = cs.patient_id
             WHERE cs.professional_id = :professional_id
             ORDER BY cs.session_date ASC, cs.id ASC'
        );
        $statement->execute(['professional_id' => $userId]);

        return array_map(
            fn (array $row): Appointment => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function find(int $id): ?Appointment
    {
        $statement = $this->db->prepare(
            'SELECT
                cs.id,
                cs.patient_id,
                p.code AS patient_code,
                p.full_name AS patient_name,
                cs.session_date,
                cs.modality,
                cs.risk_level,
                cs.reason,
                cs.is_active
             FROM clinical_sessions cs
             INNER JOIN patients p ON p.id = cs.patient_id
             WHERE cs.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->map($row) : null;
    }

    public function professionalCanAccess(int $appointmentId, int $userId): bool
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*)
             FROM clinical_sessions cs
             INNER JOIN patients p ON p.id = cs.patient_id
             WHERE cs.id = :appointment_id
               AND cs.professional_id = :professional_id'
        );
        $statement->execute([
            'appointment_id' => $appointmentId,
            'professional_id' => $userId,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @param Appointment[] $appointments
     * @return array{total: int, upcoming: int, past: int, not_vigente: int}
     */
    public function summary(array $appointments): array
    {
        $today = date('Y-m-d 00:00:00');
        $upcoming = 0;
        $notVigente = 0;

        foreach ($appointments as $appointment) {
            if (!$appointment->isActive) {
                $notVigente++;
            }

            if ($appointment->sessionDate >= $today) {
                $upcoming++;
            }
        }

        $total = count($appointments);

        return [
            'total' => $total,
            'upcoming' => $upcoming,
            'past' => $total - $upcoming,
            'not_vigente' => $notVigente,
        ];
    }

    private function map(array $row): Appointment
    {
        return new Appointment(
            (int) $row['id'],
            (int) $row['patient_id'],
            (string) $row['patient_code'],
            (string) $row['patient_name'],
            (string) $row['session_date'],
            (string) $row['modality'],
            (string) $row['risk_level'],
            $row['reason'],
            (bool) $row['is_active']
        );
    }
}
