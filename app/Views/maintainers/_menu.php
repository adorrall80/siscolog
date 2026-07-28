<?php

use Core\View;

$activeTable ??= null;
?>

<nav class="record-tabs" aria-label="Menu de mantenedores">
    <a class="<?= $activeTable === null ? 'active' : '' ?>" href="/maintainers">Mantenedores</a>
    <?php foreach ($tables as $menuTable): ?>
        <a class="<?= $activeTable === $menuTable ? 'active' : '' ?>" href="/maintainers/<?= View::escape($tableSlugs[$menuTable] ?? $menuTable) ?>">
            <?= View::escape($tableLabels[$menuTable] ?? $menuTable) ?>
        </a>
    <?php endforeach; ?>
</nav>
