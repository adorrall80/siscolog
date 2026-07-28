<?php

use Core\View;

$roleLabels = [];
$roleColors = [];
foreach ($roles as $role) {
    $roleLabels[$role->code] = $role->name;
    $roleColors[$role->code] = $role->color ?: 'gray';
}

$statusLabels = [];
$statusColors = [];
foreach ($statuses as $status) {
    $statusLabels[$status->code] = $status->name;
    $statusColors[$status->code] = $status->color ?: 'gray';
}

$publicFilters = [
    'all' => 'todos',
    'active' => 'activos',
    'inactive' => 'inactivos',
];

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Administracion</p>
            <h1>Usuarios del sistema</h1>
            <p>Gestiona cuentas, roles y estados usando los mantenedores configurados.</p>
        </div>
        <a class="button" href="/usuarios/crear">Nuevo usuario</a>
    </div>

    <div class="summary-grid">
        <article class="summary-card">
            <span>Total usuarios</span>
            <strong><?= View::escape((string) $summary['total']) ?></strong>
        </article>
        <article class="summary-card">
            <span>Activos</span>
            <strong><?= View::escape((string) $summary['active']) ?></strong>
        </article>
        <article class="summary-card">
            <span>Inactivos</span>
            <strong><?= View::escape((string) $summary['inactive']) ?></strong>
        </article>
    </div>

    <nav class="filter-chips" aria-label="Filtros de usuarios">
        <?php foreach (['all' => 'Todos', 'active' => 'Activos', 'inactive' => 'Inactivos'] as $filterValue => $filterLabel): ?>
            <?php
            $query = [];
            if ($filterValue !== 'all') {
                $query['estado'] = $publicFilters[$filterValue];
            }
            if ($activeSearch !== '') {
                $query['q'] = $activeSearch;
            }
            $queryString = $query === [] ? '' : '?' . http_build_query($query);
            ?>
            <a class="<?= $activeFilter === $filterValue ? 'active' : '' ?>" href="/usuarios<?= View::escape($queryString) ?>">
                <?= View::escape($filterLabel) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form class="inline-search" method="get" action="/usuarios">
        <?php if ($activeFilter !== 'all'): ?>
            <input type="hidden" name="estado" value="<?= View::escape($publicFilters[$activeFilter]) ?>">
        <?php endif; ?>
        <label>
            Buscar
            <input name="q" value="<?= View::escape($activeSearch) ?>" placeholder="Nombre, email, rol o estado">
        </label>
        <button class="button small" type="submit">Buscar</button>
        <?php if ($activeSearch !== ''): ?>
            <a class="button small secondary" href="/usuarios<?= $activeFilter === 'all' ? '' : '?estado=' . View::escape($publicFilters[$activeFilter]) ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users === []): ?>
                    <tr>
                        <td colspan="6">
                            <strong>No hay usuarios registrados.</strong>
                            <small>Agrega usuarios para controlar acceso y responsabilidades.</small>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= View::escape($user->name) ?></strong></td>
                        <td><?= View::escape($user->email) ?></td>
                        <td>
                            <span class="state tone-<?= View::escape($roleColors[$user->role] ?? 'gray') ?>">
                                <?= View::escape($roleLabels[$user->role] ?? $user->role) ?>
                            </span>
                        </td>
                        <td>
                            <span class="state tone-<?= View::escape($statusColors[$user->status] ?? 'gray') ?>">
                                <?= View::escape($statusLabels[$user->status] ?? $user->status) ?>
                            </span>
                        </td>
                        <td>
                            <span class="state tone-<?= $user->isActive ? 'green' : 'red' ?>">
                                <?= $user->isActive ? 'Si' : 'No' ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <a class="action-link ghost" href="/usuarios/<?= View::escape((string) $user->id) ?>/editar">Editar</a>
                            <?php if ($user->isActive): ?>
                                <a class="switch-action is-on" href="/usuarios/<?= View::escape((string) $user->id) ?>/desactivar" aria-label="Desactivar usuario <?= View::escape($user->name) ?>">
                                    <span></span>
                                    Vigente
                                </a>
                            <?php else: ?>
                                <a class="switch-action is-off" href="/usuarios/<?= View::escape((string) $user->id) ?>/activar" aria-label="Activar usuario <?= View::escape($user->name) ?>">
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
