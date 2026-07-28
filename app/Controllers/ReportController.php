<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AiAnalysisService;
use App\Services\ClinicalSessionService;
use App\Services\MaintainerService;
use App\Services\PatientConsentService;
use App\Services\PatientService;
use App\Services\PsychometricService;
use Core\Request;
use Core\Response;
use Core\Session;
use RuntimeException;

final class ReportController
{
    private PatientService $patients;
    private ClinicalSessionService $sessions;
    private PatientConsentService $consents;
    private AiAnalysisService $analyses;
    private PsychometricService $psychometrics;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->patients = new PatientService();
        $this->sessions = new ClinicalSessionService();
        $this->consents = new PatientConsentService();
        $this->analyses = new AiAnalysisService();
        $this->psychometrics = new PsychometricService();
        $this->maintainers = new MaintainerService();
    }

    public function index(Request $request): Response
    {
        $user = $this->currentUser();
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'estado' => (string) $request->input('estado', 'todos'),
            'tipo' => (string) $request->input('tipo', 'todos'),
        ];
        $patients = $this->patients->filteredForUser(
            $user,
            $this->patients->normalizeFilter($filters['estado']),
            $filters['q']
        );
        $items = $this->reportItems($patients, $user);
        $items = $this->filterReportItems($items, $filters['tipo']);

        return Response::view('reports.index', [
            'items' => $items,
            'summary' => $this->summary($items),
            'filters' => $filters,
            'patientStatuses' => $this->maintainers->active(MaintainerService::PATIENT_STATUSES),
        ]);
    }

    public function show(Request $request): Response
    {
        return $this->render($request, false);
    }

    public function export(Request $request): Response
    {
        return $this->render($request, true);
    }

    private function render(Request $request, bool $exportMode): Response
    {
        $patientId = (int) $request->param('id');
        $user = $this->currentUser();

        try {
            $psychometricResults = $this->psychometrics->byPatient($patientId, $user);

            return Response::view('reports.patient', [
                'patient' => $this->patients->findForUser($patientId, $user),
                'sessions' => $this->sessions->byPatientForUser($patientId, $user),
                'consents' => $this->consents->byPatient($patientId, $user),
                'analyses' => $this->analyses->activeByPatient($patientId, $user),
                'psychometricResults' => $psychometricResults,
                'psychometricDetails' => $this->psychometrics->applicationDetailsForResults($psychometricResults, $user),
                'patientStatuses' => $this->maintainers->active(MaintainerService::PATIENT_STATUSES),
                'sessionModalities' => $this->maintainers->active(MaintainerService::SESSION_MODALITIES),
                'riskLevels' => $this->maintainers->active(MaintainerService::RISK_LEVELS),
                'consentTypes' => $this->maintainers->active(MaintainerService::CONSENT_TYPES),
                'exportMode' => $exportMode,
                'generatedAt' => date('Y-m-d H:i:s'),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/patients');
        }
    }

    private function reportItems(array $patients, array $user): array
    {
        $items = [];

        foreach ($patients as $patient) {
            $patientId = (int) $patient->id;
            $sessions = $this->sessions->byPatientForUser($patientId, $user);
            $consents = $this->consents->byPatient($patientId, $user);
            $analyses = $this->analyses->activeByPatient($patientId, $user);
            $psychometrics = $this->psychometrics->byPatient($patientId, $user);
            $activeSessions = array_values(array_filter($sessions, fn ($session): bool => $session->isActive));
            $acceptedAiConsent = array_values(array_filter($consents, fn ($consent): bool => $consent->isActive && $consent->accepted && $consent->consentType === 'analisis_ia'));
            $psychometricAlerts = array_values(array_filter($psychometrics, fn ($result): bool => $result->isActive && $result->severityTone() !== 'green'));
            $lastSession = $activeSessions[0] ?? $sessions[0] ?? null;

            $items[] = [
                'patient' => $patient,
                'sessions_total' => count($sessions),
                'sessions_active' => count($activeSessions),
                'has_ai_consent' => $acceptedAiConsent !== [],
                'ai_outputs' => count($analyses),
                'psychometric_total' => count($psychometrics),
                'psychometric_alerts' => count($psychometricAlerts),
                'last_session_at' => $lastSession?->sessionDate,
            ];
        }

        return $items;
    }

    private function filterReportItems(array $items, string $type): array
    {
        return match ($type) {
            'con_sesiones' => array_values(array_filter($items, fn (array $item): bool => $item['sessions_active'] > 0)),
            'pendiente_consentimiento_ia' => array_values(array_filter($items, fn (array $item): bool => !$item['has_ai_consent'])),
            'con_ia' => array_values(array_filter($items, fn (array $item): bool => $item['ai_outputs'] > 0)),
            'alertas_instrumentos' => array_values(array_filter($items, fn (array $item): bool => $item['psychometric_alerts'] > 0)),
            default => $items,
        };
    }

    private function summary(array $items): array
    {
        $withSessions = 0;
        $withAi = 0;
        $pendingConsent = 0;
        $withPsychometricAlerts = 0;

        foreach ($items as $item) {
            $withSessions += $item['sessions_active'] > 0 ? 1 : 0;
            $withAi += $item['ai_outputs'] > 0 ? 1 : 0;
            $pendingConsent += $item['has_ai_consent'] ? 0 : 1;
            $withPsychometricAlerts += $item['psychometric_alerts'] > 0 ? 1 : 0;
        }

        return [
            'total' => count($items),
            'with_sessions' => $withSessions,
            'with_ai' => $withAi,
            'pending_consent' => $pendingConsent,
            'with_psychometric_alerts' => $withPsychometricAlerts,
        ];
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
