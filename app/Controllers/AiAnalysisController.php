<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AiAnalysisService;
use App\Services\ClinicalSessionService;
use App\Services\MaintainerService;
use App\Services\PatientService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class AiAnalysisController
{
    private PatientService $patients;
    private AiAnalysisService $analyses;
    private ClinicalSessionService $sessions;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->patients = new PatientService();
        $this->analyses = new AiAnalysisService();
        $this->sessions = new ClinicalSessionService();
        $this->maintainers = new MaintainerService();
    }

    public function history(Request $request): Response
    {
        $patientId = (int) $request->param('id');

        try {
            return Response::view('ai.history', [
                'patient' => $this->patients->findForUser($patientId, $this->currentUser()),
                'analyses' => $this->analyses->byPatient($patientId, $this->currentUser()),
                'sessions' => $this->sessions->byPatientForUser($patientId, $this->currentUser()),
                'aiReviewStatuses' => $this->maintainers->active(MaintainerService::AI_REVIEW_STATUSES),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/patients');
        }
    }

    public function store(Request $request): Response
    {
        $patientId = (int) $request->param('id');

        try {
            $patient = $this->patients->findForUser($patientId, $this->currentUser());
            $this->analyses->createClinicalSummary($patient, $this->currentUser());
            Session::flash('success', 'Analisis IA generado y pendiente de revision profesional.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/patients');
        }

        return Response::redirect("/patients/{$patientId}");
    }

    public function activate(Request $request): Response
    {
        return $this->changeActiveState($request, true);
    }

    public function deactivate(Request $request): Response
    {
        return $this->changeActiveState($request, false);
    }

    public function review(Request $request): Response
    {
        $patientId = (int) $request->param('id');
        $analysisId = (int) $request->param('analysisId');

        try {
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->analyses->review($patientId, $analysisId, [
                'review_status' => (string) $request->input('review_status'),
                'professional_notes' => trim((string) $request->input('professional_notes')),
            ], $this->currentUser());
            Session::flash('success', 'Revision profesional guardada correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}");
    }

    private function changeActiveState(Request $request, bool $active): Response
    {
        $patientId = (int) $request->param('id');
        $analysisId = (int) $request->param('analysisId');

        try {
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->analyses->setActive($patientId, $analysisId, $active, $this->currentUser());
            Session::flash('success', $active ? 'Analisis IA activado correctamente.' : 'Analisis IA desactivado correctamente.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}");
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
