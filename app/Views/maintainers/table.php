<?php

use Core\View;

ob_start();
?>
<?php
$activeTable = $table;
$contextQuery ??= '';
$tableSlug ??= $table;
$publicFilters = [
    'all' => 'todos',
    'active' => 'activos',
    'inactive' => 'inactivos',
];
require __DIR__ . '/_menu.php';
?>

<section class="action-toolbar">
    <strong><?= View::escape($tableLabel) ?></strong>
    <span class="toolbar-code">Tabla mantenedora</span>
    <a class="button small" href="/maintainers/<?= View::escape($tableSlug) ?>/create">Agregar</a>
    <a class="button small secondary" href="/maintainers">Volver</a>
</section>

<section class="panel">
    <div class="panel-header compact">
        <div>
            <p class="eyebrow">Mantenedor</p>
            <h1><?= View::escape($tableLabel) ?></h1>
            <p>Administra valores activos, inactivos y color de esta tabla.</p>
        </div>
        <div class="summary-meta">
            <span class="state tone-blue"><?= View::escape((string) $summary['total']) ?> registros</span>
            <span class="state tone-green"><?= View::escape((string) $summary['active']) ?> activos</span>
            <span class="state tone-red"><?= View::escape((string) $summary['inactive']) ?> inactivos</span>
        </div>
    </div>

    <nav class="filter-chips" aria-label="Filtros del mantenedor">
        <?php foreach (['all' => 'Todos', 'active' => 'Activos', 'inactive' => 'Inactivos'] as $filterValue => $filterLabel): ?>
            <?php
            $query = [];
            if ($filterValue !== 'all') {
                $query['status'] = $publicFilters[$filterValue];
            }
            if ($activeSearch !== '') {
                $query['q'] = $activeSearch;
            }
            $queryString = $query === [] ? '' : '?' . http_build_query($query);
            ?>
            <a class="<?= $activeFilter === $filterValue ? 'active' : '' ?>" href="/maintainers/<?= View::escape($tableSlug) ?><?= View::escape($queryString) ?>">
                <?= View::escape($filterLabel) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form class="inline-search" method="get" action="/maintainers/<?= View::escape($tableSlug) ?>">
        <?php if ($activeFilter !== 'all'): ?>
            <input type="hidden" name="status" value="<?= View::escape($publicFilters[$activeFilter]) ?>">
        <?php endif; ?>
        <label>
            Buscar
            <input name="q" value="<?= View::escape($activeSearch) ?>" placeholder="Codigo, nombre o descripcion">
        </label>
        <button class="button small" type="submit">Buscar</button>
        <?php if ($activeSearch !== ''): ?>
            <a class="button small secondary" href="/maintainers/<?= View::escape($tableSlug) ?><?= $activeFilter === 'all' ? '' : '?status=' . View::escape($publicFilters[$activeFilter]) ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Color</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr>
                        <td colspan="4">
                            <strong>No hay valores para este filtro.</strong>
                            <small>Prueba con otro filtro o agrega un nuevo valor al mantenedor.</small>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?= View::escape($item->name) ?>
                            <?php if ($item->description): ?>
                                <small><?= View::escape($item->description) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="state tone-<?= View::escape($item->color ?: 'gray') ?>"><?= View::escape($item->color ?: 'sin color') ?></span></td>
                        <td><span class="state tone-<?= $item->isActive ? 'green' : 'red' ?>"><?= $item->isActive ? 'Activo' : 'Inactivo' ?></span></td>
                        <td class="table-actions">
                            <a class="action-link" href="/maintainers/<?= View::escape($tableSlug) ?>/<?= View::escape((string) $item->id) ?>/edit<?= View::escape($contextQuery) ?>">Editar</a>
                            <?php if ($item->isActive): ?>
                                <a class="switch-action is-on" href="/maintainers/<?= View::escape($tableSlug) ?>/<?= View::escape((string) $item->id) ?>/deactivate<?= View::escape($contextQuery) ?>" aria-label="Desactivar <?= View::escape($item->name) ?>">
                                    <span></span>
                                    Vigente
                                </a>
                            <?php else: ?>
                                <a class="switch-action is-off" href="/maintainers/<?= View::escape($tableSlug) ?>/<?= View::escape((string) $item->id) ?>/activate<?= View::escape($contextQuery) ?>" aria-label="Activar <?= View::escape($item->name) ?>">
                                    <span></span>
                                    No vigente
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
