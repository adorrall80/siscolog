<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PsychometricInstrument;
use App\Models\PsychometricResult;
use App\Repositories\PsychometricRepository;
use InvalidArgumentException;
use RuntimeException;

final class PsychometricService
{
    private PsychometricRepository $psychometrics;

    public function __construct()
    {
        $this->psychometrics = new PsychometricRepository();
    }

    /**
     * @return PsychometricInstrument[]
     */
    public function activeInstruments(): array
    {
        return $this->psychometrics->activeInstruments();
    }

    /**
     * @return PsychometricInstrument[]
     */
    public function allInstruments(): array
    {
        return $this->psychometrics->allInstruments();
    }

    public function findInstrument(int $id): PsychometricInstrument
    {
        $instrument = $this->psychometrics->findInstrument($id);

        if ($instrument === null) {
            throw new RuntimeException('Instrumento psicometrico no encontrado.');
        }

        return $instrument;
    }

    public function updateInstrument(int $id, array $data): void
    {
        $this->findInstrument($id);
        $this->validateInstrumentData($data);
        $this->psychometrics->updateInstrument($id, $data);
    }

    public function setInstrumentActive(int $id, bool $active): void
    {
        $this->findInstrument($id);
        $this->psychometrics->setInstrumentActive($id, $active);
    }

    public function instrumentDetail(int $id): array
    {
        $detail = $this->psychometrics->instrumentDetail($id);

        if ($detail === null) {
            throw new RuntimeException('Instrumento psicometrico no encontrado.');
        }

        return $detail;
    }

    public function questionGroups(): array
    {
        return $this->psychometrics->questionGroups();
    }

    public function replaceDefinition(int $instrumentId, array $items, array $levels): void
    {
        $this->findInstrument($instrumentId);

        if ($items === []) {
            throw new InvalidArgumentException('Debes definir al menos una pregunta.');
        }

        $this->psychometrics->replaceDefinition($instrumentId, $items, $levels);
    }

    /**
     * @return PsychometricResult[]
     */
    public function byPatient(int $patientId, ?array $user = null): array
    {
        $includeAll = $this->isGlobalRole($user);
        $userId = (int) ($user['id'] ?? 0);

        return $this->psychometrics->resultsByPatient($patientId, $userId > 0 ? $userId : null, $includeAll);
    }

    /**
     * @param array<int, PsychometricResult|int> $results
     * @return array<int, array<string, mixed>>
     */
    public function applicationDetailsForResults(array $results, ?array $user = null): array
    {
        $resultIds = array_map(
            static fn (PsychometricResult|int $result): int => $result instanceof PsychometricResult ? (int) $result->id : (int) $result,
            $results
        );
        $userId = (int) ($user['id'] ?? 0);

        return $this->psychometrics->applicationDetailsForResults(
            $resultIds,
            $userId > 0 ? $userId : null,
            $this->isGlobalRole($user)
        );
    }

    public function createResult(int $patientId, array $data, ?array $user = null): int
    {
        [$instrument, $score, $appliedAt] = $this->validatedResultData($data);

        return $this->psychometrics->createResult([
            'patient_id' => $patientId,
            'instrument_id' => (int) $instrument->id,
            'professional_id' => (int) ($user['id'] ?? 0) ?: null,
            'applied_at' => str_replace('T', ' ', $appliedAt),
            'score' => $score,
            'interpretation' => $this->interpretationFor($instrument, $score, (string) ($data['interpretation'] ?? '')),
            'professional_notes' => trim((string) ($data['professional_notes'] ?? '')),
        ]);
    }

    public function createApplication(int $patientId, int $instrumentId, array $data, ?array $user = null): int
    {
        $detail = $this->instrumentDetail($instrumentId);
        $instrument = $detail['instrument'];
        $version = $detail['version'];
        $questions = $detail['questions'];

        if ($version === null || $questions === []) {
            throw new InvalidArgumentException('Este instrumento aun no tiene preguntas configuradas para aplicacion guiada.');
        }

        $appliedAt = trim((string) ($data['applied_at'] ?? ''));

        if ($appliedAt === '') {
            throw new InvalidArgumentException('La fecha de aplicacion es obligatoria.');
        }

        [$score, $answers] = $this->answersFromDetail($questions, (array) ($data['answers'] ?? []));
        $interpretation = $this->ruleForScore((array) $detail['rules'], $score);
        $professionalNotes = trim((string) ($data['professional_notes'] ?? ''));

        $summaryResultId = $this->psychometrics->createResult([
            'patient_id' => $patientId,
            'instrument_id' => (int) $instrument['id'],
            'professional_id' => $this->userId($user),
            'applied_at' => str_replace('T', ' ', $appliedAt),
            'score' => $score,
            'interpretation' => $interpretation['interpretation'] ?? $interpretation['label'] ?? 'Interpretacion pendiente de revision profesional.',
            'professional_notes' => $professionalNotes,
        ]);

        return $this->psychometrics->createApplication([
            'patient_id' => $patientId,
            'instrument_id' => (int) $instrument['id'],
            'instrument_version_id' => (int) $version['id'],
            'professional_id' => $this->userId($user),
            'applied_at' => str_replace('T', ' ', $appliedAt),
            'total_score' => $score,
            'result_code' => $interpretation['code'] ?? null,
            'result_label' => $interpretation['label'] ?? null,
            'interpretation' => $interpretation['interpretation'] ?? null,
            'professional_notes' => $professionalNotes,
            'computed_data' => json_encode([
                'source' => 'guided_application',
                'question_count' => count($questions),
                'answered_count' => count($answers),
            ], JSON_UNESCAPED_UNICODE),
            'summary_result_id' => $summaryResultId,
        ], $answers);
    }

