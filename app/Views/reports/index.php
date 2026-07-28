<?php

use Core\View;

$items ??= [];
$summary ??= ['total' => 0, 'with_sessions' => 0, 'with_ai' => 0, 'pending_consent' => 0, 'with_psychometric_alerts' => 0];
$filters ??= ['q' => '', 'estado' => 'todos', 'tipo' => 'todos'];
$patientStatuses ??= [];
$statusLabels = [];
$statusColors = [];

foreach ($patientStatuses as $status) {
    $statusLabels[$status->code] = $status->name;
    $statusColors[$status->code] = $status->color ?: 'green';
}

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Informes clinicos</p>
            <h1>Centro de informes</h1>
            <p>Listado de pacientes disponibles para revisar reporte clinico, exportar o entrar al historial IA.</p>
        </div>
        <a class="button" href="/patients">Pacientes</a>
    </div>

    <form class="inline-search" method="get" action="/informes">
        <label>
            Buscar
            <input name="q" value="<?= View::escape((string) ($filters['q'] ?? '')) ?>" placeholder="Paciente, codigo, email o telefono">
        </label>

        <label>
            Registro
            <select name="estado">
                <option value="todos" <?= ($filters['estado'] ?? 'todos') === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="activos" <?= ($filters['estado'] ?? '') === 'activos' ? 'selected' : '' ?>>Vigentes</option>
                <option value="inactivos" <?= ($filters['estado'] ?? '') === 'inactivos' ? 'selected' : '' ?>>No vigentes</option>
            </select>
        </label>

        <label>
            Tipo de informe
            <select name="tipo">
                <option value="todos" <?= ($filters['tipo'] ?? 'todos') === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="con_sesiones" <?= ($filters['tipo'] ?? '') === 'con_sesiones' ? 'selected' : '' ?>>Con sesiones</option>
                <option value="pendiente_consentimiento_ia" <?= ($filters['tipo'] ?? '') === 'pendiente_consentimiento_ia' ? 'selected' : '' ?>>Pendiente consentimiento IA</option>
                <option value="con_ia" <?= ($filters['tipo'] ?? '') === 'con_ia' ? 'selected' : '' ?>>Con historial IA</option>
                <option value="alertas_instrumentos" <?= ($filters['tipo'] ?? '') === 'alertas_instrumentos' ? 'selected' : '' ?>>Alertas instrumentos</option>
            </select>
        </label>

        <button class="button small" type="submit">Filtrar</button>
        <a class="button small secondary" href="/informes">Limpiar</a>
    </form>

    <div class="summary-grid">
        <article class="summary-card">
            <span>Total pacientes</span>
            <strong><?= View::escape((string) $summary['total']) ?></strong>
        </article>
        <article class="summary-card">
            <span>Con sesiones</span>
            <strong><?= View::escape((string) $summary['with_sessions']) ?></strong>
        </article>
        <article class="summary-card">
            <span>Con IA</span>
            <strong><?= View::escape((string) $summary['with_ai']) ?></strong>
        </article>
        <article class="summary-card">
            <span>Consentimiento IA pendiente</span>
            <strong><?= View::escape((string) $summary['pending_consent']) ?></strong>
        </article>
        <article class="summary-card">
            <span>Alertas instrumentos</span>
            <strong><?= View::escape((string) $summary['with_psychometric_alerts']) ?></strong>
        </article>
    </div>

    <?php if ($items === []): ?>
        <div class="clinical-alert" role="status">
            <span class="clinical-alert-icon" aria-hidden="true">i</span>
            <div>
                <strong>Sin informes para mostrar</strong>
                <p>No hay pacientes que calcen con los filtros seleccionados.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="report-list">
            <?php foreach ($items as $item): ?>
                <?php $patient = $item['patient']; ?>
                <article class="report-list-card">
                    <header>
                        <div>
                            <span class="tag tone-<?= View::escape($statusColors[$patient->status] ?? 'green') ?>">
                                <?= View::escape($statusLabels[$patient->status] ?? $patient->status) ?>
                            </span>
                            <h2><?= View::escape($patient->fullName) ?></h2>
                            <p><?= View::escape($patient->code) ?> · Ultima sesion: <?= View::escape($item['last_session_at'] ?: 'Sin sesiones') ?></p>
                        </div>
                        <span class="state tone-<?= $patient->isActive ? 'green' : 'red' ?>">
                            <?= $patient->isActive ? 'Vigente' : 'No vigente' ?>
                        </span>
                    </header>

                    <div class="report-list-metrics">
                        <span><strong><?= View::escape((string) $item['sessions_active']) ?></strong> sesiones vigentes</span>
                        <span><strong><?= View::escape((string) $item['ai_outputs']) ?></strong> salidas IA</span>
                        <span><strong><?= View::escape((string) $item['psychometric_total']) ?></strong> instrumentos</span>
                        <span class="<?= $item['psychometric_alerts'] > 0 ? 'is-alert' : '' ?>">
                            <strong><?= View::escape((string) $item['psychometric_alerts']) ?></strong> alertas
                        </span>
                    </div>

                    <?php if (!$item['has_ai_consent']): ?>
                        <div class="clinical-alert is-danger compact" role="status">
                            <span class="clinical-alert-icon" aria-hidden="true">!</span>
                            <div>
                                <strong>Consentimiento IA pendiente</strong>
                                <p>La IA permanece bloqueada hasta registrar consentimiento activo y aceptado.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="table-actions">
                        <a class="action-link" href="/patients/<?= View::escape((string) $patient->id) ?>">Ficha</a>
                        <a class="action-link ghost" href="/patients/<?= View::escape((string) $patient->id) ?>/reporte">Reporte</a>
                        <a class="action-link ghost" href="/patients/<?= View::escape((string) $patient->id) ?>/reporte/exportar">Exportar</a>
                        <a class="action-link ghost" href="/patients/<?= View::escape((string) $patient->id) ?>/ai-history">Historial IA</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
