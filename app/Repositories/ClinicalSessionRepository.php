<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ClinicalSession;
use Core\Database;
use PDO;

final class ClinicalSessionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return ClinicalSession[]
     */
    public function byPatient(int $patientId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM clinical_sessions
             WHERE patient_id = :patient_id
             ORDER BY session_date DESC, id DESC'
        );
        $statement->execute(['patient_id' => $patientId]);

        return array_map(
            fn (array $row): ClinicalSession => $this->map(
                $row,
                $this->participantsForSession((int) $row['id']),
                $this->topicsForSession((int) $row['id'])
            ),
            $statement->fetchAll()
        );
    }

    /**
     * @return ClinicalSession[]
     */
    public function byPatientForProfessional(int $patientId, int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM clinical_sessions
             WHERE patient_id = :patient_id
               AND professional_id = :professional_id
             ORDER BY session_date DESC, id DESC'
        );
        $statement->execute([
            'patient_id' => $patientId,
            'professional_id' => $userId,
        ]);

        return array_map(
            fn (array $row): ClinicalSession => $this->map(
                $row,
                $this->participantsForSession((int) $row['id']),
                $this->topicsForSession((int) $row['id'])
            ),
            $statement->fetchAll()
        );
    }

    public function create(int $patientId, array $data): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO clinical_sessions (
                patient_id,
                professional_id,
                session_date,
                modality,
                reason,
                subjective_note,
                objective_note,
                clinical_impression,
                risk_level,
                is_active,
                agreements,
                next_steps,
                created_at,
                updated_at
             )
             VALUES (
                :patient_id,
                :professional_id,
                :session_date,
                :modality,
                :reason,
                :subjective_note,
                :objective_note,
                :clinical_impression,
                :risk_level,
                1,
                :agreements,
                :next_steps,
                NOW(),
                NOW()
             )'
        );

        $statement->execute([
            'patient_id' => $patientId,
            'professional_id' => ($data['professional_id'] ?? null) ?: null,
            'session_date' => $data['session_date'],
            'modality' => $data['modality'],
            'reason' => $data['reason'] ?: null,
            'subjective_note' => $data['subjective_note'] ?: null,
            'objective_note' => $data['objective_note'] ?: null,
            'clinical_impression' => $data['clinical_impression'] ?: null,
            'risk_level' => $data['risk_level'],
            'agreements' => $data['agreements'] ?: null,
            'next_steps' => $data['next_steps'] ?: null,
        ]);

        $sessionId = (int) $this->db->lastInsertId();

        $this->saveParticipants($patientId, $sessionId, $data['participants'] ?? []);
        $this->saveTopics($sessionId, $data['topics'] ?? []);
    }

    public function update(int $patientId, int $sessionId, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE clinical_sessions
             SET session_date = :session_date,
                 modality = :modality,
                 reason = :reason,
                 subjective_note = :subjective_note,
                 objective_note = :objective_note,
                 clinical_impression = :clinical_impression,
                 risk_level = :risk_level,
                 agreements = :agreements,
                 next_steps = :next_steps,
                 updated_at = NOW()
             WHERE id = :id AND patient_id = :patient_id'
        );

        $statement->execute([
            'id' => $sessionId,
            'patient_id' => $patientId,
            'session_date' => $data['session_date'],
            'modality' => $data['modality'],
            'reason' => $data['reason'] ?: null,
            'subjective_note' => $data['subjective_note'] ?: null,
            'objective_note' => $data['objective_note'] ?: null,
            'clinical_impression' => $data['clinical_impression'] ?: null,
            'risk_level' => $data['risk_level'],
            'agreements' => $data['agreements'] ?: null,
            'next_steps' => $data['next_steps'] ?: null,
        ]);

        if ((bool) ($data['replace_participants'] ?? false)) {
            $this->replaceParticipants($patientId, $sessionId, $data['participants'] ?? []);
        } elseif (($data['participants'] ?? []) !== []) {
            $this->saveParticipants($patientId, $sessionId, $data['participants']);
        }

        if ((bool) ($data['replace_topics'] ?? false)) {
            $this->replaceTopics($sessionId, $data['topics'] ?? []);
        } elseif (($data['topics'] ?? []) !== []) {
            $this->saveTopics($sessionId, $data['topics']);
        }
    }

    public function findForPatient(int $patientId, int $sessionId): ?ClinicalSession
    {
        $statement = $this->db->prepare(
            'SELECT * FROM clinical_sessions
             WHERE id = :id AND patient_id = :patient_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $sessionId,
            'patient_id' => $patientId,
        ]);
        $row = $statement->fetch();

        return $row ? $this->map($row, $this->participantsForSession((int) $row['id']), $this->topicsForSession((int) $row['id'])) : null;
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->db->prepare(
            'UPDATE clinical_sessions SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    /**
     * @return array<int, array{type_id: int, subtype_id: int, label: string}>
     */
    public function topicRelationsForSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT
                cst.session_topic_type_id AS type_id,
                cst.session_topic_subtype_id AS subtype_id,
                CONCAT(t.name, ": ", s.name) AS topic_label
             FROM clinical_session_topics cst
             INNER JOIN session_topic_types t ON t.id = cst.session_topic_type_id
             INNER JOIN session_topic_subtypes s ON s.id = cst.session_topic_subtype_id
             WHERE cst.clinical_session_id = :session_id AND cst.is_active = 1
             ORDER BY t.sort_order ASC, t.name ASC, s.name ASC'
        );
        $statement->execute(['session_id' => $sessionId]);

        return array_map(
            fn (array $row): array => [
                'type_id' => (int) $row['type_id'],
                'subtype_id' => (int) $row['subtype_id'],
                'label' => (string) $row['topic_label'],
            ],
            $statement->fetchAll()
        );
    }

    /**
     * @return array<int, array{id: int, type: string, person_id: ?int, text: string, origin: string}>
     */
    public function participantRelationsForSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, participant_type, participant_text, origin, associated_person_id
             FROM clinical_session_participants
             WHERE clinical_session_id = :session_id AND is_active = 1
             ORDER BY participant_text ASC'
        );
        $statement->execute(['session_id' => $sessionId]);

        return array_map(
            fn (array $row): array => [
                'id' => (int) $row['id'],
                'type' => (string) $row['participant_type'],
                'person_id' => $row['associated_person_id'] === null ? null : (int) $row['associated_person_id'],
                'text' => (string) $row['participant_text'],
                'origin' => (string) $row['origin'],
            ],
            $statement->fetchAll()
        );
    }

    /**
     * @return string[]
     */
    private function participantsForSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT csp.participant_text
             FROM clinical_session_participants csp
             WHERE csp.clinical_session_id = :session_id AND csp.is_active = 1
             ORDER BY csp.participant_text ASC'
        );
        $statement->execute(['session_id' => $sessionId]);

        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @return string[]
     */
    private function topicsForSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT CONCAT(t.name, ": ", s.name) AS topic_label
             FROM clinical_session_topics cst
             INNER JOIN session_topic_types t ON t.id = cst.session_topic_type_id
             INNER JOIN session_topic_subtypes s ON s.id = cst.session_topic_subtype_id
             WHERE cst.clinical_session_id = :session_id AND cst.is_active = 1
             ORDER BY t.sort_order ASC, t.name ASC, s.name ASC'
        );
        $statement->execute(['session_id' => $sessionId]);

        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @param array<int, array{type: string, person_id: ?int, text: string, origin: string}> $participants
     */
    private function saveParticipants(int $patientId, int $sessionId, array $participants): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO clinical_session_participants (
                clinical_session_id,
                patient_id,
                participant_type,
                participant_text,
                origin,
                associated_person_id,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :clinical_session_id,
                :patient_id,
                :participant_type,
                :participant_text,
                :origin,
                :associated_person_id,
                1,
                NOW(),
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                is_active = 1,
                participant_type = VALUES(participant_type),
                origin = VALUES(origin),
                associated_person_id = VALUES(associated_person_id),
                updated_at = NOW()'
        );

        foreach ($participants as $participant) {
            $statement->execute([
                'clinical_session_id' => $sessionId,
                'patient_id' => $patientId,
                'participant_type' => $participant['type'],
                'participant_text' => $participant['text'],
                'origin' => $participant['origin'],
                'associated_person_id' => $participant['person_id'],
            ]);
        }
    }

    /**
     * @param array<int, array{type: string, person_id: ?int, text: string, origin: string}> $participants
     */
    private function replaceParticipants(int $patientId, int $sessionId, array $participants): void
    {
        $statement = $this->db->prepare(
            'UPDATE clinical_session_participants
             SET is_active = 0, updated_at = NOW()
             WHERE clinical_session_id = :session_id AND patient_id = :patient_id'
        );
        $statement->execute([
            'session_id' => $sessionId,
            'patient_id' => $patientId,
        ]);

        $this->saveParticipants($patientId, $sessionId, $participants);
    }

    /**
     * @param array<int, array{type_id: int, subtype_id: int}> $topics
     */
    private function saveTopics(int $sessionId, array $topics): void
    {
        if ($topics === []) {
            return;
        }

        $statement = $this->db->prepare(
            'INSERT INTO clinical_session_topics (
                clinical_session_id,
                session_topic_type_id,
                session_topic_subtype_id,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :clinical_session_id,
                :type_id,
                :subtype_id,
                1,
                NOW(),
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                is_active = 1,
                updated_at = NOW()'
        );

        foreach ($topics as $topic) {
            $statement->execute([
                'clinical_session_id' => $sessionId,
                'type_id' => $topic['type_id'],
                'subtype_id' => $topic['subtype_id'],
            ]);
        }
    }

    /**
     * @param array<int, array{type_id: int, subtype_id: int}> $topics
     */
    private function replaceTopics(int $sessionId, array $topics): void
    {
        $statement = $this->db->prepare(
            'UPDATE clinical_session_topics
             SET is_active = 0, updated_at = NOW()
             WHERE clinical_session_id = :session_id'
        );
        $statement->execute(['session_id' => $sessionId]);

        $this->saveTopics($sessionId, $topics);
    }

    private function map(array $row, array $participants = [], array $topics = []): ClinicalSession
    {
        return new ClinicalSession(
            (int) $row['id'],
            (int) $row['patient_id'],
            (string) $row['session_date'],
            (string) $row['modality'],
            $row['reason'],
            $row['subjective_note'],
            $row['objective_note'],
            $row['clinical_impression'],
            (string) $row['risk_level'],
            $row['agreements'],
            $row['next_steps'],
            (bool) $row['is_active'],
            $row['updated_at'],
            $participants,
            $topics
        );
    }
}
