<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database;
use PDO;

final class PatientSeeder
{
    public function run(): void
    {
        $db = Database::connection();

        $this->upsertPatient($db);

        $patientId = $this->patientId($db);

        if ($patientId === 0) {
            echo 'No se pudo crear el paciente demo.' . PHP_EOL;
            return;
        }

        $this->clearDemoCase($db, $patientId);
        $this->seedEmergencyContact($db, $patientId);
        $this->seedConsent($db, $patientId);
        $sessionIds = $this->seedSessions($db, $patientId);
        $this->seedPsychometricApplication($db, $patientId);
        $this->seedAiAnalysis($db, $patientId, $sessionIds);
    }

    private function upsertPatient(PDO $db): void
    {
        $statement = $db->prepare(
            'INSERT INTO patients (code, full_name, birth_date, email, phone, status, is_active, created_at, updated_at)
             VALUES (:code, :full_name, :birth_date, :email, :phone, :status, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), status = VALUES(status), is_active = 1'
        );

        $statement->execute([
            'code' => 'PAC-0001',
            'full_name' => 'Paciente Demo',
            'birth_date' => '1990-01-01',
            'email' => 'paciente.demo@example.com',
            'phone' => '+56900000000',
            'status' => 'activo',
        ]);
    }

    private function patientId(PDO $db): int
    {
        $statement = $db->prepare('SELECT id FROM patients WHERE code = :code LIMIT 1');
        $statement->execute(['code' => 'PAC-0001']);

        return (int) $statement->fetchColumn();
    }

    private function clearDemoCase(PDO $db, int $patientId): void
    {
        $sessionIds = $this->sessionIds($db, $patientId);

        if ($sessionIds !== []) {
            $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
            $db->prepare("DELETE FROM clinical_session_topics WHERE clinical_session_id IN ({$placeholders})")->execute($sessionIds);
            $db->prepare("DELETE FROM clinical_session_participants WHERE clinical_session_id IN ({$placeholders})")->execute($sessionIds);
        }

        $db->prepare('DELETE FROM ai_analysis_outputs WHERE patient_id = ?')->execute([$patientId]);
        $this->clearPsychometricDemo($db, $patientId);
        $db->prepare('DELETE FROM clinical_sessions WHERE patient_id = ?')->execute([$patientId]);
        $db->prepare('DELETE FROM patient_consents WHERE patient_id = ?')->execute([$patientId]);
        $db->prepare('DELETE FROM emergency_contacts WHERE patient_id = ?')->execute([$patientId]);
        $db->prepare('DELETE FROM patient_associated_people WHERE patient_id = ?')->execute([$patientId]);
    }

    private function clearPsychometricDemo(PDO $db, int $patientId): void
    {
        $applicationIds = $this->psychometricApplicationIds($db, $patientId);

        if ($applicationIds !== []) {
            $placeholders = implode(',', array_fill(0, count($applicationIds), '?'));
            $db->prepare("DELETE FROM psychometric_answers WHERE application_id IN ({$placeholders})")->execute($applicationIds);
        }

        $db->prepare('DELETE FROM psychometric_applications WHERE patient_id = ?')->execute([$patientId]);
        $db->prepare('DELETE FROM psychometric_results WHERE patient_id = ?')->execute([$patientId]);
    }

