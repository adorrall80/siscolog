<?php

use Core\View;

$appointments ??= [];
$appointmentsByDate ??= [];
$calendar ??= ['view' => 'listado', 'title' => 'Listado'];
$summary ??= ['total' => 0, 'upcoming' => 0, 'past' => 0, 'not_vigente' => 0];
$filters ??= ['q' => '', 'estado' => 'todos', 'periodo' => 'todos', 'modality' => '', 'risk_level' => '', 'date_from' => '', 'date_to' => '', 'orden' => 'desc', 'vista' => 'listado', 'calendar_date' => date('Y-m-d')];
$sessionModalities ??= [];
$riskLevels ??= [];

$modalityLabels = [];
$riskLabels = [];
$riskColors = [];

foreach ($sessionModalities as $modality) {
    $modalityLabels[$modality->code] = $modality->name;
}

foreach ($riskLevels as $riskLevel) {
    $riskLabels[$riskLevel->code] = $riskLevel->name;
    $riskColors[$riskLevel->code] = $riskLevel->color ?: 'green';
}

$activeFilterCount = 0;
$activeFilterCount += trim((string) ($filters['q'] ?? '')) !== '' ? 1 : 0;
$activeFilterCount += ($filters['estado'] ?? 'todos') !== 'todos' ? 1 : 0;
$activeFilterCount += ($filters['periodo'] ?? 'todos') !== 'todos' ? 1 : 0;
$activeFilterCount += ($filters['modality'] ?? '') !== '' ? 1 : 0;
$activeFilterCount += ($filters['risk_level'] ?? '') !== '' ? 1 : 0;
$activeFilterCount += ($filters['date_from'] ?? '') !== '' ? 1 : 0;
$activeFilterCount += ($filters['date_to'] ?? '') !== '' ? 1 : 0;

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Agenda clinica</p>
            <h1>Sesiones</h1>
            <p>Listado general de sesiones registradas, filtrado segun permisos del usuario.</p>
        </div>
        <a class="button" href="/patients">Buscar paciente</a>
    </div>

    <form class="appointment-filter-shell" method="get" action="/citas" data-appointment-filter-form>
        <input type="hidden" name="orden" value="<?= View::escape((string) ($filters['orden'] ?? 'desc')) ?>" data-appointment-order-value>
        <input type="hidden" name="vista" value="<?= View::escape((string) ($filters['vista'] ?? 'listado')) ?>" data-appointment-view-value>

        <div class="appointment-filter-toolbar">
            <button
                class="button small secondary appointment-toolbar-button"
                type="button"
                aria-expanded="false"
                aria-controls="appointment-filter-panel"
                data-appointment-filter-toggle
            >
                <span class="appointment-toolbar-icon" aria-hidden="true">☷</span>
                Filtros
                <span class="appointment-filter-count"><?= View::escape((string) $activeFilterCount) ?></span>
            </button>

            <div class="appointment-toolbar-spacer"></div>

            <div class="appointment-toolbar-group" aria-label="Orden de las sesiones">
                <span class="appointment-toolbar-label">Orden</span>
                <button
                    class="appointment-icon-choice <?= ($filters['orden'] ?? 'desc') === 'desc' ? 'active' : '' ?>"
                    type="button"
                    aria-label="Más recientes primero"
                    title="Más recientes primero"
                    data-appointment-order="desc"
                >↓</button>
                <button
                    class="appointment-icon-choice <?= ($filters['orden'] ?? '') === 'asc' ? 'active' : '' ?>"
                    type="button"
                    aria-label="Más antiguas primero"
                    title="Más antiguas primero"
                    data-appointment-order="asc"
                >↑</button>
            </div>

            <div class="appointment-toolbar-group" aria-label="Vista de las sesiones">
                <span class="appointment-toolbar-label">Vista</span>
                <button
                    class="appointment-icon-choice <?= ($filters['vista'] ?? 'listado') === 'listado' ? 'active' : '' ?>"
                    type="button"
                    aria-label="Vista de listado"
                    title="Listado"
                    data-appointment-view="listado"
                >☰</button>
                <button
                    class="appointment-icon-choice <?= ($filters['vista'] ?? '') === 'semana' ? 'active' : '' ?>"
                    type="button"
                    aria-label="Vista semanal"
                    title="Semana"
                    data-appointment-view="semana"
                >▦</button>
                <button
                    class="appointment-icon-choice <?= ($filters['vista'] ?? '') === 'mes' ? 'active' : '' ?>"
                    type="button"
                    aria-label="Vista mensual"
                    title="Mes"
                    data-appointment-view="mes"
                >▣</button>
            </div>
        </div>

        <div class="inline-search appointment-filters" id="appointment-filter-panel" data-appointment-filter-panel hidden>
        <label>
            Buscar
            <input name="q" value="<?= View::escape((string) ($filters['q'] ?? '')) ?>" placeholder="Paciente, codigo o motivo">
        </label>

        <label>
            Vigencia
            <select name="estado">
                <option value="todos" <?= ($filters['estado'] ?? 'todos') === 'todos' ? 'selected' : '' ?>>Todas</option>
                <option value="vigentes" <?= ($filters['estado'] ?? '') === 'vigentes' ? 'selected' : '' ?>>Vigentes</option>
                <option value="no_vigentes" <?= ($filters['estado'] ?? '') === 'no_vigentes' ? 'selected' : '' ?>>No vigentes</option>
            </select>
        </label>

        <label>
            Periodo
            <select name="periodo">
                <option value="todos" <?= ($filters['periodo'] ?? 'todos') === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="hoy" <?= ($filters['periodo'] ?? '') === 'hoy' ? 'selected' : '' ?>>Hoy</option>
                <option value="proximas" <?= ($filters['periodo'] ?? '') === 'proximas' ? 'selected' : '' ?>>Proximas</option>
                <option value="historicas" <?= ($filters['periodo'] ?? '') === 'historicas' ? 'selected' : '' ?>>Historicas</option>
            </select>
        </label>

        <label>
            Modalidad
            <select name="modality">
                <option value="">Todas</option>
                <?php foreach ($sessionModalities as $modality): ?>
                    <option value="<?= View::escape($modality->code) ?>" <?= ($filters['modality'] ?? '') === $modality->code ? 'selected' : '' ?>>
                        <?= View::escape($modality->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Riesgo
            <select name="risk_level">
                <option value="">Todos</option>
                <?php foreach ($riskLevels as $riskLevel): ?>
                    <option value="<?= View::escape($riskLevel->code) ?>" <?= ($filters['risk_level'] ?? '') === $riskLevel->code ? 'selected' : '' ?>>
                        <?= View::escape($riskLevel->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Desde
            <input type="date" name="date_from" value="<?= View::escape((string) ($filters['date_from'] ?? '')) ?>">
        </label>

        <label>
            Hasta
            <input type="date" name="date_to" value="<?= View::escape((string) ($filters['date_to'] ?? '')) ?>">
        </label>

        <label>
            Fecha calendario
            <input type="date" name="calendar_date" value="<?= View::escape((string) ($filters['calendar_date'] ?? date('Y-m-d'))) ?>">
        </label>

        <button class="button small" type="submit">Filtrar</button>
        <a class="button small secondary" href="/citas">Limpiar</a>
        </div>
    </form>

    <div class="summary-grid">
        <article class="summary-card">
            <span>Total sesiones</span>
            <strong><?= View::escape((string) $summary['total']) ?></strong>
        </article>

        <article class="summary-card">
            <span>Proximas</span>
            <strong><?= View::escape((string) $summary['upcoming']) ?></strong>
            <small>Desde hoy en adelante</small>
        </article>

        <article class="summary-card">
            <span>Historicas</span>
            <strong><?= View::escape((string) $summary['past']) ?></strong>
            <small>Fechas anteriores</small>
        </article>

        <article class="summary-card">
            <span>No vigentes</span>
            <strong><?= View::escape((string) $summary['not_vigente']) ?></strong>
            <small>Sesiones apagadas por bit 0</small>
        </article>
    </div>

    <?php if ($appointments === []): ?>
        <div class="clinical-alert" role="status">
            <span class="clinical-alert-icon" aria-hidden="true">i</span>
            <div>
                <strong>Sin citas registradas</strong>
                <p>Registra una sesion en la ficha del paciente para que aparezca en este modulo.</p>
            </div>
        </div>
    <?php else: ?>
        <?php if (($calendar['view'] ?? 'listado') === 'semana'): ?>
            <section class="calendar-board">
                <div class="panel-header compact">
                    <div>
                        <p class="eyebrow">Calendario semanal</p>
                        <h2><?= View::escape((string) ($calendar['title'] ?? 'Semana')) ?></h2>
                    </div>
                </div>
                <div class="calendar-week">
                    <?php foreach (($calendar['days'] ?? []) as $day): ?>
                        <article class="calendar-day-column">
                            <header><?= View::escape((string) $day['label']) ?></header>
                            <?php if (($day['appointments'] ?? []) === []): ?>
                                <p class="calendar-empty">Sin sesiones</p>
                            <?php else: ?>
                                <?php foreach ($day['appointments'] as $appointment): ?>
                                    <a class="calendar-event <?= $appointment->isActive ? '' : 'is-muted' ?>" href="/citas/<?= View::escape((string) $appointment->id) ?>">
                                        <strong><?= View::escape(date('H:i', strtotime($appointment->sessionDate))) ?></strong>
                                        <span><?= View::escape($appointment->patientName) ?></span>
                                        <small><?= View::escape($modalityLabels[$appointment->modality] ?? $appointment->modality) ?></small>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (($calendar['view'] ?? 'listado') === 'mes'): ?>
            <section class="calendar-board">
                <div class="panel-header compact">
                    <div>
                        <p class="eyebrow">Calendario mensual</p>
                        <h2><?= View::escape((string) ($calendar['title'] ?? 'Mes')) ?></h2>
                    </div>
                </div>
                <div class="calendar-month-labels">
                    <span>Lun</span><span>Mar</span><span>Mie</span><span>Jue</span><span>Vie</span><span>Sab</span><span>Dom</span>
                </div>
                <div class="calendar-month">
                    <?php foreach (($calendar['cells'] ?? []) as $cell): ?>
                        <article class="calendar-month-cell <?= $cell['date'] === null ? 'is-empty' : '' ?>">
                            <header><?= View::escape((string) $cell['label']) ?></header>
                            <?php foreach (($cell['appointments'] ?? []) as $appointment): ?>
                                <a class="calendar-mini-event <?= $appointment->isActive ? '' : 'is-muted' ?>" href="/citas/<?= View::escape((string) $appointment->id) ?>">
                                    <?= View::escape(date('H:i', strtotime($appointment->sessionDate))) ?> · <?= View::escape($appointment->patientName) ?>
                                </a>
                            <?php endforeach; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="appointment-calendar" aria-label="Calendario de citas">
            <?php foreach ($appointmentsByDate as $date => $dayAppointments): ?>
                <article class="appointment-day">
                    <header>
                        <span><?= View::escape(date('d/m/Y', strtotime($date))) ?></span>
                        <strong><?= View::escape((string) count($dayAppointments)) ?> cita<?= count($dayAppointments) === 1 ? '' : 's' ?></strong>
                    </header>

                    <?php foreach ($dayAppointments as $appointment): ?>
                        <a class="appointment-slot <?= $appointment->isActive ? '' : 'is-muted' ?>" href="/citas/<?= View::escape((string) $appointment->id) ?>">
                            <span><?= View::escape(date('H:i', strtotime($appointment->sessionDate))) ?></span>
                            <strong><?= View::escape($appointment->patientName) ?></strong>
                            <small><?= View::escape($modalityLabels[$appointment->modality] ?? $appointment->modality) ?> · <?= $appointment->isActive ? 'Vigente' : 'No vigente' ?></small>
                        </a>
                    <?php endforeach; ?>
                </article>
            <?php endforeach; ?>
        </section>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Modalidad</th>
                        <th>Riesgo</th>
                        <th>Motivo</th>
                        <th>Vigencia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td>
                                <strong><?= View::escape(date('d/m/Y H:i', strtotime($appointment->sessionDate))) ?></strong>
                            </td>
                            <td>
                                <?= View::escape($appointment->patientName) ?>
                                <small><?= View::escape($appointment->patientCode) ?></small>
                            </td>
                            <td><?= View::escape($modalityLabels[$appointment->modality] ?? $appointment->modality) ?></td>
                            <td>
                                <span class="state tone-<?= View::escape($riskColors[$appointment->riskLevel] ?? 'green') ?>">
                                    <?= View::escape($riskLabels[$appointment->riskLevel] ?? $appointment->riskLevel) ?>
                                </span>
                            </td>
                            <td><?= View::escape($appointment->reason ?: 'Sin motivo registrado') ?></td>
                            <td>
                                <span class="state tone-<?= $appointment->isActive ? 'green' : 'red' ?>">
                                    <?= $appointment->isActive ? 'Vigente' : 'No vigente' ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <a class="action-link" href="/patients/<?= View::escape((string) $appointment->patientId) ?>">Ficha</a>
                                <a class="action-link ghost" href="/citas/<?= View::escape((string) $appointment->id) ?>">Ver cita</a>
                                <a class="action-link ghost" href="/citas/<?= View::escape((string) $appointment->id) ?>/editar">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
