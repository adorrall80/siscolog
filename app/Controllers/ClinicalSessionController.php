<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ClinicalSessionService;
use App\Services\AssociatedPersonService;
use App\Services\MaintainerService;
use App\Services\PatientService;
use App\Services\SessionTopicSubtypeService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class ClinicalSessionController
{
    private PatientService $patients;
    private ClinicalSessionService $sessions;
    private AssociatedPersonService $associatedPeople;
    private MaintainerService $maintainers;
    private SessionTopicSubtypeService $topicSubtypes;

    public function __construct()
    {
        $this->patients = new PatientService();
        $this->sessions = new ClinicalSessionService();
        $this->associatedPeople = new AssociatedPersonService();
        $this->maintainers = new MaintainerService();
        $this->topicSubtypes = new SessionTopicSubtypeService();
    }

    public function create(Request $request): Response
    {
        $patientId = (int) $request->param('id');

        try {
            $topicTypes = $this->maintainers->activeVisible(
                MaintainerService::SESSION_TOPIC_TYPES,
                $this->currentUser()
            );

            return Response::view('sessions.create', [
                'patient' => $this->patients->findForUser($patientId, $this->currentUser()),
                'sessionModalities' => $this->maintainers->active(MaintainerService::SESSION_MODALITIES),
                'riskLevels' => $this->maintainers->active(MaintainerService::RISK_LEVELS),
                'participantTypes' => $this->maintainers->active(MaintainerService::SESSION_PARTICIPANT_TYPES),
                'associatedPeople' => $this->associatedPeople->byPatient($patientId, $this->currentUser()),
                'topicTypes' => $topicTypes,
                'topicSubtypesByType' => $this->topicSubtypes->activeGroupedByType($topicTypes, $this->currentUser()),
                'allTopicSubtypes' => $this->topicSubtypes->allActive(),
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
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->sessions->create($patientId, $this->sessionData($request));
            Session::flash('success', 'Sesion clinica registrada correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/patients/{$patientId}/sessions/create");
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

    private function changeActiveState(Request $request, bool $active): Response
    {
        $patientId = (int) $request->param('id');
        $sessionId = (int) $request->param('sessionId');

        try {
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->sessions->setActive($patientId, $sessionId, $active);
            Session::flash('success', $active ? 'Sesion clinica activada correctamente.' : 'Sesion clinica desactivada correctamente.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}");
    }

    private function sessionData(Request $request): array
    {
        $participants = $request->input('participants', []);
        $participants = is_array($participants) ? $participants : [];

        if (trim((string) ($participants['otro']['new_name'] ?? '')) !== '') {
            $participants['otro']['selected'] = '1';
        }

        return [
            'session_date' => trim((string) $request->input('session_date')),
            'professional_id' => (int) ($this->currentUser()['id'] ?? 0),
            'created_by_user_id' => (int) ($this->currentUser()['id'] ?? 0),
            'modality' => (string) $request->input(
                'modality',
                $this->maintainers->defaultCode(MaintainerService::SESSION_MODALITIES)
            ),
            'reason' => trim((string) $request->input('reason')),
            'subjective_note' => trim((string) $request->input('subjective_note')),
            'objective_note' => trim((string) $request->input('objective_note')),
            'clinical_impression' => trim((string) $request->input('clinical_impression')),
            'risk_level' => (string) $request->input(
                'risk_level',
                $this->maintainers->defaultCode(MaintainerService::RISK_LEVELS)
            ),
            'agreements' => trim((string) $request->input('agreements')),
            'next_steps' => trim((string) $request->input('next_steps')),
            'participants' => $participants,
            'topics' => is_array($request->input('topics', [])) ? $request->input('topics', []) : [],
            'topic_pairs' => is_array($request->input('topic_pairs', [])) ? $request->input('topic_pairs', []) : [],
            'topic_type_text' => trim((string) $request->input('topic_type_text')),
            'topic_subtype_text' => trim((string) $request->input('topic_subtype_text')),
        ];
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