    /**
     * @return int[]
     */
    private function psychometricApplicationIds(PDO $db, int $patientId): array
    {
        $statement = $db->prepare('SELECT id FROM psychometric_applications WHERE patient_id = :patient_id');
        $statement->execute(['patient_id' => $patientId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @return int[]
     */
    private function sessionIds(PDO $db, int $patientId): array
    {
        $statement = $db->prepare('SELECT id FROM clinical_sessions WHERE patient_id = :patient_id');
        $statement->execute(['patient_id' => $patientId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    private function seedEmergencyContact(PDO $db, int $patientId): void
    {
        $statement = $db->prepare(
            'INSERT INTO emergency_contacts (patient_id, name, relationship, phone, email, is_active, created_at, updated_at)
             VALUES (:patient_id, :name, :relationship, :phone, :email, 1, NOW(), NOW())'
        );
        $statement->execute([
            'patient_id' => $patientId,
            'name' => 'Maria Demo',
            'relationship' => 'Madre',
            'phone' => '+56911111111',
            'email' => 'madre.demo@example.com',
        ]);
    }

    private function seedConsent(PDO $db, int $patientId): void
    {
        $statement = $db->prepare(
            'INSERT INTO patient_consents (
                patient_id,
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
                :consent_type,
                1,
                1,
                :accepted_at,
                NULL,
                :document_version,
                NOW(),
                NOW()
             )'
        );
        $statement->execute([
            'patient_id' => $patientId,
            'consent_type' => 'analisis_ia',
            'accepted_at' => '2026-06-01 09:00:00',
            'document_version' => 'IA-v1.0-demo',
        ]);
    }

    /**
     * @return int[]
     */
    private function seedSessions(PDO $db, int $patientId): array
    {
        $sessions = [
            [
                'date' => '2026-06-03 10:00:00',
                'modality' => 'presencial',
                'reason' => 'Paciente asiste a sesion por ansiedad, problemas de sueno y tension familiar reciente.',
                'subjective' => 'Refiere preocupacion persistente durante la noche y dificultad para desconectarse.',
                'objective' => 'Se observa colaborador, orientado y con lenguaje coherente. Afecto ansioso leve.',
                'impression' => 'Sintomatologia ansiosa leve a moderada asociada a estres familiar.',
                'risk' => 'bajo',
                'agreements' => 'Registrar higiene del sueno y situaciones gatillantes.',
                'next' => 'Revisar rutina nocturna y red de apoyo en proxima sesion.',
                'participants' => [
                    ['type' => 'paciente', 'text' => 'Paciente', 'origin' => 'L'],
                ],
                'topics' => [
                    ['motivo_consulta', 'Ansiedad'],
                    ['motivo_consulta', 'Problemas de sueno'],
                ],
            ],
            [
                'date' => '2026-06-10 16:30:00',
                'modality' => 'online',
                'reason' => 'Seguimiento por ansiedad y comunicacion familiar.',
                'subjective' => 'Paciente senala menor intensidad de ansiedad, pero mantiene discusiones con figura paterna.',
                'objective' => 'Participa padre. Se identifican diferencias en limites y expectativas familiares.',
                'impression' => 'Mejora parcial de autorregistro. Persisten tensiones vinculares que mantienen malestar.',
                'risk' => 'bajo',
                'agreements' => 'Practicar comunicacion concreta y pausas ante escalada de conflicto.',
                'next' => 'Explorar acuerdos familiares y reforzar estrategias de regulacion emocional.',
                'participants' => [
                    ['type' => 'paciente', 'text' => 'Paciente', 'origin' => 'L'],
                    ['type' => 'padre', 'text' => 'Padre', 'origin' => 'L'],
                ],
                'topics' => [
                    ['familia_vinculos', 'Relacion con padre'],
                    ['proceso_terapeutico', 'Regulacion emocional'],
                ],
            ],
            [
                'date' => '2026-06-17 11:00:00',
                'modality' => 'presencial',
                'reason' => 'Revision de avances, sueno y acuerdos familiares.',
                'subjective' => 'Refiere dos noches de mejor descanso y menor evitacion de conversaciones dificiles.',
                'objective' => 'Se observa mayor capacidad de nombrar emociones y pedir pausa antes de discutir.',
                'impression' => 'Evolucion favorable inicial. Mantener seguimiento de ansiedad y vinculos familiares.',
                'risk' => 'sin_riesgo',
                'agreements' => 'Continuar registro emocional y una conversacion semanal breve con padre.',
                'next' => 'Evaluar continuidad de habitos de sueno y cumplimiento de acuerdos.',
                'participants' => [
                    ['type' => 'paciente', 'text' => 'Paciente', 'origin' => 'L'],
                    ['type' => 'madre', 'text' => 'Madre', 'origin' => 'L'],
                ],
                'topics' => [
                    ['habitos_salud', 'Sueno'],
                    ['familia_vinculos', 'Comunicacion familiar'],
                    ['proceso_terapeutico', 'Seguimiento de acuerdos'],
                ],
            ],
        ];

        $insert = $db->prepare(
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

        $sessionIds = [];
        $professionalId = $this->adminUserId($db);

        foreach ($sessions as $session) {
            $insert->execute([
                'patient_id' => $patientId,
                'professional_id' => $professionalId ?: null,
                'session_date' => $session['date'],
                'modality' => $session['modality'],
                'reason' => $session['reason'],
                'subjective_note' => $session['subjective'],
                'objective_note' => $session['objective'],
                'clinical_impression' => $session['impression'],
                'risk_level' => $session['risk'],
                'agreements' => $session['agreements'],
                'next_steps' => $session['next'],
            ]);

            $sessionId = (int) $db->lastInsertId();
            $sessionIds[] = $sessionId;
            $this->seedSessionParticipants($db, $patientId, $sessionId, $session['participants']);
            $this->seedSessionTopics($db, $sessionId, $session['topics']);
        }

        return $sessionIds;
    }

    /**
     * @param array<int, array{type: string, text: string, origin: string}> $participants
     */
    private function seedSessionParticipants(PDO $db, int $patientId, int $sessionId, array $participants): void
    {
        $statement = $db->prepare(
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
                NULL,
                1,
                NOW(),
                NOW()
             )'
        );

        foreach ($participants as $participant) {
            $statement->execute([
                'clinical_session_id' => $sessionId,
                'patient_id' => $patientId,
                'participant_type' => $participant['type'],
                'participant_text' => $participant['text'],
                'origin' => $participant['origin'],
            ]);
        }
    }

    /**
     * @param array<int, array{0: string, 1: string}> $topics
     */
    private function seedSessionTopics(PDO $db, int $sessionId, array $topics): void
    {
        $statement = $db->prepare(
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
             )'
        );

        foreach ($topics as [$typeCode, $subtypeName]) {
            $typeId = $this->topicTypeId($db, $typeCode);
            $subtypeId = $this->topicSubtypeId($db, $subtypeName);

            if ($typeId === 0 || $subtypeId === 0) {
                continue;
            }

            $statement->execute([
                'clinical_session_id' => $sessionId,
                'type_id' => $typeId,
                'subtype_id' => $subtypeId,
            ]);
        }
    }

    private function seedPsychometricApplication(PDO $db, int $patientId): void
    {
        $instrument = $this->instrumentByCode($db, 'phq_9');

        if ($instrument === null) {
            return;
        }

        $version = $this->activeInstrumentVersion($db, (int) $instrument['id']);

        if ($version === null) {
            return;
        }

        $questions = $this->questionsForVersion($db, (int) $version['id']);

        if ($questions === []) {
            return;
        }

        $demoScores = [1, 1, 2, 1, 1, 1, 2, 0, 0];
        $answers = [];
        $totalScore = 0;

        foreach ($questions as $index => $question) {
            $score = $demoScores[$index] ?? 0;
            $option = $this->optionByScore($db, (int) $question['id'], $score);

            if ($option === null) {
                continue;
            }

            $totalScore += $score;
            $answers[] = [
                'question' => $question,
                'option' => $option,
                'score' => $score,
            ];
        }

        if ($answers === []) {
            return;
        }

        $rule = $this->interpretationRuleForScore($db, (int) $version['id'], $totalScore);
        $professionalId = $this->adminUserId($db) ?: null;
        $appliedAt = '2026-06-18 09:30:00';
        $interpretation = (string) ($rule['interpretation'] ?? 'Interpretacion pendiente de revision profesional.');

        $summaryId = $this->insertPsychometricResult($db, [
            'patient_id' => $patientId,
            'instrument_id' => (int) $instrument['id'],
            'professional_id' => $professionalId,
            'applied_at' => $appliedAt,
            'score' => $totalScore,
            'interpretation' => $interpretation,
            'professional_notes' => 'Aplicacion demo PHQ-9. Puntaje leve; contrastar con entrevista, sueno y contexto familiar.',
        ]);

        $applicationId = $this->insertPsychometricApplication($db, [
            'patient_id' => $patientId,
            'instrument_id' => (int) $instrument['id'],
            'instrument_version_id' => (int) $version['id'],
            'professional_id' => $professionalId,
            'applied_at' => $appliedAt,
            'total_score' => $totalScore,
            'result_code' => $rule['code'] ?? null,
            'result_label' => $rule['label'] ?? null,
            'interpretation' => $interpretation,
            'professional_notes' => 'Aplicacion demo PHQ-9. Puntaje leve; contrastar con entrevista, sueno y contexto familiar.',
            'summary_result_id' => $summaryId,
            'computed_data' => json_encode([
                'source' => 'demo_seed',
                'question_count' => count($questions),
                'answered_count' => count($answers),
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $this->insertPsychometricAnswers($db, $applicationId, $answers);
    }

    private function instrumentByCode(PDO $db, string $code): ?array
    {
        $statement = $db->prepare('SELECT * FROM psychometric_instruments WHERE code = :code LIMIT 1');
        $statement->execute(['code' => $code]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    private function activeInstrumentVersion(PDO $db, int $instrumentId): ?array
    {
        $statement = $db->prepare(
            'SELECT *
             FROM psychometric_instrument_versions
             WHERE instrument_id = :instrument_id
               AND is_active = 1
             ORDER BY version_number DESC, id DESC
             LIMIT 1'
        );
        $statement->execute(['instrument_id' => $instrumentId]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    private function questionsForVersion(PDO $db, int $versionId): array
    {
        $statement = $db->prepare(
            'SELECT *
             FROM psychometric_questions
             WHERE instrument_version_id = :version_id
               AND is_active = 1
             ORDER BY item_order ASC, id ASC'
        );
        $statement->execute(['version_id' => $versionId]);

        return $statement->fetchAll();
    }

    private function optionByScore(PDO $db, int $questionId, int $score): ?array
    {
        $statement = $db->prepare(
            'SELECT *
             FROM psychometric_question_options
             WHERE question_id = :question_id
               AND score = :score
               AND is_active = 1
             ORDER BY option_order ASC
             LIMIT 1'
        );
        $statement->execute([
            'question_id' => $questionId,
            'score' => $score,
        ]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    private function interpretationRuleForScore(PDO $db, int $versionId, int $score): ?array
    {
        $statement = $db->prepare(
            'SELECT *
             FROM psychometric_interpretation_rules
             WHERE instrument_version_id = :version_id
               AND is_active = 1
               AND min_score <= :score_min
               AND (max_score IS NULL OR max_score >= :score_max)
             ORDER BY rule_order ASC
             LIMIT 1'
        );
        $statement->execute([
            'version_id' => $versionId,
            'score_min' => $score,
            'score_max' => $score,
        ]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    private function insertPsychometricResult(PDO $db, array $data): int
    {
        $statement = $db->prepare(
            'INSERT INTO psychometric_results (
                patient_id,
                instrument_id,
                professional_id,
                applied_at,
                score,
                interpretation,
                professional_notes,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :patient_id,
                :instrument_id,
                :professional_id,
                :applied_at,
                :score,
                :interpretation,
                :professional_notes,
                1,
                NOW(),
                NOW()
             )'
        );
        $statement->execute($data);

        return (int) $db->lastInsertId();
    }

    private function insertPsychometricApplication(PDO $db, array $data): int
    {
        $statement = $db->prepare(
            'INSERT INTO psychometric_applications (
                patient_id,
                instrument_id,
                instrument_version_id,
                professional_id,
                applied_at,
                total_score,
                result_code,
                result_label,
                interpretation,
                professional_notes,
                computed_data,
                summary_result_id,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :patient_id,
                :instrument_id,
                :instrument_version_id,
                :professional_id,
                :applied_at,
                :total_score,
                :result_code,
                :result_label,
                :interpretation,
                :professional_notes,
                :computed_data,
                :summary_result_id,
                1,
                NOW(),
                NOW()
             )'
        );
        $statement->execute($data);

        return (int) $db->lastInsertId();
    }

    private function insertPsychometricAnswers(PDO $db, int $applicationId, array $answers): void
    {
        $statement = $db->prepare(
            'INSERT INTO psychometric_answers (
                application_id,
                question_id,
                option_id,
                item_order,
                question_snapshot,
                answer_label,
                answer_value,
                answer_score,
                answer_json,
                created_at
             )
             VALUES (
                :application_id,
                :question_id,
                :option_id,
                :item_order,
                :question_snapshot,
                :answer_label,
                :answer_value,
                :answer_score,
                :answer_json,
                NOW()
             )'
        );

        foreach ($answers as $answer) {
            $question = $answer['question'];
            $option = $answer['option'];
            $statement->execute([
                'application_id' => $applicationId,
                'question_id' => (int) $question['id'],
                'option_id' => (int) $option['id'],
                'item_order' => (int) $question['item_order'],
                'question_snapshot' => (string) $question['question_text'],
                'answer_label' => (string) $option['label'],
                'answer_value' => (string) $option['value'],
                'answer_score' => (int) $answer['score'],
                'answer_json' => json_encode([
                    'question_code' => $question['code'] ?? null,
                    'question_type' => $question['question_type'] ?? null,
                    'source' => 'demo_seed',
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * @param int[] $sessionIds
     */
    private function seedAiAnalysis(PDO $db, int $patientId, array $sessionIds): void
    {
        if ($sessionIds === []) {
            return;
        }

        $output = [
            'resumen' => 'Paciente demo con ansiedad inicial asociada a estres familiar, con mejoria progresiva en sueno, autorregistro y comunicacion.',
            'evolucion_del_caso' => 'Entre la primera y tercera sesion se observa reduccion del malestar reportado, mayor identificacion emocional y mejor uso de pausas ante conflicto familiar.',
            'factores_observados' => [
                'Ansiedad y problemas de sueno registrados al inicio.',
                'Participacion de padre y madre en sesiones posteriores.',
                'Mejor adherencia a acuerdos de registro emocional.',
            ],
            'hipotesis_de_trabajo' => [
                'Ansiedad mantenida por tension familiar y habitos de sueno irregulares.',
                'La mejora aparece asociada a estructura, comunicacion y seguimiento de acuerdos.',
            ],
            'factores_protectores' => [
                'Paciente colaborador.',
                'Red familiar disponible.',
                'Capacidad creciente de pedir pausa y expresar necesidades.',
            ],
            'alertas' => [
                'Mantener monitoreo de sueno y escalada de conflicto familiar.',
                'Sin riesgo critico observado en las sesiones demo.',
            ],
            'preguntas_para_proxima_sesion' => [
                'Que situaciones facilitaron dormir mejor esta semana?',
                'Que acuerdos familiares fueron posibles de cumplir?',
                'Que senales tempranas aparecen antes de discutir?',
            ],
            'limites' => 'Salida demo para pruebas. No reemplaza juicio clinico ni supervision profesional.',
        ];

        $statement = $db->prepare(
            'INSERT INTO ai_analysis_outputs (
                patient_id,
                requested_by,
                source_type,
                source_ids,
                model,
                prompt_version,
                output_json,
                review_status,
                is_active,
                professional_notes,
                reviewed_by,
                reviewed_at,
                created_at,
                updated_at
             )
             VALUES (
                :patient_id,
                :requested_by,
                :source_type,
                :source_ids,
                :model,
                :prompt_version,
                :output_json,
                :review_status,
                1,
                :professional_notes,
                :reviewed_by,
                :reviewed_at,
                NOW(),
                NOW()
             )'
        );

        $userId = $this->adminUserId($db) ?: null;
        $statement->execute([
            'patient_id' => $patientId,
            'requested_by' => $userId,
            'source_type' => 'patient_evolution',
            'source_ids' => json_encode($sessionIds, JSON_THROW_ON_ERROR),
            'model' => 'demo-local',
            'prompt_version' => 'demo-v1',
            'output_json' => json_encode($output, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'review_status' => 'pendiente',
            'professional_notes' => 'Nota profesional demo: revisar con criterio clinico, validar con el paciente y ajustar hipotesis segun nuevas sesiones.',
            'reviewed_by' => $userId,
            'reviewed_at' => '2026-06-17 12:00:00',
        ]);
    }

    private function adminUserId(PDO $db): int
    {
        $statement = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => 'admin@siscolog.local']);

        return (int) $statement->fetchColumn();
    }

    private function topicTypeId(PDO $db, string $code): int
    {
        $statement = $db->prepare('SELECT id FROM session_topic_types WHERE code = :code LIMIT 1');
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn();
    }

    private function topicSubtypeId(PDO $db, string $name): int
    {
        $statement = $db->prepare('SELECT id FROM session_topic_subtypes WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);

        return (int) $statement->fetchColumn();
    }
}
