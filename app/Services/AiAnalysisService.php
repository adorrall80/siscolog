<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiAnalysisOutput;
use App\Models\ClinicalSession;
use App\Models\Patient;
use App\Models\PsychometricResult;
use App\Repositories\AiAnalysisRepository;
use InvalidArgumentException;
use RuntimeException;

final class AiAnalysisService
{
    private AiAnalysisRepository $analyses;
    private PatientConsentService $consents;
    private ClinicalSessionService $sessions;
    private MaintainerService $maintainers;
    private PsychometricService $psychometrics;
    private AiProviderService $aiProvider;

    public function __construct()
    {
        $this->analyses = new AiAnalysisRepository();
        $this->consents = new PatientConsentService();
        $this->sessions = new ClinicalSessionService();
        $this->maintainers = new MaintainerService();
        $this->psychometrics = new PsychometricService();
        $this->aiProvider = new AiProviderService();
    }

    /**
     * @return AiAnalysisOutput[]
     */
    public function byPatient(int $patientId, ?array $user = null): array
    {
        return $this->analyses->byPatient($patientId, $this->userId($user), $this->canSeeAll($user));
    }

    /**
     * @return AiAnalysisOutput[]
     */
    public function activeByPatient(int $patientId, ?array $user = null): array
    {
        return array_values(array_filter(
            $this->byPatient($patientId, $user),
            fn (AiAnalysisOutput $analysis): bool => $analysis->isActive
        ));
    }

    public function canAnalyze(int $patientId, ?array $user = null): bool
    {
        return $this->consents->hasActiveAccepted($patientId, 'analisis_ia', $user);
    }

    public function createClinicalSummary(Patient $patient, ?array $user = null): void
    {
        if (!$this->canAnalyze((int) $patient->id, $user)) {
            throw new InvalidArgumentException('IA bloqueada: falta consentimiento activo y aceptado para analisis asistido por IA.');
        }

        $userId = $this->userId($user);
        $sessions = $this->sessions->byPatientForUser((int) $patient->id, $user);
        $activeSessions = array_values(array_filter($sessions, fn (ClinicalSession $session): bool => $session->isActive));
        $activeSessionIds = $this->sessionIds($activeSessions);
        $activePsychometrics = array_values(array_filter(
            $this->psychometrics->byPatient((int) $patient->id, $user),
            fn (PsychometricResult $result): bool => $result->isActive
        ));
        $activePsychometricDetails = $this->psychometrics->applicationDetailsForResults($activePsychometrics, $user);
        $sourceFingerprint = $this->sourceFingerprint($activeSessions, $activePsychometrics, $activePsychometricDetails);

        if ($activeSessionIds === []) {
            throw new InvalidArgumentException('No hay sesiones clinicas activas para generar evolucion IA.');
        }

        $currentAnalysis = $this->currentAnalysisWithSameSources((int) $patient->id, $activeSessionIds, $sourceFingerprint, $user);

        if ($currentAnalysis !== null) {
            throw new InvalidArgumentException('Ya existe una evolucion IA vigente con las mismas sesiones activas y sin cambios clinicos registrados.');
        }

        $context = $this->analysisContext($patient, $activeSessions, $activePsychometrics, $activePsychometricDetails, $activeSessionIds, $sourceFingerprint);
        $promptText = $this->aiProvider->promptForReview($context);
        $output = $this->aiProvider->generateClinicalEvolution($context) ?? $this->localClinicalOutput(
            $patient,
            $activeSessions,
            $activePsychometrics,
            $activePsychometricDetails,
            $activeSessionIds,
            $sourceFingerprint
        );
        $output['fuentes']['sesiones_activas'] = $activeSessionIds;
        $output['fuentes']['huella_clinica'] = $sourceFingerprint;
        $output['limites'] = $output['limites'] ?? 'Salida de apoyo. No reemplaza juicio clinico, diagnostico profesional ni protocolo de riesgo.';

        $this->analyses->archiveForPatient((int) $patient->id, null, $userId);

        $this->analyses->create((int) $patient->id, [
            'requested_by' => $userId,
            'source_type' => 'historial_clinico_longitudinal',
            'source_ids' => json_encode($activeSessionIds),
            'model' => $this->aiProvider->configured() ? $this->aiProvider->model() : 'simulado-mvp',
            'prompt_version' => $this->aiProvider->configured() ? $this->aiProvider->promptVersion() : 'mvp-v1',
            'prompt_text' => $promptText,
            'output_json' => json_encode($output, JSON_UNESCAPED_UNICODE),
            'final_text' => $this->editableTextFromOutput($output),
            'selected_source' => 'ia',
            'review_status' => $this->maintainers->defaultCode(MaintainerService::AI_REVIEW_STATUSES) ?: 'pendiente',
        ]);
    }

