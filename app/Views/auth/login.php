<?php

use Core\View;

$defaultEmail = 'admin@siscolog.local';
$defaultPassword = 'admin123';
$emailValue = (string) ($_POST['email'] ?? $defaultEmail);
$passwordValue = (string) ($_POST['password'] ?? $defaultPassword);

ob_start();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar | SisColog</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card">
            <p class="eyebrow">Acceso seguro</p>
            <h1>Ingresar a SisColog</h1>
            <p>Identificate para acceder a fichas, sesiones, consentimientos y analisis supervisado.</p>

            <div class="auth-tabs">
                <a class="active" href="/login">Ingreso</a>
                <a href="/registro">Registro</a>
            </div>

            <?php require dirname(__DIR__) . '/partials/flash.php'; ?>

            <form class="form-grid" method="post" action="/login">
                <label>
                    Email
                    <input type="email" name="email" value="<?= View::escape($emailValue) ?>" placeholder="admin@siscolog.local" required autofocus>
                </label>
                <label>
                    Contrasena
                    <input type="password" name="password" value="<?= View::escape($passwordValue) ?>" placeholder="admin123" required>
                </label>
                <button class="button" type="submit">Iniciar sesion</button>
            </form>

            <div class="auth-divider"><span>o</span></div>

            <?php if (($googleEnabled ?? false) === true): ?>
                <a class="google-login-button" href="/auth/google">
                    <span class="google-login-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.3-.2-1.9H12v3.6h5.4c-.2 1.2-.9 2.3-2 3v2.5h3.2c1.9-1.7 3-4.2 3-7.2z"/>
                            <path fill="#34A853" d="M12 22c2.7 0 5-0.9 6.6-2.5l-3.2-2.5c-.9.6-2 1-3.4 1-2.6 0-4.8-1.8-5.6-4.1H3.1v2.6C4.8 19.7 8.2 22 12 22z"/>
                            <path fill="#FBBC05" d="M6.4 13.9c-.2-.6-.3-1.2-.3-1.9s.1-1.3.3-1.9V7.5H3.1C2.4 8.8 2 10.4 2 12s.4 3.2 1.1 4.5l3.3-2.6z"/>
                            <path fill="#EA4335" d="M12 6c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.9 3 14.7 2 12 2 8.2 2 4.8 4.3 3.1 7.5l3.3 2.6C7.2 7.8 9.4 6 12 6z"/>
                        </svg>
                    </span>
                    Ingresar con Gmail
                </a>
            <?php else: ?>
                <a class="google-login-button is-disabled" href="/auth/google">
                    <span class="google-login-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.3-.2-1.9H12v3.6h5.4c-.2 1.2-.9 2.3-2 3v2.5h3.2c1.9-1.7 3-4.2 3-7.2z"/>
                            <path fill="#34A853" d="M12 22c2.7 0 5-0.9 6.6-2.5l-3.2-2.5c-.9.6-2 1-3.4 1-2.6 0-4.8-1.8-5.6-4.1H3.1v2.6C4.8 19.7 8.2 22 12 22z"/>
                            <path fill="#FBBC05" d="M6.4 13.9c-.2-.6-.3-1.2-.3-1.9s.1-1.3.3-1.9V7.5H3.1C2.4 8.8 2 10.4 2 12s.4 3.2 1.1 4.5l3.3-2.6z"/>
                            <path fill="#EA4335" d="M12 6c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.9 3 14.7 2 12 2 8.2 2 4.8 4.3 3.1 7.5l3.3 2.6C7.2 7.8 9.4 6 12 6z"/>
                        </svg>
                    </span>
                    Ingresar con Gmail
                </a>
            <?php endif; ?>

            <small class="auth-help">Usuario demo inicial: admin@siscolog.local / admin123</small>
            <small class="auth-help">Profesional prueba: profesional@siscolog.local / demo123</small>
            <small class="auth-help">Supervisor prueba: supervisor@siscolog.local / demo123</small>
            <small class="auth-help">Google/Gmail solo permite correos registrados como usuarios activos.</small>
        </section>
    </main>
</body>
</html>
<?php
echo ob_get_clean();
