<?php

use Core\Session;
use Core\View;

$currentUser = Session::get('user', []);
$currentRole = is_array($currentUser) ? (string) ($currentUser['role'] ?? '') : '';
$isAdmin = $currentRole === 'administrador';
?>

<aside class="module sidebar sidebar-left" aria-label="Columna izquierda">
    <p class="module-kicker">Navegacion clinica</p>
    <h2>Modulos del caso</h2>

    <a class="module-link" href="/patients">Pacientes</a>
    <a class="module-link" href="#">Sesiones clinicas</a>
    <a class="module-link" href="<?= isset($patient) ? '/patients/' . View::escape((string) $patient->id) . '/consents/create' : '#' ?>">Consentimientos</a>
    <a class="module-link" href="#">Instrumentos</a>
    <a class="module-link" href="#">Plan terapeutico</a>
    <?php if ($isAdmin): ?>
        <a class="module-link" href="/maintainers">Mantenedores</a>
    <?php endif; ?>

    <div class="mini-card">
        <strong>Acceso rapido</strong>
        <span>Este panel puede cambiar segun la pantalla: filtros en listados, secciones en ficha y tareas en sesiones.</span>
    </div>
</aside>
