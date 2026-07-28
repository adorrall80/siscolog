<?php

use Core\View;

$old = is_array($old ?? null) ? $old : [];

ob_start();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro | SisColog</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card auth-card-wide">
            <p class="eyebrow">Crear acceso</p>
            <h1>Registro</h1>
            <p>Primero crea tu acceso con datos basicos. Despues de ingresar, SisColog te pedira completar tus datos profesionales de atencion.</p>

            <div class="auth-tabs">
                <a href="/login">Ingreso</a>
                <a class="active" href="/registro">Registro</a>
            </div>

            <?php require dirname(__DIR__) . '/partials/flash.php'; ?>

            <section class="auth-method-card">
                <div>
                    <strong>Registrarme con Gmail</strong>
                    <p>Valida tu correo con Gmail. Si es correcto, entraras y luego completarás tu perfil profesional.</p>
                </div>
                <a class="google-login-button" href="/registro/google">
                    <span class="google-login-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.3-.2-1.9H12v3.6h5.4c-.2 1.2-.9 2.3-2 3v2.5h3.2c1.9-1.7 3-4.2 3-7.2z"/>
                            <path fill="#34A853" d="M12 22c2.7 0 5-0.9 6.6-2.5l-3.2-2.5c-.9.6-2 1-3.4 1-2.6 0-4.8-1.8-5.6-4.1H3.1v2.6C4.8 19.7 8.2 22 12 22z"/>
                            <path fill="#FBBC05" d="M6.4 13.9c-.2-.6-.3-1.2-.3-1.9s.1-1.3.3-1.9V7.5H3.1C2.4 8.8 2 10.4 2 12s.4 3.2 1.1 4.5l3.3-2.6z"/>
                            <path fill="#EA4335" d="M12 6c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.9 3 14.7 2 12 2 8.2 2 4.8 4.3 3.1 7.5l3.3 2.6C7.2 7.8 9.4 6 12 6z"/>
                        </svg>
                    </span>
                    Registrarme con Gmail
                </a>
            </section>

            <div class="auth-divider"><span>o</span></div>

            <form class="form-grid" method="post" action="/registro">
                <fieldset>
                    <legend>Registro basico</legend>
                    <label>
                        Nombre
                        <input name="name" required value="<?= View::escape((string) ($old['name'] ?? '')) ?>" placeholder="Ej: Sebastian Jara">
                    </label>
                    <label>
                        Email
                        <input type="email" name="email" required value="<?= View::escape((string) ($old['email'] ?? '')) ?>" placeholder="correo@dominio.cl">
                    </label>
                    <label>
                        Contrasena
                        <input type="password" name="password" required minlength="6" placeholder="Minimo 6 caracteres">
                    </label>
                </fieldset>

                <button class="button" type="submit">Crear acceso basico</button>
            </form>
        </section>
    </main>
</body>
</html>
<?php
echo ob_get_clean();
