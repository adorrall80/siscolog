<?php

use Core\Session;
use Core\View;

$currentUser = Session::get('user', []);
$currentUserEmail = is_array($currentUser) ? (string) ($currentUser['email'] ?? '') : '';
?>

<header class="module module-header">
    <div>
        <p class="module-kicker">Sistema clinico</p>
        <a href="/" class="brand">SisColog</a>
    </div>

    <div class="header-actions">
        <?php if ($currentUserEmail !== ''): ?>
            <span class="status-pill"><?= View::escape($currentUserEmail) ?></span>
        <?php endif; ?>
        <button class="theme-toggle" type="button" data-theme-toggle>Modo oscuro</button>
        <a class="button small" href="/patients/create">Nuevo paciente</a>
        <a class="button small secondary" href="/logout">Salir</a>
    </div>
</header>
