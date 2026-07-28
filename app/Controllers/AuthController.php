<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\AuditService;
use App\Services\UserService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class AuthController
{
    private AuthService $auth;
    private AuditService $audit;
    private UserService $users;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->audit = new AuditService();
        $this->users = new UserService();
    }

    public function login(Request $request): Response
    {
        return Response::view('auth.login', [
            'googleEnabled' => $this->auth->googleEnabled(),
        ]);
    }

    public function authenticate(Request $request): Response
    {
        $user = $this->auth->attempt(
            (string) $request->input('email'),
            (string) $request->input('password')
        );

        if ($user === null) {
            $this->audit->record('login_fallido', 'auth', null, $request, null, [
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
            Session::flash('error', 'Credenciales invalidas o usuario inactivo.');
            return Response::redirect('/login');
        }

        $this->startUserSession($user);
        $this->audit->record('login_exitoso', 'auth', (int) $user->id, $request, [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
        Session::flash('success', 'Sesion iniciada correctamente.');

        return Response::redirect('/');
    }

    public function googleRedirect(Request $request): Response
    {
        try {
            $state = bin2hex(random_bytes(24));
            Session::put('google_oauth_state', $state);

            return Response::redirect($this->auth->googleAuthorizationUrl($state));
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/login');
        }
    }

    public function googleCallback(Request $request): Response
    {
        $expectedState = (string) Session::get('google_oauth_state', '');
        Session::forget('google_oauth_state');

        if ((string) $request->input('error', '') !== '') {
            Session::flash('error', 'Google cancelo o rechazo el acceso.');
            return Response::redirect('/login');
        }

        if ($expectedState === '' || !hash_equals($expectedState, (string) $request->input('state', ''))) {
            Session::flash('error', 'La respuesta de Google no pudo validarse. Intenta nuevamente.');
            return Response::redirect('/login');
        }

        try {
            $profile = $this->auth->googleUserFromCode((string) $request->input('code', ''));
            $user = $this->auth->attemptGoogle($profile['email'], $profile['email_verified']);

            if ($user === null) {
                Session::flash('error', 'El correo Google no esta autorizado como usuario activo en SisColog.');
                return Response::redirect('/login');
            }

            $this->startUserSession($user);
            Session::flash('success', 'Sesion iniciada con Google.');

            return Response::redirect('/');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/login');
        }
    }

    public function register(Request $request): Response
    {
        return Response::view('auth.register', [
            'googleEnabled' => $this->auth->googleEnabled(),
            'old' => Session::get('registration_old', []),
        ]);
    }

    public function storeRegistration(Request $request): Response
    {
        $data = $this->basicRegistrationData($request);

        try {
            $user = $this->users->registerBasicProfessional($data);
            Session::forget('registration_old');
            $this->startUserSession($user);
            Session::flash('success', 'Cuenta creada. Completa tus datos profesionales para continuar.');

            return Response::redirect('/perfil/profesional');
        } catch (InvalidArgumentException $exception) {
            Session::put('registration_old', $data);
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/registro');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/registro');
        }
    }

    public function googleRegisterRedirect(Request $request): Response
    {
        try {
            $state = bin2hex(random_bytes(24));
            Session::put('google_register_oauth_state', $state);

            return Response::redirect($this->auth->googleAuthorizationUrl(
                $state,
                $this->auth->googleRegisterRedirectUri()
            ));
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/registro');
        }
    }

    public function googleRegisterCallback(Request $request): Response
    {
        $expectedState = (string) Session::get('google_register_oauth_state', '');
        Session::forget('google_register_oauth_state');

        if ((string) $request->input('error', '') !== '') {
            Session::flash('error', 'Google cancelo o rechazo el registro.');
            return Response::redirect('/registro');
        }

        if ($expectedState === '' || !hash_equals($expectedState, (string) $request->input('state', ''))) {
            Session::flash('error', 'La respuesta de Google no pudo validarse. Intenta nuevamente.');
            return Response::redirect('/registro');
        }

        try {
            $profile = $this->auth->googleUserFromCode(
                (string) $request->input('code', ''),
                $this->auth->googleRegisterRedirectUri()
            );

            if (!$profile['email_verified']) {
                Session::flash('error', 'Gmail no confirmo el correo como verificado.');
                return Response::redirect('/registro');
            }

            $existing = $this->users->findByEmail($profile['email']);

            if ($existing !== null) {
                if (!$existing->isActive || $existing->status !== 'activo') {
                    Session::flash('error', 'Ese correo ya existe, pero el usuario esta inactivo.');
                    return Response::redirect('/login');
                }

                $this->startUserSession($existing);
                Session::flash('success', 'Ingreso con Gmail correcto. Completa tu perfil si falta informacion.');
                return Response::redirect('/perfil/profesional');
            }

            $user = $this->users->registerBasicProfessional([
                'email' => $profile['email'],
                'name' => $profile['name'],
                'password' => '',
            ], true);
            $this->startUserSession($user);
            Session::flash('success', 'Gmail validado. Completa tus datos profesionales para continuar.');

            return Response::redirect('/perfil/profesional');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/registro');
        }
    }

    public function logout(Request $request): Response
    {
        $this->audit->record('logout', 'auth', null, $request);
        Session::forget('user');
        Session::forget('last_activity_at');
        session_regenerate_id(true);
        Session::flash('success', 'Sesion cerrada correctamente.');

        return Response::redirect('/login');
    }

    private function startUserSession(object $user): void
    {
        session_regenerate_id(true);
        Session::put('user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
        Session::put('last_activity_at', time());
    }

    private function basicRegistrationData(Request $request): array
    {
        return [
            'name' => trim((string) $request->input('name')),
            'email' => strtolower(trim((string) $request->input('email'))),
            'password' => (string) $request->input('password'),
        ];
    }

    public function profileData(Request $request): array
    {
        return [
            'name' => trim((string) $request->input('name')),
            'profession' => trim((string) $request->input('profession')),
            'specialty' => trim((string) $request->input('specialty')),
            'professional_license' => trim((string) $request->input('professional_license')),
            'phone' => trim((string) $request->input('phone')),
            'clinic_name' => trim((string) $request->input('clinic_name')),
            'clinic_address' => trim((string) $request->input('clinic_address')),
            'professional_bio' => trim((string) $request->input('professional_bio')),
        ];
    }
}
