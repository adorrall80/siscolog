<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Core\Env;
use RuntimeException;

final class AuthService
{
    private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GOOGLE_USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function attempt(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail(strtolower(trim($email)));

        if ($user === null || !$user->isActive || $user->status !== 'activo') {
            return null;
        }

        if (!password_verify($password, $user->passwordHash)) {
            return null;
        }

        return $user;
    }

    public function attemptGoogle(string $email, bool $emailVerified): ?User
    {
        if (!$emailVerified) {
            return null;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || !$user->isActive || $user->status !== 'activo') {
            return null;
        }

        return $user;
    }

    public function googleEnabled(): bool
    {
        return $this->googleClientId() !== '' && $this->googleClientSecret() !== '';
    }

    public function googleAuthorizationUrl(string $state, ?string $redirectUri = null): string
    {
        $this->assertGoogleConfigured();

        return self::GOOGLE_AUTH_URL . '?' . http_build_query([
            'client_id' => $this->googleClientId(),
            'redirect_uri' => $redirectUri ?? $this->googleRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    /**
     * @return array{email: string, email_verified: bool, name: string}
     */
    public function googleUserFromCode(string $code, ?string $redirectUri = null): array
    {
        $this->assertGoogleConfigured();

        if (trim($code) === '') {
            throw new RuntimeException('Google no retorno codigo de autorizacion.');
        }

        $token = $this->postForm(self::GOOGLE_TOKEN_URL, [
            'code' => $code,
            'client_id' => $this->googleClientId(),
            'client_secret' => $this->googleClientSecret(),
            'redirect_uri' => $redirectUri ?? $this->googleRedirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        $accessToken = (string) ($token['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Google no retorno token de acceso.');
        }

        $profile = $this->getJson(self::GOOGLE_USERINFO_URL, [
            'Authorization: Bearer ' . $accessToken,
        ]);

        return [
            'email' => strtolower(trim((string) ($profile['email'] ?? ''))),
            'email_verified' => (bool) ($profile['email_verified'] ?? false),
            'name' => (string) ($profile['name'] ?? ''),
        ];
    }

    private function assertGoogleConfigured(): void
    {
        if (!$this->googleEnabled()) {
            throw new RuntimeException('Login con Google aun no esta configurado. Falta GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en .env.');
        }
    }

    private function googleClientId(): string
    {
        return trim((string) Env::get('GOOGLE_CLIENT_ID', ''));
    }

    private function googleClientSecret(): string
    {
        return trim((string) Env::get('GOOGLE_CLIENT_SECRET', ''));
    }

    private function googleRedirectUri(): string
    {
        return $this->redirectUriFromEnv('GOOGLE_REDIRECT_URI', '/auth/google/callback');
    }

    public function googleRegisterRedirectUri(): string
    {
        return $this->redirectUriFromEnv('GOOGLE_REGISTER_REDIRECT_URI', '/registro/google/callback');
    }

    private function redirectUriFromEnv(string $key, string $path): string
    {
        $configured = trim((string) Env::get($key, ''));

        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) Env::get('APP_URL', 'http://127.0.0.1:8080'), '/') . $path;
    }

    private function postForm(string $url, array $data): array
    {
        return $this->requestJson($url, [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
            'ignore_errors' => true,
        ]);
    }

    private function getJson(string $url, array $headers = []): array
    {
        return $this->requestJson($url, [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
        ]);
    }

    private function requestJson(string $url, array $httpOptions): array
    {
        $response = file_get_contents($url, false, stream_context_create(['http' => $httpOptions]));

        if ($response === false) {
            throw new RuntimeException('No se pudo conectar con Google.');
        }

        $payload = json_decode($response, true);

        if (!is_array($payload)) {
            throw new RuntimeException('Google retorno una respuesta no valida.');
        }

        if (isset($payload['error'])) {
            $message = is_array($payload['error'])
                ? (string) ($payload['error']['message'] ?? 'Error de Google.')
                : (string) $payload['error'];
            throw new RuntimeException('Google rechazo la autenticacion: ' . $message);
        }

        return $payload;
    }
}
