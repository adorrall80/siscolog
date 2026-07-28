<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MaintainerService;
use App\Services\UserService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class UserController
{
    private UserService $users;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->users = new UserService();
        $this->maintainers = new MaintainerService();
    }

    public function index(Request $request): Response
    {
        $filter = $this->users->normalizeFilter((string) $request->input('estado', 'todos'));
        $search = $this->users->normalizeSearch((string) $request->input('q', ''));
        $users = $this->users->filtered($filter, $search);
        $allUsers = $this->users->all();

        return Response::view('users.index', [
            'users' => $users,
            'summary' => $this->users->summary($allUsers),
            'activeFilter' => $filter,
            'activeSearch' => $search,
            'roles' => $this->maintainers->active(MaintainerService::USER_ROLES),
            'statuses' => $this->maintainers->active(MaintainerService::USER_STATUSES),
            'attentionModalities' => $this->maintainers->active(MaintainerService::SESSION_MODALITIES),
            'selectedAttentionModalities' => [],
        ]);
    }

    public function create(Request $request): Response
    {
        return Response::view('users.create', [
            'roles' => $this->maintainers->active(MaintainerService::USER_ROLES),
            'statuses' => $this->maintainers->active(MaintainerService::USER_STATUSES),
            'attentionModalities' => $this->maintainers->active(MaintainerService::SESSION_MODALITIES),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->data($request);

        try {
            $this->assertMaintainers($data);
            $this->users->create($data);
            Session::flash('success', 'Usuario creado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/usuarios/crear');
        }

        return Response::redirect('/usuarios');
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');

        try {
            return Response::view('users.edit', [
                'user' => $this->users->find($id),
                'roles' => $this->maintainers->active(MaintainerService::USER_ROLES),
                'statuses' => $this->maintainers->active(MaintainerService::USER_STATUSES),
                'attentionModalities' => $this->maintainers->active(MaintainerService::SESSION_MODALITIES),
                'selectedAttentionModalities' => $this->users->attentionModalitiesForUser($id),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/usuarios');
        }
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $data = $this->data($request);

        try {
            $this->assertMaintainers($data);
            $this->users->update($id, $data);
            Session::flash('success', 'Usuario actualizado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/usuarios/{$id}/editar");
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/usuarios');
        }

        return Response::redirect('/usuarios');
    }

    public function activate(Request $request): Response
    {
        return $this->setActive($request, true);
    }

    public function deactivate(Request $request): Response
    {
        return $this->setActive($request, false);
    }

    private function setActive(Request $request, bool $active): Response
    {
        $id = (int) $request->param('id');

        try {
            $this->users->setActive($id, $active);
            Session::flash('success', $active ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect('/usuarios');
    }

    private function data(Request $request): array
    {
        return [
            'name' => trim((string) $request->input('name')),
            'email' => trim((string) $request->input('email')),
            'password' => (string) $request->input('password'),
            'role' => (string) $request->input('role'),
            'status' => (string) $request->input('status'),
            'profession' => trim((string) $request->input('profession')),
            'specialty' => trim((string) $request->input('specialty')),
            'professional_license' => trim((string) $request->input('professional_license')),
            'phone' => trim((string) $request->input('phone')),
            'clinic_name' => trim((string) $request->input('clinic_name')),
            'clinic_address' => trim((string) $request->input('clinic_address')),
            'attention_modalities' => $request->input('attention_modalities', []),
            'professional_bio' => trim((string) $request->input('professional_bio')),
            'is_active' => (int) $request->input('is_active', 1),
        ];
    }

    private function assertMaintainers(array $data): void
    {
        if (!$this->maintainers->isValid(MaintainerService::USER_ROLES, $data['role'])) {
            throw new InvalidArgumentException('El rol seleccionado no es valido.');
        }

        if (!$this->maintainers->isValid(MaintainerService::USER_STATUSES, $data['status'])) {
            throw new InvalidArgumentException('El estado seleccionado no es valido.');
        }

        $this->users->assertModalities($this->users->normalizeModalities($data['attention_modalities'] ?? []));
    }
}
