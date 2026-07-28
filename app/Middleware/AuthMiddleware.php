<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\UserService;
use App\Services\RbacService;
use Core\Csrf;
use Core\Request;
use Core\Response;
use Core\Session;
use RuntimeException;

final class AuthMiddleware
{
    private const PUBLIC_ROUTES = [
        'GET' => ['/login', '/registro', '/auth/google', '/auth/google/callback', '/registro/google', '/registro/google/callback'],
        'POST' => ['/login', '/registro'],
    ];

    private RbacService $rbac;

    public function __construct()
    {
        $this->rbac = new RbacService();
    }

    public function handle(Request $request): ?Response
    {
        if ($request->method === 'POST' && !$this->validCsrf($request)) {
            if ($this->expectsJson($request)) {
                return Response::json([
                    'ok' => false,
                    'message' => 'Sesion expirada. Recarga la pagina e intenta nuevamente.',
                ], 419);
            }

            Session::flash('error', 'Sesion expirada. Recarga la pagina e intenta nuevamente.');
            return Response::redirect($this->csrfFallback($request));
        }

        $isPublic = in_array($request->uri, self::PUBLIC_ROUTES[$request->method] ?? [], true);
        $user = Session::get('user');
        $isAuthenticated = is_array($user);

        if ($isAuthenticated && !$this->validSessionRole($user)) {
            return $this->clearInvalidSession('Sesion antigua o invalida. Inicia sesion nuevamente.');
        }

        if ($isAuthenticated && in_array($request->uri, ['/login', '/registro'], true)) {
            return Response::redirect('/');
        }

        if (!$isAuthenticated && !$isPublic) {
            Session::flash('error', 'Debes iniciar sesion para acceder al sistema.');
            return Response::redirect('/login');
        }

        if ($isAuthenticated && $this->sessionExpired()) {
            Session::forget('user');
            Session::forget('last_activity_at');
            session_regenerate_id(true);
            Session::flash('error', 'Tu sesion expiro por inactividad. Inicia sesion nuevamente.');
            return Response::redirect('/login');
        }

        if ($isAuthenticated) {
            Session::put('last_activity_at', time());
        }

        if ($isAuthenticated && !$this->canAccess($request, (string) ($user['role'] ?? ''))) {
            Session::flash('error', 'Tu rol no tiene permiso para acceder a ese modulo.');

            if ($request->uri === '/') {
                return $this->clearInvalidSession('Sesion sin permisos validos. Inicia sesion nuevamente.');
            }

            return Response::redirect('/');
        }

        if ($isAuthenticated && $this->mustCompleteProfessionalProfile($request, $user)) {
            Session::flash('error', 'Completa tus datos profesionales de atencion para continuar.');
            return Response::redirect('/perfil/profesional');
        }

        return null;
    }

    private function clearInvalidSession(string $message): Response
    {
        Session::forget('user');
        Session::forget('last_activity_at');
        session_regenerate_id(true);
        Session::flash('error', $message);

        return Response::redirect('/login');
    }

    private function validCsrf(Request $request): bool
    {
        return Csrf::validate($request->input(Csrf::FIELD, $request->header('X-CSRF-Token')));
    }

    private function expectsJson(Request $request): bool
    {
        $accept = (string) $request->header('Accept', '');

        return str_contains($accept, 'application/json')
            || str_contains($request->uri, '/family-node-position')
            || $request->input('async') === '1';
    }

    private function csrfFallback(Request $request): string
    {
        if ($request->uri === '/registro') {
            return '/registro';
        }

        if ($request->uri === '/login') {
            return '/login';
        }

        if ($request->uri === '/perfil/profesional/actualizar') {
            return '/perfil/profesional';
        }

        return '/';
    }

    private function canAccess(Request $request, string $role): bool
    {
        return $this->rbac->canAccess($request, $role);
    }

    private function validSessionRole(array $sessionUser): bool
    {
        return in_array((string) ($sessionUser['role'] ?? ''), ['administrador', 'profesional', 'supervisor'], true);
    }

    private function sessionExpired(): bool
    {
        $lastActivity = (int) Session::get('last_activity_at', time());

        return time() - $lastActivity > 28800;
    }

    /**
     * @param string[] $prefixes
     */
    private function matchesPrefix(string $uri, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function mustCompleteProfessionalProfile(Request $request, array $sessionUser): bool
    {
        if ((string) ($sessionUser['role'] ?? '') !== 'profesional') {
            return false;
        }

        if ($this->matchesPrefix($request->uri, ['/perfil/profesional']) || $request->uri === '/logout') {
            return false;
        }

        $users = new UserService();
        try {
            $user = $users->find((int) ($sessionUser['id'] ?? 0));
        } catch (RuntimeException) {
            return true;
        }

        return !$users->professionalProfileComplete($user);
    }
}
