<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database;
use PDO;

final class PsychometricInstrumentSeeder
{
    public function run(): void
    {
        $db = Database::connection();

        $this->seedCategories($db);
        $this->seedTypes($db);
        $this->seedGroups($db);

        foreach ($this->instruments() as $instrument) {
            $instrumentId = $this->upsertInstrument($db, $instrument);

            if (($instrument['questions'] ?? []) === []) {
                continue;
            }

            $versionId = $this->upsertVersion($db, $instrumentId, $instrument);
            $this->upsertQuestions($db, $versionId, $instrument);
            $this->upsertInterpretationRules($db, $versionId, $instrument);
        }
    }

    private function seedCategories(PDO $db): void
    {
        $statement = $db->prepare(
            'INSERT INTO psychometric_categories (code, name, description, is_active, created_at, updated_at)
             VALUES (:code, :name, :description, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                is_active = 1,
                updated_at = NOW()'
        );

        foreach ([
            ['salud_mental', 'Salud mental', 'Instrumentos orientados a salud mental y seguimiento clinico.'],
            ['personalizado', 'Personalizado', 'Instrumentos creados o adaptados por el equipo profesional.'],
        ] as [$code, $name, $description]) {
            $statement->execute(compact('code', 'name', 'description'));
        }
    }

    private function seedTypes(PDO $db): void
    {
        $statement = $db->prepare(
            'INSERT INTO psychometric_instrument_types (code, name, description, is_active, created_at, updated_at)
             VALUES (:code, :name, :description, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                is_active = 1,
                updated_at = NOW()'
        );

        foreach ([
            ['escala', 'Escala', 'Instrumento cuantitativo con puntaje e interpretacion por rango.'],
            ['test_clinico', 'Test clinico', 'Instrumento clinico o cualitativo con aplicacion supervisada.'],
            ['formulario', 'Formulario', 'Registro configurable sin finalidad diagnostica automatica.'],
        ] as [$code, $name, $description]) {
            $statement->execute(compact('code', 'name', 'description'));
        }
    }

    private function seedGroups(PDO $db): void
    {
        $statement = $db->prepare(
            'INSERT INTO psychometric_question_groups (code, name, description, is_active, created_at, updated_at)
             VALUES (:code, :name, :description, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                is_active = 1,
                updated_at = NOW()'
        );

        foreach ([
            ['general', 'General', 'Preguntas generales del instrumento.'],
            ['estado_animo', 'Estado de animo', 'Preguntas vinculadas a animo, interes, energia y desesperanza.'],
            ['ansiedad', 'Ansiedad', 'Preguntas vinculadas a preocupacion, tension, inquietud o miedo.'],
        ] as [$code, $name, $description]) {
            $statement->execute(compact('code', 'name', 'description'));
        }
    }

    private function upsertInstrument(PDO $db, array $instrument): int
    {
        $categoryId = $this->idByCode($db, 'psychometric_categories', $instrument['category']);
        $typeId = $this->idByCode($db, 'psychometric_instrument_types', $instrument['type']);

        $statement = $db->prepare(
            'INSERT INTO psychometric_instruments (
                category_id,
                instrument_type_id,
                code,
                name,
                measures,
                description,
                instructions,
                cautions,
                frequency,
                min_days,
                min_score,
                max_score,
                caution_cutoff,
                critical_cutoff,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :category_id,
                :instrument_type_id,
                :code,
                :name,
                :measures,
                :description,
                :instructions,
                :cautions,
                :frequency,
                :min_days,
                :min_score,
                :max_score,
                :caution_cutoff,
                :critical_cutoff,
                1,
                NOW(),
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                category_id = VALUES(category_id),
                instrument_type_id = VALUES(instrument_type_id),
                name = VALUES(name),
                measures = VALUES(measures),
                description = VALUES(description),
                instructions = VALUES(instructions),
                cautions = VALUES(cautions),
                frequency = VALUES(frequency),
                min_days = VALUES(min_days),
                min_score = VALUES(min_score),
                max_score = VALUES(max_score),
                caution_cutoff = VALUES(caution_cutoff),
                critical_cutoff = VALUES(critical_cutoff),
                is_active = 1,
                updated_at = NOW()'
        );

        $statement->execute([
            'category_id' => $categoryId,
            'instrument_type_id' => $typeId,
            'code' => $instrument['code'],
            'name' => $instrument['name'],
            'measures' => $instrument['measures'] ?? null,
            'description' => $instrument['description'] ?? null,
            'instructions' => $instrument['instructions'] ?? null,
            'cautions' => $instrument['cautions'] ?? null,
            'frequency' => $instrument['frequency'] ?? null,
            'min_days' => (int) ($instrument['min_days'] ?? 0),
            'min_score' => (int) ($instrument['min_score'] ?? 0),
            'max_score' => (int) $instrument['max_score'],
            'caution_cutoff' => $instrument['caution_cutoff'] ?? null,
            'critical_cutoff' => $instrument['critical_cutoff'] ?? null,
        ]);

        return $this->idByCode($db, 'psychometric_instruments', $instrument['code']);
    }

    private function upsertVersion(PDO $db, int $instrumentId, array $instrument): int
    {
        $existing = $db->prepare(
            'SELECT id FROM psychometric_instrument_versions
             WHERE instrument_id = :instrument_id AND version_number = 1
             LIMIT 1'
        );
        $existing->execute(['instrument_id' => $instrumentId]);
        $versionId = (int) ($existing->fetchColumn() ?: 0);

        $data = [
            'instrument_id' => $instrumentId,
            'version_number' => 1,
            'version_label' => 'v1',
            'status' => 'active',
            'scoring_config' => json_encode(['type' => 'sum'], JSON_UNESCAPED_UNICODE),
            'special_rules' => isset($instrument['special_rules'])
                ? json_encode($instrument['special_rules'], JSON_UNESCAPED_UNICODE)
                : null,
        ];

        if ($versionId > 0) {
            $statement = $db->prepare(
                'UPDATE psychometric_instrument_versions
                 SET version_label = :version_label,
                     status = :status,
                     scoring_config = :scoring_config,
                     special_rules = :special_rules,
                     is_active = 1,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $versionId,
                'version_label' => $data['version_label'],
                'status' => $data['status'],
                'scoring_config' => $data['scoring_config'],
                'special_rules' => $data['special_rules'],
            ]);

            return $versionId;
        }

        $statement = $db->prepare(
            'INSERT INTO psychometric_instrument_versions (
                instrument_id,
                version_number,
                version_label,
                status,
                scoring_config,
                special_rules,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :instrument_id,
                :version_number,
                :version_label,
                :status,
                :scoring_config,
                :special_rules,
                1,
                NOW(),
                NOW()
             )'
        );
        $statement->execute($data);

        return (int) $db->lastInsertId();
    }

    private function upsertQuestions(PDO $db, int $versionId, array $instrument): void
    {
        foreach ($instrument['questions'] as $index => $question) {
            $questionId = $this->upsertQuestion($db, $versionId, $index + 1, $question);
            $this->upsertOptions($db, $questionId, $question['options'] ?? $instrument['options'] ?? []);
        }
    }

    private function upsertQuestion(PDO $db, int $versionId, int $order, array $question): int
    {
        $code = $question['code'];
        $existing = $db->prepare(
            'SELECT id FROM psychometric_questions
             WHERE instrument_version_id = :version_id AND code = :code
             LIMIT 1'
        );
        $existing->execute(['version_id' => $versionId, 'code' => $code]);
        $questionId = (int) ($existing->fetchColumn() ?: 0);

        $groupId = isset($question['group'])
            ? $this->idByCode($db, 'psychometric_question_groups', $question['group'])
            : $this->idByCode($db, 'psychometric_question_groups', 'general');

        $data = [
            'instrument_version_id' => $versionId,
            'question_group_id' => $groupId,
            'item_order' => $order,
            'code' => $code,
            'label' => $question['label'] ?? $question['text'],
            'question_text' => $question['text'],
            'prompt' => $question['prompt'] ?? null,
            'question_type' => $question['type'] ?? 'likert',
            'is_required' => !array_key_exists('required', $question) || $question['required'] ? 1 : 0,
            'min_value' => $question['min'] ?? null,
            'max_value' => $question['max'] ?? null,
            'response_fields' => isset($question['response_fields'])
                ? json_encode($question['response_fields'], JSON_UNESCAPED_UNICODE)
                : null,
            'metadata' => isset($question['metadata'])
                ? json_encode($question['metadata'], JSON_UNESCAPED_UNICODE)
                : null,
        ];

        if ($questionId > 0) {
            $statement = $db->prepare(
                'UPDATE psychometric_questions
                 SET question_group_id = :question_group_id,
                     item_order = :item_order,
                     label = :label,
                     question_text = :question_text,
                     prompt = :prompt,
                     question_type = :question_type,
                     is_required = :is_required,
                     min_value = :min_value,
                     max_value = :max_value,
                     response_fields = :response_fields,
                     metadata = :metadata,
                     is_active = 1,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $questionId,
                'question_group_id' => $data['question_group_id'],
                'item_order' => $data['item_order'],
                'label' => $data['label'],
                'question_text' => $data['question_text'],
                'prompt' => $data['prompt'],
                'question_type' => $data['question_type'],
                'is_required' => $data['is_required'],
                'min_value' => $data['min_value'],
                'max_value' => $data['max_value'],
                'response_fields' => $data['response_fields'],
                'metadata' => $data['metadata'],
            ]);

            return $questionId;
        }

        $statement = $db->prepare(
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
                :is_required,
                :min_value,
                :max_value,
                :response_fields,
                :metadata,
                1,
                NOW(),
                NOW()
             )'
        );
        $statement->execute($data);