    public function findResultForPatient(int $patientId, int $resultId, ?array $user = null): PsychometricResult
    {
        $result = $this->psychometrics->findResultForPatient(
            $patientId,
            $resultId,
            $this->userId($user),
            $this->isGlobalRole($user)
        );

        if ($result === null) {
            throw new RuntimeException('Resultado psicometrico no encontrado para este paciente.');
        }

        return $result;
    }

    public function updateResult(int $patientId, int $resultId, array $data, ?array $user = null): void
    {
        $this->findResultForPatient($patientId, $resultId, $user);
        [$instrument, $score, $appliedAt] = $this->validatedResultData($data);

        $this->psychometrics->updateResult($patientId, $resultId, [
            'instrument_id' => (int) $instrument->id,
            'applied_at' => $appliedAt,
            'score' => $score,
            'interpretation' => $this->interpretationFor($instrument, $score, (string) ($data['interpretation'] ?? '')),
            'professional_notes' => trim((string) ($data['professional_notes'] ?? '')),
        ], $this->userId($user), $this->isGlobalRole($user));
    }

    public function setActive(int $patientId, int $resultId, bool $active, ?array $user = null): void
    {
        $includeAll = $this->isGlobalRole($user);
        $userId = (int) ($user['id'] ?? 0);

        $this->psychometrics->setResultActive($patientId, $resultId, $active, $userId > 0 ? $userId : null, $includeAll);
    }

    public function summary(array $results): array
    {
        $active = array_values(array_filter($results, fn (PsychometricResult $result): bool => $result->isActive));
        $alerts = array_values(array_filter($active, fn (PsychometricResult $result): bool => $result->severityTone() !== 'green'));

        return [
            'total' => count($results),
            'active' => count($active),
            'alerts' => count($alerts),
        ];
    }

    private function interpretationFor(PsychometricInstrument $instrument, int $score, string $manual): string
    {
        $manual = trim($manual);

        if ($manual !== '') {
            return $manual;
        }

        if ($instrument->criticalCutoff !== null && $score >= $instrument->criticalCutoff) {
            return 'Puntaje en rango alto. Requiere revision profesional y contraste clinico.';
        }

        if ($instrument->cautionCutoff !== null && $score >= $instrument->cautionCutoff) {
            return 'Puntaje en rango de seguimiento. Interpretar junto a entrevista clinica.';
        }

        return 'Puntaje dentro de rango bajo/referencial. No constituye diagnostico por si solo.';
    }

    private function validatedResultData(array $data): array
    {
        $instrumentId = (int) ($data['instrument_id'] ?? 0);
        $instrument = $this->psychometrics->findInstrument($instrumentId);

        if ($instrument === null || !$instrument->isActive) {
            throw new InvalidArgumentException('Debes seleccionar un instrumento activo.');
        }

        $score = (int) ($data['score'] ?? -1);

        if ($score < $instrument->minScore || $score > $instrument->maxScore) {
            throw new InvalidArgumentException(
                'El puntaje debe estar entre '
                . $instrument->minScore
                . ' y '
                . $instrument->maxScore
                . ' para '
                . $instrument->name
                . '.'
            );
        }

        $appliedAt = trim((string) ($data['applied_at'] ?? ''));

        if ($appliedAt === '') {
            throw new InvalidArgumentException('La fecha de aplicacion es obligatoria.');
        }

        return [$instrument, $score, str_replace('T', ' ', $appliedAt)];
    }

