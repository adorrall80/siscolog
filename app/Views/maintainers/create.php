<?php

use Core\View;

ob_start();
?>
<?php
$tables = $tables ?? [$table];
$activeTable = $table;
$tableSlug ??= $table;
require __DIR__ . '/_menu.php';
?>

<section class="action-toolbar">
    <strong>Nuevo mantenedor</strong>
    <span class="toolbar-code"><?= View::escape($tableLabel) ?></span>
    <a class="button small secondary" href="/maintainers/<?= View::escape($tableSlug) ?>">Volver</a>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow"><?= View::escape($tableLabel) ?></p>
            <h1>Agregar valor</h1>
            <p>Este valor quedara disponible para selects, estados y validaciones del sistema.</p>
        </div>
    </div>

    <form method="post" action="/maintainers/<?= View::escape($tableSlug) ?>" class="form-grid">
        <?php require __DIR__ . '/form.php'; ?>

        <button class="button" type="submit">Guardar valor</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
