<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MaintainerService;
use App\Services\PatientConsentService;
use App\Services\PatientService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class PatientConsentController
{
    private PatientService $patients;
    private PatientConsentService $consents;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->patients = new PatientService();
        $this->consents = new PatientConsentService();
        $this->maintainers = new MaintainerService();
    }

    public function create(Request $request): Response
    {
        $patientId = (int) $request->param('id');

        try {
            return Response::view('consents.create', [
                'patient' => $this->patients->findForUser($patientId, $this->currentUser()),
                'consentTypes' => $this->maintainers->active(MaintainerService::CONSENT_TYPES),
                'consents' => $this->consents->byPatient($patientId, $this->currentUser()),
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
            $this->consents->create($patientId, $this->consentData($request), $this->currentUser());
            Session::flash('success', 'Consentimiento registrado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/patients/{$patientId}/consents/create");
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/patients');
        }

        return Response::redirect("/patients/{$patientId}/consents/create");
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
        $consentId = (int) $request->param('consentId');

        try {
            $this->patients->findForUser($patientId, $this->currentUser());
            $this->consents->setActive($patientId, $consentId, $active, $this->currentUser());
            Session::flash('success', $active ? 'Consentimiento activado correctamente.' : 'Consentimiento desactivado correctamente.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/patients/{$patientId}");
    }

    private function consentData(Request $request): array
    {
        $accepted = (string) $request->input('accepted', '1') === '1';

        return [
            'consent_type' => (string) $request->input(
                'consent_type',
                $this->maintainers->defaultCode(MaintainerService::CONSENT_TYPES)
            ),
            'accepted' => $accepted,
            'accepted_at' => trim((string) $request->input('accepted_at')),
            'revoked_at' => trim((string) $request->input('revoked_at')),
            'document_version' => trim((string) $request->input('document_version', 'v1.0')),
        ];
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
