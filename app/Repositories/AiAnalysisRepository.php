<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AiAnalysisOutput;
use Core\Database;
use PDO;

final class AiAnalysisRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return AiAnalysisOutput[]
     */
    public function byPatient(int $patientId, ?int $requestedBy = null, bool $includeAll = false): array
    {
        if (!$includeAll && $requestedBy === null) {
            return [];
        }

        $sql = 'SELECT * FROM ai_analysis_outputs WHERE patient_id = :patient_id';
        $params = ['patient_id' => $patientId];

        if (!$includeAll) {
            $sql .= ' AND requested_by = :requested_by';
            $params['requested_by'] = $requestedBy;
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        $statement = $this->db->prepare(
            $sql
        );
        $statement->execute($params);

        return array_map(
            fn (array $row): AiAnalysisOutput => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function create(int $patientId, array $data): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO ai_analysis_outputs (
                patient_id,
                requested_by,
                source_type,
                source_ids,
                model,
                prompt_version,
                prompt_text,
                output_json,
                final_text,
                selected_source,
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
                :prompt_text,
                :output_json,
                :final_text,
                :selected_source,
                :review_status,
                1,
                NULL,
                NULL,
                NULL,
                NOW(),
                NOW()
             )'
        );

        $statement->execute([
            'patient_id' => $patientId,
            'requested_by' => $data['requested_by'],
            'source_type' => $data['source_type'],
            'source_ids' => $data['source_ids'],
            'model' => $data['model'],
            'prompt_version' => $data['prompt_version'],
            'prompt_text' => $data['prompt_text'] ?? null,
            'output_json' => $data['output_json'],
            'final_text' => $data['final_text'] ?? null,
            'selected_source' => $data['selected_source'] ?? 'ia',
            'review_status' => $data['review_status'],
        ]);
    }

    public function archiveForPatient(int $patientId, ?int $exceptId = null, ?int $requestedBy = null, bool $includeAll = false): void
    {
        if (!$includeAll && $requestedBy === null) {
            return;
        }

        $sql = 'UPDATE ai_analysis_outputs SET is_active = 0, updated_at = NOW() WHERE patient_id = :patient_id';
        $params = ['patient_id' => $patientId];

        if (!$includeAll) {
            $sql .= ' AND requested_by = :requested_by';
            $params['requested_by'] = $requestedBy;
        }

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    public function findForPatient(int $patientId, int $analysisId, ?int $requestedBy = null, bool $includeAll = false): ?AiAnalysisOutput
    {
        if (!$includeAll && $requestedBy === null) {
            return null;
        }

        $sql = 'SELECT * FROM ai_analysis_outputs
             WHERE id = :id AND patient_id = :patient_id';
        $params = [
            'id' => $analysisId,
            'patient_id' => $patientId,
        ];

        if (!$includeAll) {
            $sql .= ' AND requested_by = :requested_by';
            $params['requested_by'] = $requestedBy;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare(
            $sql
        );
        $statement->execute($params);
        $row = $statement->fetch();

        return $row ? $this->map($row) : null;
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->db->prepare(
            'UPDATE ai_analysis_outputs SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public function review(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE ai_analysis_outputs
             SET review_status = :review_status,
                 final_text = :final_text,
                 selected_source = :selected_source,
                 professional_notes = :professional_notes,
                 reviewed_by = :reviewed_by,
                 reviewed_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'review_status' => $data['review_status'],
            'final_text' => $data['final_text'],
            'selected_source' => $data['selected_source'],
            'professional_notes' => $data['professional_notes'] ?: null,
            'reviewed_by' => $data['reviewed_by'],
        ]);
    }

    private function map(array $row): AiAnalysisOutput
    {
        return new AiAnalysisOutput(
            (int) $row['id'],
            (int) $row['patient_id'],
            $row['requested_by'] === null ? null : (int) $row['requested_by'],
            (string) $row['source_type'],
            $row['source_ids'],
            $row['model'],
            (string) $row['prompt_version'],
            $row['prompt_text'] ?? null,
            (string) $row['output_json'],
            $row['final_text'] ?? null,
            (string) ($row['selected_source'] ?? 'ia'),
            (string) $row['review_status'],
            (bool) $row['is_active'],
            $row['professional_notes'],
            $row['reviewed_by'] === null ? null : (int) $row['reviewed_by'],
            $row['reviewed_at'],
            $row['created_at']
        );
    }
}
