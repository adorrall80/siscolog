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

final class ProfessionalProfileController
{
    private UserService $users;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->users = new UserService();
        $this->maintainers = new MaintainerService();
    }

    public function edit(Request $request): Response
    {
        $sessionUser = Session::get('user', []);
        $userId = is_array($sessionUser) ? (int) ($sessionUser['id'] ?? 0) : 0;

        try {
            return Response::view('profile.professional', [
                'user' => $this->users->find($userId),
                'attentionModalities' => $this->maintainers->active(MaintainerService::SESSION_MODALITIES),
                'selectedAttentionModalities' => $this->users->attentionModalitiesForUser($userId),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/logout');
        }
    }

    public function update(Request $request): Response
    {
        $sessionUser = Session::get('user', []);
        $userId = is_array($sessionUser) ? (int) ($sessionUser['id'] ?? 0) : 0;

        try {
            $user = $this->users->updateProfessionalProfile($userId, $this->data($request));
            Session::put('user', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]);
            Session::flash('success', 'Perfil profesional completado correctamente.');

            return Response::redirect('/');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/perfil/profesional');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/logout');
        }
    }

    private function data(Request $request): array
    {
        return [
            'name' => trim((string) $request->input('name')),
            'profession' => trim((string) $request->input('profession')),
            'specialty' => trim((string) $request->input('specialty')),
            'professional_license' => trim((string) $request->input('professional_license')),
            'phone' => trim((string) $request->input('phone')),
            'clinic_name' => trim((string) $request->input('clinic_name')),
            'clinic_address' => trim((string) $request->input('clinic_address')),
            'attention_modalities' => $request->input('attention_modalities', []),
            'professional_bio' => trim((string) $request->input('professional_bio')),
        ];
    }
}
