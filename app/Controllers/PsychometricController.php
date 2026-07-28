<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PatientService;
use App\Services\PsychometricService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class PsychometricController
{
    private PatientService $patients;
    private PsychometricService $psychometrics;

    public function __construct()
    {
        $this->patients = new PatientService();
        $this->psychometrics = new PsychometricService();
    }

    public function create(Request $request): Response
    {
        $patientId = (int) $request->param('id');

        try {
            return Response::view('psychometrics.create', [
                'patient' => $this->patients->findForUser($patientId, $this->currentUser()),
                'instruments' => $this->psychometrics->activeInstruments(),
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
            $this->psychometrics->createResult($patientId, [
                'instrument_id' => (string) $request->input('instrument_id'),
                'applied_at' => (string) $request->input('applied_at'),
                'score' => (string) $request->input('score'),
                'interpretation' => trim((string) $request->input('interpretation')),
                'professional_notes' => trim((string) $request->input('professional_notes')),
            ], $this->currentUser());
            Session::flash('success', 'Resultado psicometrico registrado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/patients/{$patientId}/instrumentos/create");
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/patients');
        }

        return Response::redirect("/patients/{$patientId}#instrumentos");
    }

    public function apply(Request $request): Response
    {
        $patientId = (int) $request->param('id');
        $instrumentId = (int) $request->param('instrumentId');

        try {
            return Response::view('psychometrics.apply', [
                'patient' => $this->patients->findForUser($patientId, $this->currentUser()),
                'detail' => $this->psychometrics->instrumentDetail($instrumentId),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/patients/{$patientId}/instrumentos/create");
        }
    }

    public function storeApplication(Request $request): Response
    {
        $patientId = (int) $request->param('id');
        $instrumentId = (int) $request->param('instrumentId');

        try {
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->psychometrics->createApplication($patientId, $instrumentId, [
                'applied_at' => (string) $request->input('applied_at'),
                'answers' => (array) $request->input('answers', []),
                'professional_notes' => trim((string) $request->input('professional_notes')),
            ], $this->currentUser());
            Session::flash('success', 'Instrumento aplicado y resultado registrado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/patients/{$patientId}/instrumentos/{$instrumentId}/aplicar");
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}#instrumentos");
    }

    public function edit(Request $request): Response
    {
        $patientId = (int) $request->param('id');
        $resultId = (int) $request->param('resultId');

        try {
            $result = $this->psychometrics->findResultForPatient(
                $patientId,
                $resultId,
                $this->currentUser()
            );

            $applicationDetails = $this->psychometrics->applicationDetailsForResults([$result], $this->currentUser());

            return Response::view('psychometrics.edit', [
                'patient' => $this->patients->findForUser($patientId, $this->currentUser()),
                'result' => $result,
                'applicationDetail' => $applicationDetails[$result->id] ?? null,
                'instruments' => $this->psychometrics->activeInstruments(),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/patients/{$patientId}#instrumentos");
        }
    }

    public function update(Request $request): Response
    {
        $patientId = (int) $request->param('id');
        $resultId = (int) $request->param('resultId');

        try {
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->psychometrics->updateResult($patientId, $resultId, [
                'instrument_id' => (string) $request->input('instrument_id'),
                'applied_at' => (string) $request->input('applied_at'),
                'score' => (string) $request->input('score'),
                'interpretation' => trim((string) $request->input('interpretation')),
                'professional_notes' => trim((string) $request->input('professional_notes')),
            ], $this->currentUser());
            Session::flash('success', 'Resultado psicometrico actualizado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/patients/{$patientId}/instrumentos/{$resultId}/edit");
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}#instrumentos");
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

        try {
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->psychometrics->setActive(
                $patientId,
                (int) $request->param('resultId'),
                $active,
                $this->currentUser()
            );
            Session::flash('success', $active ? 'Resultado psicometrico activado correctamente.' : 'Resultado psicometrico desactivado correctamente.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}#instrumentos");
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