        return (int) $db->lastInsertId();
    }

    private function upsertOptions(PDO $db, int $questionId, array $options): void
    {
        foreach ($options as $index => $option) {
            [$label, $score] = $option;
            $value = (string) $score;
            $existing = $db->prepare(
                'SELECT id FROM psychometric_question_options
                 WHERE question_id = :question_id AND value = :value
                 LIMIT 1'
            );
            $existing->execute(['question_id' => $questionId, 'value' => $value]);
            $optionId = (int) ($existing->fetchColumn() ?: 0);

            $data = [
                'question_id' => $questionId,
                'option_order' => $index + 1,
                'label' => $label,
                'value' => $value,
                'score' => (int) $score,
            ];

            if ($optionId > 0) {
                $statement = $db->prepare(
                    'UPDATE psychometric_question_options
                     SET option_order = :option_order,
                         label = :label,
                         score = :score,
                         is_active = 1,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $statement->execute([
                    'id' => $optionId,
                    'option_order' => $data['option_order'],
                    'label' => $data['label'],
                    'score' => $data['score'],
                ]);
                continue;
            }

            $statement = $db->prepare(
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
            $statement->execute($data);
        }
    }

    private function upsertInterpretationRules(PDO $db, int $versionId, array $instrument): void
    {
        foreach ($instrument['rules'] ?? [] as $index => $rule) {
            $code = $rule['code'];
            $existing = $db->prepare(
                'SELECT id FROM psychometric_interpretation_rules
                 WHERE instrument_version_id = :version_id AND code = :code
                 LIMIT 1'
            );
            $existing->execute(['version_id' => $versionId, 'code' => $code]);
            $ruleId = (int) ($existing->fetchColumn() ?: 0);

            $data = [
                'instrument_version_id' => $versionId,
                'rule_order' => $index + 1,
                'code' => $code,
                'label' => $rule['label'],
                'min_score' => $rule['min'],
                'max_score' => $rule['max'],
                'interpretation' => $rule['interpretation'],
            ];

            if ($ruleId > 0) {
                $statement = $db->prepare(
                    'UPDATE psychometric_interpretation_rules
                     SET rule_order = :rule_order,
                         label = :label,
                         min_score = :min_score,
                         max_score = :max_score,
                         interpretation = :interpretation,
                         is_active = 1,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $statement->execute([
                    'id' => $ruleId,
                    'rule_order' => $data['rule_order'],
                    'label' => $data['label'],
                    'min_score' => $data['min_score'],
                    'max_score' => $data['max_score'],
                    'interpretation' => $data['interpretation'],
                ]);
                continue;
            }

            $statement = $db->prepare(
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
            $statement->execute($data);
        }
    }

    private function idByCode(PDO $db, string $table, string $code): int
    {
        $statement = $db->prepare("SELECT id FROM {$table} WHERE code = :code LIMIT 1");
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn();
    }

    private function instruments(): array
    {
        return [
            [
                'code' => 'phq_9',
                'name' => 'PHQ-9',
                'category' => 'salud_mental',
                'type' => 'escala',
                'measures' => 'Depresion / estado de animo',
                'description' => 'Cuestionario de salud del paciente para sintomas depresivos. Interpretacion referencial.',
                'instructions' => 'Marca como se ha sentido la persona durante las ultimas dos semanas.',
                'cautions' => 'No diagnostica por si solo. Debe interpretarse junto a entrevista, contexto y riesgo.',
                'frequency' => 'Cada 2 a 4 semanas',
                'min_days' => 14,
                'min_score' => 0,
                'max_score' => 27,
                'caution_cutoff' => 10,
                'critical_cutoff' => 20,
                'options' => $this->frequencyOptions(),
                'questions' => [
                    ['code' => 'phq_9_01', 'group' => 'estado_animo', 'text' => 'Poco interes o placer en hacer cosas.'],
                    ['code' => 'phq_9_02', 'group' => 'estado_animo', 'text' => 'Animo bajo, tristeza o desesperanza.'],
                    ['code' => 'phq_9_03', 'group' => 'estado_animo', 'text' => 'Problemas para dormir o dormir demasiado.'],
                    ['code' => 'phq_9_04', 'group' => 'estado_animo', 'text' => 'Cansancio o poca energia.'],
                    ['code' => 'phq_9_05', 'group' => 'estado_animo', 'text' => 'Poco apetito o comer en exceso.'],
                    ['code' => 'phq_9_06', 'group' => 'estado_animo', 'text' => 'Sentirse mal consigo mismo o con culpa.'],
                    ['code' => 'phq_9_07', 'group' => 'estado_animo', 'text' => 'Dificultad para concentrarse.'],
                    ['code' => 'phq_9_08', 'group' => 'estado_animo', 'text' => 'Moverse o hablar muy lento, o estar inquieto.'],
                    ['code' => 'phq_9_09', 'group' => 'estado_animo', 'text' => 'Pensamientos de hacerse dano o de que seria mejor no estar.'],
                ],
                'rules' => [
                    ['code' => 'minimo', 'label' => 'Minimo', 'min' => 0, 'max' => 4, 'interpretation' => 'Sintomas depresivos minimos o no relevantes en este registro.'],
                    ['code' => 'leve', 'label' => 'Leve', 'min' => 5, 'max' => 9, 'interpretation' => 'Sintomas leves del estado de animo. Requiere seguimiento segun contexto.'],
                    ['code' => 'moderado', 'label' => 'Moderado', 'min' => 10, 'max' => 14, 'interpretation' => 'Sintomas moderados. Contrastar con entrevista y funcionamiento.'],
                    ['code' => 'moderadamente_severo', 'label' => 'Moderadamente severo', 'min' => 15, 'max' => 19, 'interpretation' => 'Sintomas importantes. Requiere revision profesional oportuna.'],
                    ['code' => 'severo', 'label' => 'Severo', 'min' => 20, 'max' => null, 'interpretation' => 'Sintomas severos. Evaluar riesgo, red de apoyo y plan clinico.'],
                ],
            ],
            [
                'code' => 'gad_7',
                'name' => 'GAD-7',
                'category' => 'salud_mental',
                'type' => 'escala',
                'measures' => 'Ansiedad',
                'description' => 'Escala breve para sintomas de ansiedad generalizada. Interpretacion referencial.',
                'instructions' => 'Marca cuanto han estado presentes estas senales durante las ultimas dos semanas.',
                'cautions' => 'No diagnostica por si sola. Debe interpretarse junto a entrevista, contexto y riesgo.',
                'frequency' => 'Semanal o cada 2 semanas',
                'min_days' => 7,
                'min_score' => 0,
                'max_score' => 21,
                'caution_cutoff' => 10,
                'critical_cutoff' => 15,
                'options' => $this->frequencyOptions(),
                'questions' => [
                    ['code' => 'gad_7_01', 'group' => 'ansiedad', 'text' => 'Sentirse nervioso, ansioso o con tension.'],
                    ['code' => 'gad_7_02', 'group' => 'ansiedad', 'text' => 'No poder detener o controlar la preocupacion.'],
                    ['code' => 'gad_7_03', 'group' => 'ansiedad', 'text' => 'Preocuparse demasiado por distintas cosas.'],
                    ['code' => 'gad_7_04', 'group' => 'ansiedad', 'text' => 'Dificultad para relajarse.'],
                    ['code' => 'gad_7_05', 'group' => 'ansiedad', 'text' => 'Inquietud o dificultad para quedarse tranquilo.'],
                    ['code' => 'gad_7_06', 'group' => 'ansiedad', 'text' => 'Irritabilidad o molestarse con facilidad.'],
                    ['code' => 'gad_7_07', 'group' => 'ansiedad', 'text' => 'Miedo a que algo malo pueda pasar.'],
                ],
                'rules' => [
                    ['code' => 'minima', 'label' => 'Minima', 'min' => 0, 'max' => 4, 'interpretation' => 'No se observan sintomas relevantes de ansiedad en este registro.'],
                    ['code' => 'leve', 'label' => 'Leve', 'min' => 5, 'max' => 9, 'interpretation' => 'Sintomas leves de ansiedad. Observar evolucion.'],
                    ['code' => 'moderada', 'label' => 'Moderada', 'min' => 10, 'max' => 14, 'interpretation' => 'Sintomas moderados de ansiedad. Contrastar con entrevista y funcionamiento.'],
                    ['code' => 'grave', 'label' => 'Grave', 'min' => 15, 'max' => null, 'interpretation' => 'Sintomas severos de ansiedad. Requiere revision profesional y plan de apoyo.'],
                ],
            ],
            [
                'code' => 'dass_21',
                'name' => 'DASS-21',
                'category' => 'salud_mental',
                'type' => 'escala',
                'measures' => 'Depresion, ansiedad y estres',
                'description' => 'Escala de depresion, ansiedad y estres. Registrar puntaje total o subescala segun criterio profesional.',
                'instructions' => 'Registrar puntaje total o subescala segun forma de aplicacion definida por el profesional.',
                'cautions' => 'Usar como apoyo al juicio profesional, no como diagnostico automatico.',
                'frequency' => 'Segun seguimiento clinico',
                'min_days' => 14,
                'min_score' => 0,
                'max_score' => 63,
                'caution_cutoff' => 21,
                'critical_cutoff' => 42,
                'questions' => [],
            ],
        ];
    }

    private function frequencyOptions(): array
    {
        return [
            ['Nunca', 0],
            ['Varios dias', 1],
            ['Mas de la mitad de los dias', 2],
            ['Casi todos los dias', 3],
        ];
    }
}
