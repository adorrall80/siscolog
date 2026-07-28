<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PatientService;
use App\Services\AiAnalysisService;
use App\Services\ClinicalSessionService;
use App\Services\MaintainerService;
use App\Services\PatientConsentService;
use App\Services\PsychometricService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class PatientController
{
    private PatientService $patients;
    private AiAnalysisService $analyses;
    private ClinicalSessionService $sessions;
    private MaintainerService $maintainers;
    private PatientConsentService $consents;
    private PsychometricService $psychometrics;

    public function __construct()
    {
        $this->patients = new PatientService();
        $this->analyses = new AiAnalysisService();
        $this->sessions = new ClinicalSessionService();
        $this->maintainers = new MaintainerService();
        $this->consents = new PatientConsentService();
        $this->psychometrics = new PsychometricService();
    }

    public function index(Request $request): Response
    {
        $activeFilter = $this->patients->normalizeFilter((string) $request->input('estado', 'todos'));
        $activeSearch = $this->patients->normalizeSearch((string) $request->input('q', ''));
        $patients = $this->patients->filteredForUser($this->currentUser(), $activeFilter, $activeSearch);

        return Response::view('patients.index', [
            'patients' => $patients,
            'summary' => $this->patients->summary($patients),
            'activeFilter' => $activeFilter,
            'activeSearch' => $activeSearch,
            'patientStatuses' => $this->maintainers->active(MaintainerService::PATIENT_STATUSES),
        ]);
    }

    public function create(Request $request): Response
    {
        return Response::view('patients.create', [
            'patientStatuses' => $this->maintainers->active(MaintainerService::PATIENT_STATUSES),
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $this->patients->create($this->patientData($request));
            Session::flash('success', 'Paciente creado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/patients/create');
        }

        return Response::redirect('/patients');
    }

    public function show(Request $request): Response
    {
        try {
            $patientId = (int) $request->param('id');

            return Response::view('patients.show', [
                'patient' => $this->patients->findForUser($patientId, $this->currentUser()),
                'sessions' => $this->sessions->byPatientForUser($patientId, $this->currentUser()),
                'consents' => $this->consents->byPatient($patientId, $this->currentUser()),
                'analyses' => $this->analyses->activeByPatient($patientId, $this->currentUser()),
                'psychometricResults' => $this->psychometrics->byPatient($patientId, $this->currentUser()),
                'canAnalyzeWithAi' => $this->analyses->canAnalyze($patientId, $this->currentUser()),
                'patientStatuses' => $this->maintainers->active(MaintainerService::PATIENT_STATUSES),
                'sessionModalities' => $this->maintainers->active(MaintainerService::SESSION_MODALITIES),
                'riskLevels' => $this->maintainers->active(MaintainerService::RISK_LEVELS),
                'consentTypes' => $this->maintainers->active(MaintainerService::CONSENT_TYPES),
                'participantTypes' => $this->maintainers->active(MaintainerService::SESSION_PARTICIPANT_TYPES),
                'aiReviewStatuses' => $this->maintainers->active(MaintainerService::AI_REVIEW_STATUSES),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/patients');
        }
    }

    public function edit(Request $request): Response
    {
        try {
            return Response::view('patients.edit', [
                'patient' => $this->patients->findForUser((int) $request->param('id'), $this->currentUser()),
                'patientStatuses' => $this->maintainers->active(MaintainerService::PATIENT_STATUSES),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/patients');
        }
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');

        try {
            $this->patients->findForUser($id, $this->currentUser());
            $this->patients->update($id, $this->patientData($request, true));
            Session::flash('success', 'Paciente actualizado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/patients/{$id}/edit");
        }

        return Response::redirect("/patients/{$id}");
    }

    public function activate(Request $request): Response
    {
        return $this->changeActiveState($request, true);
    }

    public function deactivate(Request $request): Response
    {
        return $this->changeActiveState($request, false);
    }

    private function changeActiveState(Request $request, bool $active): Response
    {
        try {
            $patientId = (int) $request->param('id');
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->patients->setActive($patientId, $active);
            Session::flash('success', $active ? 'Paciente activado correctamente.' : 'Paciente desactivado correctamente.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect('/patients');
    }

    private function patientData(Request $request, bool $includeStatus = false): array
    {
        $data = [
            'code' => trim((string) $request->input('code')),
            'full_name' => trim((string) $request->input('full_name')),
            'birth_date' => trim((string) $request->input('birth_date')),
            'email' => trim((string) $request->input('email')),
            'phone' => trim((string) $request->input('phone')),
            'emergency_name' => trim((string) $request->input('emergency_name')),
            'emergency_relationship' => trim((string) $request->input('emergency_relationship')),
            'emergency_phone' => trim((string) $request->input('emergency_phone')),
            'emergency_email' => trim((string) $request->input('emergency_email')),
        ];

        if ($includeStatus) {
            $data['status'] = (string) $request->input(
                'status',
                $this->maintainers->defaultCode(MaintainerService::PATIENT_STATUSES)
            );
        } else {
            $data['status'] = $this->maintainers->defaultCode(MaintainerService::PATIENT_STATUSES);
        }

        $user = $this->currentUser();
        if ((string) ($user['role'] ?? '') !== 'administrador') {
            $data['assigned_professional_id'] = (int) ($user['id'] ?? 0);
        }

        return $data;
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
