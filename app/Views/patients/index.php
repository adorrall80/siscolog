<?php

use Core\View;

$statusLabels = [];
$statusColors = [];
$statusCounts = [];

foreach ($patientStatuses as $status) {
    $statusLabels[$status->code] = $status->name;
    $statusColors[$status->code] = $status->color ?: 'green';
    $statusCounts[$status->code] = 0;
}

foreach ($patients as $patient) {
    $statusCounts[$patient->status] = ($statusCounts[$patient->status] ?? 0) + 1;
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
            <p class="eyebrow">Gestion clinica</p>
            <h1>Pacientes</h1>
        </div>
        <a class="button" href="/patients/create">Crear paciente</a>
    </div>

    <div class="summary-grid">
        <article class="summary-card">
            <span>Total pacientes</span>
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

        <?php foreach ($patientStatuses as $status): ?>
            <article class="summary-card">
                <span><?= View::escape($status->name) ?></span>
                <strong><?= View::escape((string) ($statusCounts[$status->code] ?? 0)) ?></strong>
            </article>
        <?php endforeach; ?>
    </div>

    <nav class="filter-chips" aria-label="Filtros de pacientes">
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
            <a class="<?= $activeFilter === $filterValue ? 'active' : '' ?>" href="/patients<?= View::escape($queryString) ?>">
                <?= View::escape($filterLabel) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form class="inline-search" method="get" action="/patients">
        <?php if ($activeFilter !== 'all'): ?>
            <input type="hidden" name="estado" value="<?= View::escape($publicFilters[$activeFilter]) ?>">
        <?php endif; ?>
        <label>
            Buscar
            <input name="q" value="<?= View::escape($activeSearch) ?>" placeholder="Codigo, nombre, email o telefono">
        </label>
        <button class="button small" type="submit">Buscar</button>
        <?php if ($activeSearch !== ''): ?>
            <a class="button small secondary" href="/patients<?= $activeFilter === 'all' ? '' : '?estado=' . View::escape($publicFilters[$activeFilter]) ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>Estado clinico</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($patients === []): ?>
                    <tr>
                        <td colspan="7">
                            <strong>No hay pacientes para este filtro.</strong>
                            <small>Prueba limpiar la busqueda o revisar activos/inactivos.</small>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><strong><?= View::escape($patient->code) ?></strong></td>
                        <td><?= View::escape($patient->fullName) ?></td>
                        <td><?= View::escape($patient->email ?: 'Sin email') ?></td>
                        <td><?= View::escape($patient->phone ?: 'Sin telefono') ?></td>
                        <td>
                            <span class="state tone-<?= View::escape($statusColors[$patient->status] ?? 'green') ?>">
                                <?= View::escape($statusLabels[$patient->status] ?? $patient->status) ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <a class="action-link" href="/patients/<?= View::escape((string) $patient->id) ?>">Ver ficha</a>
                            <a class="action-link ghost" href="/patients/<?= View::escape((string) $patient->id) ?>/edit">Editar</a>
                            <?php if ($patient->isActive): ?>
                                <a class="switch-action is-on" href="/patients/<?= View::escape((string) $patient->id) ?>/desactivar" aria-label="Desactivar paciente <?= View::escape($patient->fullName) ?>">
                                    <span></span>
                                    Vigente
                                </a>
                            <?php else: ?>
                                <a class="switch-action is-off" href="/patients/<?= View::escape((string) $patient->id) ?>/activar" aria-label="Activar paciente <?= View::escape($patient->fullName) ?>">
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
