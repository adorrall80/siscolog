<?php

use Core\Session;

$currentUser = Session::get('user', []);
$currentRole = is_array($currentUser) ? (string) ($currentUser['role'] ?? '') : '';
$isAdmin = $currentRole === 'administrador';
?>

<nav class="module module-nav" aria-label="Menu principal">
    <a href="/">Menu</a>
    <a href="/panel">Panel</a>
    <a href="/patients">Pacientes</a>
    <a href="/citas">Sesiones</a>
    <a href="/informes">Informes</a>
    <a href="/informes?tipo=con_ia">Analisis IA</a>
    <a href="/informes">Listados</a>
    <?php if ($isAdmin): ?>
        <a href="/usuarios">Usuarios</a>
        <a href="/maintainers">Mantenedores</a>
        <a href="/instrumentos">Instrumentos</a>
        <a href="/auditoria">Auditoria</a>
    <?php endif; ?>
    <a href="/logout">Salir</a>
</nav>
