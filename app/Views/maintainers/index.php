<?php

use Core\View;

ob_start();
?>
<?php
$activeTable = null;
require __DIR__ . '/_menu.php';
?>

<section class="action-toolbar">
    <strong>Mantenedores</strong>
    <span class="toolbar-code">Tablas separadas</span>
    <a class="button small" href="/maintainers/estados-paciente">Abrir estados paciente</a>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Configuracion clinica</p>
            <h1>Mantenedores del sistema</h1>
            <p>Estados y tipos viven en tablas separadas, cada una con su propio activo/inactivo.</p>
        </div>
        <span class="state tone-blue"><?= View::escape((string) count($tables)) ?> tablas</span>
    </div>

    <div class="summary-grid">
        <?php foreach ($tables as $table): ?>
            <?php $summary = $tableSummaries[$table] ?? ['active' => 0, 'inactive' => 0]; ?>
            <a class="summary-card summary-link" href="/maintainers/<?= View::escape($tableSlugs[$table] ?? $table) ?>">
                <span><?= View::escape($tableLabels[$table] ?? $table) ?></span>
                <strong>Gestionar</strong>
                <small><?= View::escape((string) $summary['active']) ?> activos / <?= View::escape((string) $summary['inactive']) ?> inactivos</small>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
