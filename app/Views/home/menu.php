<?php

use Core\Session;

$currentUser = Session::get('user', []);
$currentRole = is_array($currentUser) ? (string) ($currentUser['role'] ?? '') : '';
$isAdmin = $currentRole === 'administrador';

ob_start();
?>
<section class="panel dashboard-panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Panel principal</p>
            <h1>Centro clinico</h1>
        </div>
        <span class="status-pill">MVP operativo</span>
    </div>

    <div class="app-grid">
        <a class="app-tile" href="/patients">
            <span class="app-icon app-icon-patients" aria-hidden="true"></span>
            <strong>Pacientes</strong>
        </a>

        <a class="app-tile" href="/patients/create">
            <span class="app-icon app-icon-new-patient" aria-hidden="true"></span>
            <strong>Nuevo paciente</strong>
        </a>

        <a class="app-tile" href="/citas?vista=semana">
            <span class="app-icon app-icon-calendar" aria-hidden="true"></span>
            <strong>Agenda sesiones</strong>
        </a>

        <a class="app-tile" href="/citas">
            <span class="app-icon app-icon-session" aria-hidden="true"></span>
            <strong>Sesiones</strong>
        </a>

        <a class="app-tile" href="/informes">
            <span class="app-icon app-icon-report" aria-hidden="true"></span>
            <strong>Informes</strong>
        </a>

        <a class="app-tile" href="/informes?tipo=con_ia">
            <span class="app-icon app-icon-ai" aria-hidden="true"></span>
            <strong>Analisis IA</strong>
        </a>

        <?php if ($isAdmin): ?>
            <a class="app-tile" href="/maintainers">
                <span class="app-icon app-icon-maintainers" aria-hidden="true"></span>
                <strong>Mantenedores</strong>
            </a>

            <a class="app-tile" href="/usuarios">
                <span class="app-icon app-icon-audit" aria-hidden="true"></span>
                <strong>Usuarios</strong>
            </a>

            <a class="app-tile" href="/instrumentos">
                <span class="app-icon app-icon-report" aria-hidden="true"></span>
                <strong>Instrumentos</strong>
            </a>

            <a class="app-tile" href="/auditoria">
                <span class="app-icon app-icon-audit" aria-hidden="true"></span>
                <strong>Auditoria</strong>
            </a>
        <?php endif; ?>

        <a class="app-tile" href="/informes">
            <span class="app-icon app-icon-stats" aria-hidden="true"></span>
            <strong>Reportes</strong>
        </a>
    </div>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