    private function answersFromDetail(array $questions, array $inputAnswers): array
    {
        $score = 0;
        $answers = [];

        foreach ($questions as $question) {
            $questionId = (int) $question['id'];
            $questionType = (string) ($question['question_type'] ?? 'likert');
            $rawAnswer = $inputAnswers[$questionId] ?? null;

            if (!empty($question['is_required']) && ($rawAnswer === null || $rawAnswer === '' || $rawAnswer === [])) {
                throw new InvalidArgumentException('Debes responder todas las preguntas obligatorias.');
            }

            if (in_array($questionType, ['text_score', 'number'], true)) {
                $text = is_array($rawAnswer) ? trim((string) ($rawAnswer['text'] ?? '')) : '';
                $value = is_array($rawAnswer) ? (string) ($rawAnswer['score'] ?? '') : (string) $rawAnswer;
                $answerScore = $this->boundedScore($value, $question);
                $score += $answerScore;
                $answers[] = [
                    'question_id' => $questionId,
                    'option_id' => null,
                    'item_order' => (int) $question['item_order'],
                    'question_snapshot' => (string) $question['question_text'],
                    'answer_label' => $questionType === 'text_score' ? $text : (string) $answerScore,
                    'answer_value' => $questionType === 'text_score' ? $text : (string) $answerScore,
                    'answer_score' => $answerScore,
                    'answer_json' => json_encode([
                        'question_code' => $question['code'] ?? null,
                        'question_type' => $questionType,
                        'group' => $question['group_name'] ?? null,
                        'text' => $text,
                    ], JSON_UNESCAPED_UNICODE),
                ];
                continue;
            }

            $selectedOption = $this->selectedOption($question, (string) $rawAnswer);

            if ($selectedOption === null) {
                throw new InvalidArgumentException('Una respuesta seleccionada no pertenece al instrumento.');
            }

            $answerScore = $selectedOption['score'] === null ? 0 : (int) $selectedOption['score'];
            $score += $answerScore;
            $answers[] = [
                'question_id' => $questionId,
                'option_id' => (int) $selectedOption['id'],
                'item_order' => (int) $question['item_order'],
                'question_snapshot' => (string) $question['question_text'],
                'answer_label' => (string) $selectedOption['label'],
                'answer_value' => (string) $selectedOption['value'],
                'answer_score' => $answerScore,
                'answer_json' => json_encode([
                    'question_code' => $question['code'] ?? null,
                    'question_type' => $question['question_type'] ?? null,
                    'group' => $question['group_name'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
            ];
        }

        return [$score, $answers];
    }

    private function selectedOption(array $question, string $submittedValue): ?array
    {
        $submittedValue = trim($submittedValue);

        foreach ($question['options'] ?? [] as $option) {
            $optionScore = $option['score'] === null ? '0' : (string) (int) $option['score'];
            $optionValue = $option['value'] === null ? $optionScore : (string) $option['value'];

            if ($optionScore === $submittedValue || $optionValue === $submittedValue) {
                return $option;
            }
        }

        // Compatibilidad con aplicaciones antiguas que enviaban el id interno de la opcion.
        foreach ($question['options'] ?? [] as $option) {
            if ((string) $option['id'] === $submittedValue) {
                return $option;
            }
        }

        return null;
    }

    private function boundedScore(string $value, array $question): int
    {
        if ($value === '') {
            throw new InvalidArgumentException('Debes ingresar el valor de puntaje solicitado.');
        }

        $score = (int) $value;
        $min = $question['min_value'] === null || $question['min_value'] === '' ? null : (int) $question['min_value'];
        $max = $question['max_value'] === null || $question['max_value'] === '' ? null : (int) $question['max_value'];

        if ($min !== null && $score < $min) {
            throw new InvalidArgumentException('Un puntaje ingresado esta bajo el minimo permitido.');
        }

        if ($max !== null && $score > $max) {
            throw new InvalidArgumentException('Un puntaje ingresado supera el maximo permitido.');
        }

        return $score;
    }

    private function ruleForScore(array $rules, int $score): array
    {
        foreach ($rules as $rule) {
            $min = $rule['min_score'] === null ? null : (int) $rule['min_score'];
            $max = $rule['max_score'] === null ? null : (int) $rule['max_score'];

            if (($min === null || $score >= $min) && ($max === null || $score <= $max)) {
                return $rule;
            }
        }

        return [
            'code' => null,
            'label' => 'Sin rango',
            'interpretation' => 'El puntaje no coincide con un rango configurado. Requiere revision profesional.',
        ];
    }

    private function validateInstrumentData(array $data): void
    {
        if (trim((string) ($data['code'] ?? '')) === '') {
            throw new InvalidArgumentException('El codigo del instrumento es obligatorio.');
        }

        if (!preg_match('/^[a-z0-9_\\-]+$/', (string) $data['code'])) {
            throw new InvalidArgumentException('El codigo solo puede usar minusculas, numeros, guion y guion bajo.');
        }

        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new InvalidArgumentException('El nombre del instrumento es obligatorio.');
        }

        $min = (int) ($data['min_score'] ?? 0);
        $max = (int) ($data['max_score'] ?? 0);

        if ($max <= $min) {
            throw new InvalidArgumentException('El puntaje maximo debe ser mayor al minimo.');
        }
    }

    private function userId(?array $user): ?int
    {
        $id = (int) ($user['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function isGlobalRole(?array $user): bool
    {
        return (string) ($user['role'] ?? '') === 'administrador';
    }
}