    /**
     * @param ClinicalSession[] $activeSessions
     * @param PsychometricResult[] $activePsychometrics
     */
    private function localClinicalOutput(
        Patient $patient,
        array $activeSessions,
        array $activePsychometrics,
        array $activePsychometricDetails,
        array $activeSessionIds,
        string $sourceFingerprint
    ): array {
        return [
            'tipo' => 'evolucion_clinica_supervisada',
            'paciente' => [
                'codigo' => $patient->code,
                'nombre' => $patient->fullName,
            ],
            'fuentes' => [
                'sesiones_activas' => $activeSessionIds,
                'huella_clinica' => $sourceFingerprint,
            ],
            'resumen' => $this->summaryText($activeSessions),
            'evolucion_del_caso' => $this->caseEvolution($activeSessions),
            'factores_observados' => $this->observedFactors($activeSessions),
            'instrumentos_psicometricos' => $this->psychometricSummary($activePsychometrics, $activePsychometricDetails),
            'hipotesis_de_trabajo' => $this->workingHypotheses($activeSessions),
            'factores_protectores' => $this->protectiveFactors($activeSessions),
            'alertas' => $this->alerts($activeSessions, $activePsychometrics),
            'proximos_pasos_sugeridos' => $this->nextSteps($activeSessions),
            'preguntas_para_proxima_sesion' => $this->nextSessionQuestions($activeSessions),
            'limites' => 'Salida de apoyo. No reemplaza juicio clinico, diagnostico profesional ni protocolo de riesgo.',
            'motor_ia' => [
                'provider' => 'local',
                'model' => 'simulado-mvp',
                'prompt_version' => 'mvp-v1',
                'mode' => 'local_fallback',
            ],
        ];
    }

    /**
     * @param ClinicalSession[] $activeSessions
     * @param PsychometricResult[] $activePsychometrics
     * @param int[] $activeSessionIds
     * @return array<string, mixed>
     */
    private function analysisContext(Patient $patient, array $activeSessions, array $activePsychometrics, array $activePsychometricDetails, array $activeSessionIds, string $sourceFingerprint): array
    {
        return [
            'paciente' => [
                'codigo' => $patient->code,
                'estado' => $patient->status,
            ],
            'fuentes' => [
                'sesiones_activas' => $activeSessionIds,
                'huella_clinica' => $sourceFingerprint,
            ],
            'sesiones' => array_map(fn (ClinicalSession $session): array => [
                'id' => $session->id,
                'fecha' => $session->sessionDate,
                'modalidad' => $session->modality,
                'motivo' => $session->reason,
                'relato' => $session->subjectiveNote,
                'observacion' => $session->objectiveNote,
                'impresion_clinica' => $session->clinicalImpression,
                'riesgo' => $session->riskLevel,
                'acuerdos' => $session->agreements,
                'proximos_pasos' => $session->nextSteps,
                'participantes' => $session->participants,
                'temas' => $session->topics,
            ], $activeSessions),
            'instrumentos_psicometricos' => $this->psychometricSummary($activePsychometrics, $activePsychometricDetails),
        ];
    }

    /**
     * @param ClinicalSession[] $sessions
     * @return int[]
     */
    private function sessionIds(array $sessions): array
    {
        $ids = array_map(fn (ClinicalSession $session): int => (int) $session->id, $sessions);
        sort($ids);

        return $ids;
    }

