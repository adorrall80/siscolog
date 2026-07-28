<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AssociatedPersonService;
use App\Services\MaintainerService;
use App\Services\PatientFamilyRelationshipService;
use App\Services\PatientService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class PatientFamilyRelationshipController
{
    private PatientService $patients;
    private AssociatedPersonService $associatedPeople;
    private PatientFamilyRelationshipService $relationships;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->patients = new PatientService();
        $this->associatedPeople = new AssociatedPersonService();
        $this->relationships = new PatientFamilyRelationshipService();
        $this->maintainers = new MaintainerService();
    }

    public function index(Request $request): Response
    {
        try {
            $patientId = (int) $request->param('id');
            $patient = $this->patients->findForUser($patientId, $this->currentUser());
            $associatedPeople = $this->associatedPeople->byPatient($patientId, $this->currentUser());

            return Response::view('patients.relationships', [
                'patient' => $patient,
                'associatedPeople' => $associatedPeople,
                'participantTypes' => $this->maintainers->active(MaintainerService::SESSION_PARTICIPANT_TYPES),
                'familyGraph' => $this->relationships->graph($patient, $associatedPeople, $this->currentUser()),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/patients');
        }
    }

    public function storePerson(Request $request): Response
    {
        $patientId = (int) $request->param('id');

        try {
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->associatedPeople->findOrCreate(
                $patientId,
                (string) $request->input('participant_type'),
                (string) $request->input('display_name'),
                $this->currentUser()
            );
            Session::flash('success', 'Persona agregada al mapa de vinculos.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}/vinculos#family-map");
    }

    public function store(Request $request): Response
    {
        $patientId = (int) $request->param('id');

        try {
            $patient = $this->patients->findForUser($patientId, $this->currentUser());
            $this->relationships->create($patient, $this->associatedPeople->byPatient($patientId, $this->currentUser()), [
                'created_by_user_id' => (int) ($this->currentUser()['id'] ?? 0),
                'from_node_key' => (string) $request->input('from_node_key'),
                'relationship_label' => (string) $request->input('relationship_label'),
                'to_node_key' => (string) $request->input('to_node_key'),
                'replace_existing_relationship' => (string) $request->input('replace_existing_relationship'),
                'replace_relationship_id' => (string) $request->input('replace_relationship_id'),
            ]);
            Session::flash('success', 'Relacion familiar agregada correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}/vinculos#family-map");
    }

    public function deactivate(Request $request): Response
    {
        $patientId = (int) $request->param('id');
        $isAsync = (string) $request->input('async', '') === '1';

        try {
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->relationships->deactivate($patientId, (int) $request->param('relationshipId'), $this->currentUser());

            if ($isAsync) {
                return Response::json(['ok' => true]);
            }

            Session::flash('success', 'Relacion familiar quitada del diagrama.');
        } catch (RuntimeException $exception) {
            if ($isAsync) {
                return Response::json(['ok' => false, 'message' => $exception->getMessage()], 404);
            }

            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}/vinculos#family-map");
    }

    public function position(Request $request): Response
    {
        $patientId = (int) $request->param('id');

        try {
            $patient = $this->patients->findForUser($patientId, $this->currentUser());
            $this->relationships->saveNodePosition($patient, $this->associatedPeople->byPatient($patientId, $this->currentUser()), [
                'created_by_user_id' => (int) ($this->currentUser()['id'] ?? 0),
                'node_key' => (string) $request->input('node_key'),
                'x' => (string) $request->input('x'),
                'y' => (string) $request->input('y'),
            ]);

            return Response::json(['ok' => true]);
        } catch (InvalidArgumentException $exception) {
            return Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            return Response::json(['ok' => false, 'message' => $exception->getMessage()], 404);
        }
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
