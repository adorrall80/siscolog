<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PsychometricInstrument;
use App\Models\PsychometricResult;
use Core\Database;
use PDO;

final class PsychometricRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return PsychometricInstrument[]
     */
    public function activeInstruments(): array
    {
        $statement = $this->db->query(
            'SELECT *
             FROM psychometric_instruments
             WHERE is_active = 1
             ORDER BY name'
        );

        return array_map(
            fn (array $row): PsychometricInstrument => $this->mapInstrument($row),
            $statement->fetchAll()
        );
    }

    /**
     * @return PsychometricInstrument[]
     */
    public function allInstruments(): array
    {
        $statement = $this->db->query(
            'SELECT *
             FROM psychometric_instruments
             ORDER BY is_active DESC, name'
        );

        return array_map(
            fn (array $row): PsychometricInstrument => $this->mapInstrument($row),
            $statement->fetchAll()
        );
    }

    public function findInstrument(int $id): ?PsychometricInstrument
    {
        $statement = $this->db->prepare('SELECT * FROM psychometric_instruments WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->mapInstrument($row) : null;
    }

    public function updateInstrument(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE psychometric_instruments
             SET code = :code,
                 name = :name,
                 description = :description,
                 min_score = :min_score,
                 max_score = :max_score,
                 caution_cutoff = :caution_cutoff,
                 critical_cutoff = :critical_cutoff,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'min_score' => $data['min_score'],
            'max_score' => $data['max_score'],
            'caution_cutoff' => $data['caution_cutoff'] ?? null,
            'critical_cutoff' => $data['critical_cutoff'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    public function setInstrumentActive(int $id, bool $active): void
    {
        $statement = $this->db->prepare(
            'UPDATE psychometric_instruments SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public function questionGroups(): array
    {
        if (!$this->tableExists('psychometric_question_groups')) {
            return [];
        }

        $statement = $this->db->query(
            'SELECT *
             FROM psychometric_question_groups
             WHERE is_active = 1
             ORDER BY name ASC'
        );

        return $statement === false ? [] : $statement->fetchAll();
    }

    public function instrumentDetail(int $id): ?array
    {
        if (!$this->tableExists('psychometric_instrument_versions') || !$this->columnExists('psychometric_instruments', 'category_id')) {
            $statement = $this->db->prepare('SELECT * FROM psychometric_instruments WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $id]);
            $instrument = $statement->fetch();

            if (!$instrument) {
                return null;
            }

            return [
                'instrument' => $instrument,
                'version' => null,
                'questions' => [],
                'rules' => [],
            ];
        }

        $statement = $this->db->prepare(
            'SELECT pi.*,
                    pc.name AS category_name,
                    pit.name AS type_name
             FROM psychometric_instruments pi
             LEFT JOIN psychometric_categories pc ON pc.id = pi.category_id
             LEFT JOIN psychometric_instrument_types pit ON pit.id = pi.instrument_type_id
             WHERE pi.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $instrument = $statement->fetch();

        if (!$instrument) {
            return null;
        }

        $version = $this->activeVersionForInstrument($id);

        if ($version === null) {
            return [
                'instrument' => $instrument,
                'version' => null,
                'questions' => [],
                'rules' => [],
            ];
        }

        $questions = $this->questionsForVersion((int) $version['id']);

        return [
            'instrument' => $instrument,
            'version' => $version,
            'questions' => $questions,
            'rules' => $this->rulesForVersion((int) $version['id']),
        ];
    }

    /**
     * @return PsychometricResult[]
     */
    public function resultsByPatient(int $patientId, ?int $professionalId = null, bool $includeAll = false): array
    {
        $params = ['patient_id' => $patientId];
        $where = ['pr.patient_id = :patient_id'];

        if (!$includeAll && $professionalId !== null) {
            $where[] = '(pr.professional_id = :professional_id OR pr.professional_id IS NULL)';
            $params['professional_id'] = $professionalId;
        }

        $statement = $this->db->prepare(
            'SELECT pr.*,
                    pi.code AS instrument_code,
                    pi.name AS instrument_name,
                    pi.min_score,
                    pi.max_score,
                    pi.caution_cutoff,
                    pi.critical_cutoff,
                    u.name AS professional_name
             FROM psychometric_results pr
             INNER JOIN psychometric_instruments pi ON pi.id = pr.instrument_id
             LEFT JOIN users u ON u.id = pr.professional_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY pr.applied_at DESC, pr.id DESC'
        );
        $statement->execute($params);

        return array_map(
            fn (array $row): PsychometricResult => $this->mapResult($row),
            $statement->fetchAll()
        );
    }

    public function createResult(array $data): int
    {
        $statement = $this->db->prepare(
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

        $statement->execute([
            'patient_id' => $data['patient_id'],
            'instrument_id' => $data['instrument_id'],
            'professional_id' => $data['professional_id'] ?: null,
            'applied_at' => $data['applied_at'],
            'score' => $data['score'],
            'interpretation' => $data['interpretation'] ?: null,
            'professional_notes' => $data['professional_notes'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param int[] $resultIds
     * @return array<int, array<string, mixed>>
     */
    public function applicationDetailsForResults(array $resultIds, ?int $professionalId = null, bool $includeAll = false): array
    {
        $resultIds = array_values(array_unique(array_filter(array_map('intval', $resultIds))));

        if ($resultIds === [] || !$this->tableExists('psychometric_applications') || !$this->tableExists('psychometric_answers')) {
            return [];
        }

        $params = [];
        $placeholders = [];
        foreach ($resultIds as $index => $resultId) {
            $key = 'result_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $resultId;
        }

        $where = ['pa.summary_result_id IN (' . implode(',', $placeholders) . ')'];

        if (!$includeAll && $professionalId !== null) {
            $where[] = '(pa.professional_id = :professional_id OR pa.professional_id IS NULL)';
            $params['professional_id'] = $professionalId;
        }

        $statement = $this->db->prepare(
            'SELECT pa.*,
                    pi.name AS instrument_name,
                    piv.version_label,
                    u.name AS professional_name
             FROM psychometric_applications pa
             INNER JOIN psychometric_instruments pi ON pi.id = pa.instrument_id
             INNER JOIN psychometric_instrument_versions piv ON piv.id = pa.instrument_version_id
             LEFT JOIN users u ON u.id = pa.professional_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY pa.applied_at DESC, pa.id DESC'
        );
        $statement->execute($params);
        $applications = $statement->fetchAll();

        if ($applications === []) {
            return [];
        }

        $applicationIds = array_map(static fn (array $application): int => (int) $application['id'], $applications);
        $answersByApplication = $this->answersForApplications($applicationIds);
        $details = [];

        foreach ($applications as $application) {
            $summaryResultId = (int) $application['summary_result_id'];
            $application['answers'] = $answersByApplication[(int) $application['id']] ?? [];
            $details[$summaryResultId] = $application;
        }

        return $details;
    }

    public function createApplication(array $data, array $answers): int
    {
        $statement = $this->db->prepare(
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
        $statement->execute([
            'patient_id' => $data['patient_id'],
            'instrument_id' => $data['instrument_id'],
            'instrument_version_id' => $data['instrument_version_id'],
            'professional_id' => $data['professional_id'] ?: null,
            'applied_at' => $data['applied_at'],
            'total_score' => $data['total_score'],
            'result_code' => $data['result_code'] ?: null,
            'result_label' => $data['result_label'] ?: null,
            'interpretation' => $data['interpretation'] ?: null,
            'professional_notes' => $data['professional_notes'] ?: null,
            'computed_data' => $data['computed_data'] ?? null,
            'summary_result_id' => $data['summary_result_id'] ?: null,
        ]);

        $applicationId = (int) $this->db->lastInsertId();
        $answerStatement = $this->db->prepare(
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
            $answerStatement->execute([
                'application_id' => $applicationId,
                'question_id' => $answer['question_id'] ?: null,
                'option_id' => $answer['option_id'] ?: null,
                'item_order' => $answer['item_order'],
                'question_snapshot' => $answer['question_snapshot'],
                'answer_label' => $answer['answer_label'] ?: null,
                'answer_value' => $answer['answer_value'] ?: null,
                'answer_score' => $answer['answer_score'],
                'answer_json' => $answer['answer_json'] ?? null,
            ]);
        }

        return $applicationId;
    }

    public function replaceDefinition(int $instrumentId, array $items, array $levels): void
    {
        $currentVersion = $this->activeVersionForInstrument($instrumentId);
        $nextVersionNumber = $currentVersion === null ? 1 : ((int) $currentVersion['version_number'] + 1);

        $this->db->beginTransaction();

        try {
            $retire = $this->db->prepare(
                'UPDATE psychometric_instrument_versions
                 SET status = "retired",
                     is_active = 0,
                     updated_at = NOW()
                 WHERE instrument_id = :instrument_id
                   AND is_active = 1'
            );
            $retire->execute(['instrument_id' => $instrumentId]);

            $versionStatement = $this->db->prepare(
                'INSERT INTO psychometric_instrument_versions (
                    instrument_id,
                    version_number,
                    version_label,
                    status,
                    scoring_config,
                    is_active,
                    created_at,
                    updated_at
                 )
                 VALUES (
                    :instrument_id,
                    :version_number,
                    :version_label,
                    "active",
                    :scoring_config,
                    1,
                    NOW(),
                    NOW()
                 )'
            );
            $versionStatement->execute([
                'instrument_id' => $instrumentId,
                'version_number' => $nextVersionNumber,
                'version_label' => 'v' . $nextVersionNumber,
                'scoring_config' => json_encode(['type' => 'sum'], JSON_UNESCAPED_UNICODE),
            ]);
            $versionId = (int) $this->db->lastInsertId();

            $maxScore = $this->insertQuestionsForVersion($versionId, $items);
            $this->insertRulesForVersion($versionId, $levels);
            $this->updateInstrumentScoreRange($instrumentId, $maxScore);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function findResultForPatient(int $patientId, int $resultId, ?int $professionalId = null, bool $includeAll = false): ?PsychometricResult
    {
        $params = [
            'patient_id' => $patientId,
            'id' => $resultId,
        ];
        $where = [
            'pr.patient_id = :patient_id',
            'pr.id = :id',
        ];

        if (!$includeAll && $professionalId !== null) {
            $where[] = '(pr.professional_id = :professional_id OR pr.professional_id IS NULL)';
            $params['professional_id'] = $professionalId;
        }

        $statement = $this->db->prepare(
            'SELECT pr.*,
                    pi.code AS instrument_code,
                    pi.name AS instrument_name,
                    pi.min_score,
                    pi.max_score,
                    pi.caution_cutoff,
                    pi.critical_cutoff,
                    u.name AS professional_name
             FROM psychometric_results pr
             INNER JOIN psychometric_instruments pi ON pi.id = pr.instrument_id
             LEFT JOIN users u ON u.id = pr.professional_id
             WHERE ' . implode(' AND ', $where) . '
             LIMIT 1'
        );
        $statement->execute($params);
        $row = $statement->fetch();

        return $row ? $this->mapResult($row) : null;
    }

    public function updateResult(int $patientId, int $resultId, array $data, ?int $professionalId = null, bool $includeAll = false): void
    {
        $params = [
            'patient_id' => $patientId,
            'id' => $resultId,
            'instrument_id' => $data['instrument_id'],
            'applied_at' => $data['applied_at'],
            'score' => $data['score'],
            'interpretation' => $data['interpretation'] ?: null,
            'professional_notes' => $data['professional_notes'] ?: null,
        ];
        $where = 'patient_id = :patient_id AND id = :id';

        if (!$includeAll && $professionalId !== null) {
            $where .= ' AND (professional_id = :professional_id OR professional_id IS NULL)';
            $params['professional_id'] = $professionalId;
        }

        $statement = $this->db->prepare(
            "UPDATE psychometric_results
             SET instrument_id = :instrument_id,
                 applied_at = :applied_at,
                 score = :score,
                 interpretation = :interpretation,
                 professional_notes = :professional_notes,
                 updated_at = NOW()
             WHERE {$where}"
        );
        $statement->execute($params);
    }

    public function setResultActive(int $patientId, int $resultId, bool $active, ?int $professionalId = null, bool $includeAll = false): void
    {
        $params = [
            'patient_id' => $patientId,
            'id' => $resultId,
            'is_active' => $active ? 1 : 0,
        ];
        $where = 'patient_id = :patient_id AND id = :id';

        if (!$includeAll && $professionalId !== null) {
            $where .= ' AND (professional_id = :professional_id OR professional_id IS NULL)';
            $params['professional_id'] = $professionalId;
        }

        $statement = $this->db->prepare(
            "UPDATE psychometric_results
             SET is_active = :is_active, updated_at = NOW()
             WHERE {$where}"
        );
        $statement->execute($params);
    }

    private function mapInstrument(array $row): PsychometricInstrument
    {
        return new PsychometricInstrument(
            (int) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            $row['description'],
            (int) $row['min_score'],
            (int) $row['max_score'],
            $row['caution_cutoff'] === null ? null : (int) $row['caution_cutoff'],
            $row['critical_cutoff'] === null ? null : (int) $row['critical_cutoff'],
            (bool) $row['is_active']
        );
    }

    private function activeVersionForInstrument(int $instrumentId): ?array
    {
        $statement = $this->db->prepare(
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

    private function insertQuestionsForVersion(int $versionId, array $items): int
    {
        $questionStatement = $this->db->prepare(
            'INSERT INTO psychometric_questions (
                instrument_version_id,
                question_group_id,
                item_order,
                code,
                label,
                question_text,
                prompt,
                question_type,
                is_required,
                min_value,
                max_value,
                response_fields,
                metadata,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :instrument_version_id,
                :question_group_id,
                :item_order,
                :code,
                :label,
                :question_text,
                :prompt,
                :question_type,
                1,
                :min_value,
                :max_value,
                :response_fields,
                :metadata,
                1,
                NOW(),
                NOW()
             )'
        );

        $order = 1;
        $maxScore = 0;
        foreach ($items as $item) {
            $questionText = trim((string) ($item['question'] ?? $item['question_text'] ?? ''));
            if ($questionText === '') {
                continue;
            }

            $type = $this->normalizeQuestionType((string) ($item['question_type'] ?? 'likert'));
            $options = $this->optionsFromText((string) ($item['options'] ?? ''), $type);
            $minValue = $this->numericOrNull($item['min_value'] ?? null);
            $maxValue = $this->numericOrNull($item['max_value'] ?? null);
            $groupId = $this->groupIdByName(trim((string) ($item['group_name'] ?? '')));

            $questionStatement->execute([
                'instrument_version_id' => $versionId,
                'question_group_id' => $groupId,
                'item_order' => $order,
                'code' => 'q_' . str_pad((string) $order, 2, '0', STR_PAD_LEFT),
                'label' => $questionText,
                'question_text' => $questionText,
                'prompt' => trim((string) ($item['prompt'] ?? '')) ?: null,
                'question_type' => $type,
                'min_value' => in_array($type, ['text_score', 'number'], true) ? $minValue : null,
                'max_value' => in_array($type, ['text_score', 'number'], true) ? $maxValue : null,
                'response_fields' => json_encode($this->responseFieldsForType($type), JSON_UNESCAPED_UNICODE),
                'metadata' => json_encode(['group_name' => trim((string) ($item['group_name'] ?? 'General')) ?: 'General'], JSON_UNESCAPED_UNICODE),
            ]);

            $questionId = (int) $this->db->lastInsertId();
            $this->insertOptionsForQuestion($questionId, $options, $type);
            $maxScore += $this->maxScoreForQuestion($options, $type, $maxValue);
            $order++;
        }

        return $maxScore;
    }

    private function insertOptionsForQuestion(int $questionId, array $options, string $type): void
    {
        if (!in_array($type, ['likert', 'select', 'yes_no'], true)) {
            return;
        }

        $statement = $this->db->prepare(
            'INSERT INTO psychometric_question_options (
                question_id,
                option_order,
                label,
                value,
                score,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :question_id,
                :option_order,
                :label,
                :value,
                :score,
                1,
                NOW(),
                NOW()
             )'
        );

        foreach ($options as $index => $option) {
            $statement->execute([
                'question_id' => $questionId,
                'option_order' => $index + 1,
                'label' => $option['label'],
                'value' => (string) $option['score'],
                'score' => $option['score'],
            ]);
        }
    }

    private function insertRulesForVersion(int $versionId, array $levels): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO psychometric_interpretation_rules (
                instrument_version_id,
                rule_order,
                code,
                label,
                min_score,
                max_score,
                interpretation,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :instrument_version_id,
                :rule_order,
                :code,
                :label,
                :min_score,
                :max_score,
                :interpretation,
                1,
                NOW(),
                NOW()
             )'
        );

        $order = 1;
        foreach ($levels as $level) {
            $label = trim((string) ($level['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $statement->execute([
                'instrument_version_id' => $versionId,
                'rule_order' => $order,
                'code' => $this->slug($label),
                'label' => $label,
                'min_score' => $this->numericOrNull($level['min_score'] ?? null) ?? 0,
                'max_score' => $this->numericOrNull($level['max_score'] ?? null),
                'interpretation' => trim((string) ($level['description'] ?? $level['interpretation'] ?? '')) ?: null,
            ]);
            $order++;
        }
    }

    private function updateInstrumentScoreRange(int $instrumentId, int $maxScore): void
    {
        $statement = $this->db->prepare(
            'UPDATE psychometric_instruments
             SET min_score = 0,
                 max_score = :max_score,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $instrumentId,
            'max_score' => max(1, $maxScore),
        ]);
    }

    private function optionsFromText(string $text, string $type): array
    {
        if ($type === 'yes_no') {
            return [
                ['label' => 'No', 'score' => 0],
                ['label' => 'Si', 'score' => 1],
            ];
        }

        $options = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($text)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            [$label, $score] = array_pad(explode('|', $line, 2), 2, '0');
            $options[] = [
                'label' => trim($label),
                'score' => (int) trim($score),
            ];
        }

        if ($options === [] && in_array($type, ['likert', 'select'], true)) {
            return [
                ['label' => 'Nunca', 'score' => 0],
                ['label' => 'Varios dias', 'score' => 1],
                ['label' => 'Mas de la mitad de los dias', 'score' => 2],
                ['label' => 'Casi todos los dias', 'score' => 3],
            ];
        }

        return $options;
    }

    private function maxScoreForQuestion(array $options, string $type, ?int $maxValue): int
    {
        if (in_array($type, ['text_score', 'number'], true)) {
            return max(0, (int) $maxValue);
        }

        $scores = array_map(static fn (array $option): int => (int) $option['score'], $options);

        return $scores === [] ? 0 : max($scores);
    }

    private function responseFieldsForType(string $type): array
    {
        if ($type === 'text_score') {
            return [
                ['name' => 'Respuesta', 'type' => 'text'],
                ['name' => 'Valor', 'type' => 'number'],
            ];
        }

        if ($type === 'number') {
            return [
                ['name' => 'Valor', 'type' => 'number'],
            ];
        }

        return [];
    }

    private function normalizeQuestionType(string $type): string
    {
        return in_array($type, ['likert', 'yes_no', 'select', 'text_score', 'number'], true) ? $type : 'likert';
    }

    private function numericOrNull(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    private function groupIdByName(string $name): int
    {
        $name = $name === '' ? 'General' : $name;
        $code = $this->slug($name);
        $statement = $this->db->prepare(
            'INSERT INTO psychometric_question_groups (code, name, is_active, created_at, updated_at)
             VALUES (:code, :name, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = 1, updated_at = NOW()'
        );
        $statement->execute(['code' => $code, 'name' => $name]);

        return (int) $this->db->query(
            'SELECT id FROM psychometric_question_groups WHERE code = ' . $this->db->quote($code) . ' LIMIT 1'
        )->fetchColumn();
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $value
        );
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: 'general';

        return trim($value, '_') ?: 'general';
    }

    private function questionsForVersion(int $versionId): array
    {
        $statement = $this->db->prepare(
            'SELECT pq.*,
                    pqg.name AS group_name
             FROM psychometric_questions pq
             LEFT JOIN psychometric_question_groups pqg ON pqg.id = pq.question_group_id
             WHERE pq.instrument_version_id = :version_id
               AND pq.is_active = 1
             ORDER BY pq.item_order ASC, pq.id ASC'
        );
        $statement->execute(['version_id' => $versionId]);
        $questions = $statement->fetchAll();

        if ($questions === []) {
            return [];
        }

        $questionIds = array_map(static fn (array $question): int => (int) $question['id'], $questions);
        $optionsByQuestion = $this->optionsForQuestions($questionIds);

        foreach ($questions as &$question) {
            $question['options'] = $optionsByQuestion[(int) $question['id']] ?? [];
        }

        return $questions;
    }

    private function optionsForQuestions(array $questionIds): array
    {
        if ($questionIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $statement = $this->db->prepare(
            "SELECT *
             FROM psychometric_question_options
             WHERE question_id IN ({$placeholders})
               AND is_active = 1
             ORDER BY question_id ASC, option_order ASC, id ASC"
        );
        $statement->execute($questionIds);

        $grouped = [];
        foreach ($statement->fetchAll() as $option) {
            $grouped[(int) $option['question_id']][] = $option;
        }

        return $grouped;
    }

    private function rulesForVersion(int $versionId): array
    {
        $statement = $this->db->prepare(
            'SELECT *
             FROM psychometric_interpretation_rules
             WHERE instrument_version_id = :version_id
               AND is_active = 1
             ORDER BY rule_order ASC, id ASC'
        );
        $statement->execute(['version_id' => $versionId]);

        return $statement->fetchAll();
    }

    /**
     * @param int[] $applicationIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function answersForApplications(array $applicationIds): array
    {
        if ($applicationIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($applicationIds), '?'));
        $statement = $this->db->prepare(
            "SELECT *
             FROM psychometric_answers
             WHERE application_id IN ({$placeholders})
             ORDER BY application_id ASC, item_order ASC, id ASC"
        );
        $statement->execute($applicationIds);

        $grouped = [];
        foreach ($statement->fetchAll() as $answer) {
            $answer['answer_data'] = [];
            if (!empty($answer['answer_json'])) {
                $decoded = json_decode((string) $answer['answer_json'], true);
                $answer['answer_data'] = is_array($decoded) ? $decoded : [];
            }

            $grouped[(int) $answer['application_id']][] = $answer;
        }

        return $grouped;
    }

    private function tableExists(string $table): bool
    {
        $statement = $this->db->query('SHOW TABLES LIKE ' . $this->db->quote($table));

        return $statement !== false && (bool) $statement->fetchColumn();
    }

    private function columnExists(string $table, string $column): bool
    {
        $statement = $this->db->query(
            'SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE ' . $this->db->quote($column)
        );

        return $statement !== false && (bool) $statement->fetchColumn();
    }

    private function mapResult(array $row): PsychometricResult
    {
        return new PsychometricResult(
            (int) $row['id'],
            (int) $row['patient_id'],
            (int) $row['instrument_id'],
            (string) $row['instrument_code'],
            (string) $row['instrument_name'],
            (int) $row['min_score'],
            (int) $row['max_score'],
            $row['caution_cutoff'] === null ? null : (int) $row['caution_cutoff'],
            $row['critical_cutoff'] === null ? null : (int) $row['critical_cutoff'],
            $row['professional_id'] === null ? null : (int) $row['professional_id'],
            $row['professional_name'] ?? null,
            (string) $row['applied_at'],
            (int) $row['score'],
            $row['interpretation'],
            $row['professional_notes'],
            (bool) $row['is_active'],
            $row['created_at']
        );
    }
}