    /**
     * @param int[] $activeSessionIds
     */
    private function sourceFingerprint(array $sessions, array $psychometrics, array $psychometricDetails = []): string
    {
        $sessionPayload = array_map(fn (ClinicalSession $session): array => [
            'id' => (int) $session->id,
            'session_date' => $session->sessionDate,
            'modality' => $session->modality,
            'reason' => $session->reason,
            'subjective_note' => $session->subjectiveNote,
            'objective_note' => $session->objectiveNote,
            'clinical_impression' => $session->clinicalImpression,
            'risk_level' => $session->riskLevel,
            'agreements' => $session->agreements,
            'next_steps' => $session->nextSteps,
            'participants' => $session->participants,
            'topics' => $session->topics,
            'updated_at' => $session->updatedAt,
        ], $sessions);

        $psychometricPayload = array_map(fn (PsychometricResult $result): array => [
            'id' => (int) $result->id,
            'instrument_id' => $result->instrumentId,
            'instrument' => $result->instrumentName,
            'applied_at' => $result->appliedAt,
            'score' => $result->score,
            'interpretation' => $result->interpretation,
            'professional_notes' => $result->professionalNotes,
            'detalle' => $this->fingerprintPsychometricDetail($psychometricDetails[(int) $result->id] ?? null),
        ], $psychometrics);

        usort($sessionPayload, fn (array $left, array $right): int => $left['id'] <=> $right['id']);
        usort($psychometricPayload, fn (array $left, array $right): int => $left['id'] <=> $right['id']);

        $payload = [
            'sesiones' => $sessionPayload,
            'instrumentos_psicometricos' => $psychometricPayload,
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param int[] $activeSessionIds
     */
    private function currentAnalysisWithSameSources(int $patientId, array $activeSessionIds, string $sourceFingerprint, ?array $user): ?AiAnalysisOutput
    {
        foreach ($this->byPatient($patientId, $user) as $analysis) {
            if (!$analysis->isActive) {
                continue;
            }

            if ($analysis->sourceType !== 'historial_clinico_longitudinal') {
                continue;
            }

            $sourceIds = json_decode((string) $analysis->sourceIds, true);

            if (!is_array($sourceIds)) {
                continue;
            }

            $sourceIds = array_map('intval', $sourceIds);
            sort($sourceIds);

            if ($sourceIds === $activeSessionIds) {
                $output = $analysis->output();

                if (($output['fuentes']['huella_clinica'] ?? null) === $sourceFingerprint) {
                    return $analysis;
                }
            }
        }

        return null;
    }

    public function setActive(int $patientId, int $analysisId, bool $active, ?array $user = null): void
    {
        $analysis = $this->analyses->findForPatient($patientId, $analysisId, $this->userId($user), $this->canSeeAll($user));

        if ($analysis === null) {
            throw new RuntimeException('Analisis IA no encontrado para este paciente.');
        }

        $this->analyses->setActive($analysisId, $active);
    }

    public function review(int $patientId, int $analysisId, array $data, ?array $user = null): void
    {
        $analysis = $this->analyses->findForPatient($patientId, $analysisId, $this->userId($user), $this->canSeeAll($user));

        if ($analysis === null) {
            throw new RuntimeException('Analisis IA no encontrado para este paciente.');
        }

        if (!$this->maintainers->isValid(MaintainerService::AI_REVIEW_STATUSES, (string) ($data['review_status'] ?? ''))) {
            throw new InvalidArgumentException('El estado de revision IA no es valido.');
        }

        $selectedSource = (string) ($data['selected_source'] ?? 'ia');
        if (!in_array($selectedSource, ['ia', 'externa'], true)) {
            throw new InvalidArgumentException('Selecciona el origen del texto que deseas guardar.');
        }

        $finalText = trim((string) ($data['final_text'] ?? ''));
        if ($finalText === '') {
            throw new InvalidArgumentException('El texto final de la evolucion no puede quedar vacio.');
        }

        $this->analyses->review($analysisId, [
            'review_status' => $data['review_status'],
            'final_text' => $finalText,
            'selected_source' => $selectedSource,
            'professional_notes' => trim((string) ($data['professional_notes'] ?? '')),
            'reviewed_by' => $data['reviewed_by'] ?? $this->userId($user),
        ]);
    }

    private function editableTextFromOutput(array $output): string
    {
        $sections = [
            'Resumen clinico' => $output['resumen'] ?? '',
            'Evolucion del caso' => $output['evolucion_del_caso'] ?? '',
            'Factores observados' => $output['factores_observados'] ?? [],
            'Hipotesis de trabajo' => $output['hipotesis_de_trabajo'] ?? [],
            'Factores protectores' => $output['factores_protectores'] ?? [],
            'Alertas' => $output['alertas'] ?? [],
            'Proximos pasos sugeridos' => $output['proximos_pasos_sugeridos'] ?? [],
            'Preguntas para la proxima sesion' => $output['preguntas_para_proxima_sesion'] ?? [],
        ];
        $blocks = [];

        foreach ($sections as $title => $value) {
            if (is_array($value)) {
                $value = implode("\n", array_map(fn ($item): string => '- ' . (string) $item, $value));
            }
            $value = trim((string) $value);
            if ($value !== '') {
                $blocks[] = $title . "\n" . $value;
            }
        }

        return implode("\n\n", $blocks);
    }

    private function userId(?array $user): ?int
    {
        $id = (int) ($user['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function canSeeAll(?array $user): bool
    {
        return (string) ($user['role'] ?? '') === 'administrador';
    }

    /**
     * @param ClinicalSession[] $sessions
     */
    private function summaryText(array $sessions): string
    {
        if ($sessions === []) {
            return 'No hay sesiones clinicas activas suficientes para construir un resumen.';
        }

        return 'Paciente en seguimiento psicologico inicial. En la sesion activa se registra motivo asociado a ansiedad situacional, con necesidad de explorar detonantes, red de apoyo y estrategias de regulacion emocional. La informacion disponible sugiere continuar evaluacion clinica antes de formular conclusiones diagnosticas.';
    }

    /**
     * @param ClinicalSession[] $sessions
     */
    private function caseEvolution(array $sessions): string
    {
        $count = count($sessions);

        if ($count === 0) {
            return 'No hay sesiones activas para estimar evolucion del caso.';
        }

        if ($count === 1) {
            return 'Linea base inicial: solo existe una sesion activa, por lo que aun no es posible comparar evolucion entre sesiones. Esta salida sirve como punto de partida para futuras comparaciones clinicas.';
        }

        return 'Evolucion longitudinal basada en ' . $count . ' sesiones activas: comparar cambios en motivo de sesion, nivel de riesgo, acuerdos, sintomas reportados y cumplimiento de proximos pasos.';
    }

    /**
     * @param ClinicalSession[] $sessions
     * @return string[]
     */
    private function observedFactors(array $sessions): array
    {
        $factors = [];

        foreach ($sessions as $session) {
            if ($session->reason) {
                $factors[] = 'Motivo registrado: ' . $session->reason;
            }

            if ($session->clinicalImpression) {
                $factors[] = 'Impresion clinica documentada: ' . $session->clinicalImpression;
            }

            if ($session->topics !== []) {
                $factors[] = 'Temas tratados: ' . implode(', ', $session->topics);
            }
        }

        return $factors === [] ? ['Sin factores suficientes registrados en sesiones activas.'] : array_slice($factors, 0, 8);
    }

    /**
     * @param PsychometricResult[] $results
     * @return array<int, array<string, mixed>>
     */
    private function psychometricSummary(array $results, array $details = []): array
    {
        if ($results === []) {
            return [[
                'estado' => 'sin_instrumentos_vigentes',
                'descripcion' => 'No hay instrumentos psicometricos vigentes incorporados a esta salida IA.',
            ]];
        }

        return array_map(function (PsychometricResult $result) use ($details): array {
            $detail = $details[(int) $result->id] ?? null;

            return [
                'instrumento' => $result->instrumentName,
                'fecha' => $result->appliedAt,
                'puntaje' => $result->score,
                'maximo' => $result->maxScore,
                'tono' => $result->severityTone(),
                'interpretacion' => $result->interpretation ?: 'Sin interpretacion profesional registrada.',
                'version' => is_array($detail) ? ($detail['version_label'] ?? null) : null,
                'respuestas_relevantes' => $this->psychometricAnswersForAi($detail),
            ];
        }, array_slice($results, 0, 8));
    }

    private function fingerprintPsychometricDetail(mixed $detail): array
    {
        if (!is_array($detail)) {
            return [];
        }

        return [
            'application_id' => (int) ($detail['id'] ?? 0),
            'version' => $detail['version_label'] ?? null,
            'answers' => array_map(static fn (array $answer): array => [
                'item_order' => (int) ($answer['item_order'] ?? 0),
                'question' => (string) ($answer['question_snapshot'] ?? ''),
                'answer' => (string) ($answer['answer_label'] ?? $answer['answer_value'] ?? ''),
                'score' => $answer['answer_score'] === null ? null : (int) $answer['answer_score'],
            ], array_slice($detail['answers'] ?? [], 0, 40)),
        ];
    }

    private function psychometricAnswersForAi(mixed $detail): array
    {
        if (!is_array($detail) || ($detail['answers'] ?? []) === []) {
            return [];
        }

        return array_map(static fn (array $answer): array => [
            'pregunta' => (string) ($answer['question_snapshot'] ?? ''),
            'respuesta' => (string) ($answer['answer_label'] ?? $answer['answer_value'] ?? ''),
            'puntaje' => $answer['answer_score'] === null ? null : (int) $answer['answer_score'],
        ], array_slice($detail['answers'], 0, 8));
    }

    /**
     * @param ClinicalSession[] $sessions
     * @return string[]
     */
    private function workingHypotheses(array $sessions): array
    {
        if ($sessions === []) {
            return ['Aun no hay informacion suficiente para proponer hipotesis de trabajo.'];
        }

        return [
            'Ansiedad situacional vinculada a estresores recientes, pendiente de precisar frecuencia, intensidad y contexto.',
            'Posible relacion entre sobrecarga cotidiana y aumento de preocupacion anticipatoria.',
            'Se recomienda diferenciar sintomas ansiosos de respuesta adaptativa esperable antes de establecer diagnostico.',
        ];
    }

    /**
     * @param ClinicalSession[] $sessions
     * @return string[]
     */
    private function protectiveFactors(array $sessions): array
    {
        if ($sessions === []) {
            return ['No se registran factores protectores suficientes.'];
        }

        return [
            'Paciente asiste a sesion y acepta iniciar proceso de evaluacion.',
            'Existe contacto de emergencia registrado, lo que facilita plan de apoyo si fuese necesario.',
            'No se observan alertas automaticas de riesgo alto en la informacion activa disponible.',
        ];
    }

    /**
     * @param ClinicalSession[] $sessions
     * @return string[]
     */
    private function alerts(array $sessions, array $psychometrics): array
    {
        $alerts = [];

        foreach ($sessions as $session) {
            if (in_array($session->riskLevel, ['alto', 'critico'], true)) {
                $alerts[] = 'Revisar protocolo clinico por nivel de riesgo ' . $session->riskLevel . '.';
            }
        }

        foreach ($psychometrics as $result) {
            if (!$result instanceof PsychometricResult || $result->severityTone() === 'green') {
                continue;
            }

            $alerts[] = 'Instrumento ' . $result->instrumentName . ' con puntaje '
                . $result->score . '/' . $result->maxScore
                . ' en rango de ' . ($result->severityTone() === 'red' ? 'revision prioritaria' : 'seguimiento') . '.';
        }

        return $alerts === [] ? ['Sin alertas automaticas de riesgo alto en sesiones o instrumentos vigentes.'] : array_slice($alerts, 0, 6);
    }

    /**
     * @param ClinicalSession[] $sessions
     * @return string[]
     */
    private function nextSteps(array $sessions): array
    {
        $steps = [];

        foreach ($sessions as $session) {
            if ($session->nextSteps) {
                $steps[] = $session->nextSteps;
            }
        }

        if ($steps === []) {
            return ['Registrar objetivos terapeuticos, acuerdos y seguimiento en la proxima sesion.'];
        }

        return array_slice($steps, 0, 3);
    }

    /**
     * @param ClinicalSession[] $sessions
     * @return string[]
     */
    private function nextSessionQuestions(array $sessions): array
    {
        if ($sessions === []) {
            return ['Registrar motivo de sesion, sintomas actuales, red de apoyo y objetivos terapeuticos.'];
        }

        return [
            'Que situaciones concretas aumentan la ansiedad durante la semana?',
            'Que estrategias usa actualmente para regular activacion fisiologica o preocupacion?',
            'Hay cambios recientes en sueno, apetito, concentracion o funcionamiento social/laboral?',
            'Que objetivos espera alcanzar en las proximas 3 a 4 sesiones?',
        ];
    }
}
