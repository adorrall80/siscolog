<?php

use Core\Session;
use Core\View;

$currentUser = Session::get('user', []);
$currentRole = is_array($currentUser) ? (string) ($currentUser['role'] ?? '') : '';
$isAdmin = $currentRole === 'administrador';
$patientId = is_object($patient ?? null) && isset($patient->id) ? (string) $patient->id : null;
?>

<aside class="module sidebar sidebar-left" aria-label="Columna izquierda">
    <p class="module-kicker">Navegacion clinica</p>
    <h2>Modulos del caso</h2>

    <a class="module-link" href="/patients">Pacientes</a>
    <a class="module-link" href="/citas">Sesiones clinicas</a>
    <a class="module-link" href="<?= $patientId !== null ? '/patients/' . View::escape($patientId) . '/consents/create' : '/patients' ?>">Consentimientos</a>
    <a class="module-link" href="/instrumentos">Instrumentos</a>
    <span class="module-link" aria-disabled="true">Plan terapeutico</span>
    <?php if ($isAdmin): ?>
        <a class="module-link" href="/maintainers">Mantenedores</a>
    <?php endif; ?>

    <div class="mini-card">
        <strong>Acceso rapido</strong>
        <span>Este panel puede cambiar segun la pantalla: filtros en listados, secciones en ficha y tareas en sesiones.</span>
    </div>
</aside>
