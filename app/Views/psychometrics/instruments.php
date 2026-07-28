<?php

use Core\View;

$instruments ??= [];

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Administracion clinica</p>
            <h1>Instrumentos psicometricos</h1>
            <p>Catalogo base de escalas e instrumentos disponibles para registrar resultados por paciente.</p>
        </div>
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Instrumento</th>
                    <th>Rango</th>
                    <th>Cortes</th>
                    <th>Vigencia</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($instruments as $instrument): ?>
                    <tr>
                        <td><strong><?= View::escape($instrument->code) ?></strong></td>
                        <td>
                            <?= View::escape($instrument->name) ?>
                            <?php if ($instrument->description !== null && $instrument->description !== ''): ?>
                                <small><?= View::escape($instrument->description) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= View::escape((string) $instrument->minScore) ?> a <?= View::escape((string) $instrument->maxScore) ?></td>
                        <td>
                            <small>Seguimiento: <?= View::escape($instrument->cautionCutoff === null ? 'Sin corte' : (string) $instrument->cautionCutoff) ?></small>
                            <small>Critico: <?= View::escape($instrument->criticalCutoff === null ? 'Sin corte' : (string) $instrument->criticalCutoff) ?></small>
                        </td>
                        <td>
                            <span class="tag <?= $instrument->isActive ? 'is-success' : 'is-danger' ?>">
                                <?= $instrument->isActive ? 'Vigente' : 'No vigente' ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="action-link ghost" href="/instrumentos/<?= View::escape((string) $instrument->id) ?>/vista-previa">Vista previa</a>
                                <a class="action-link ghost" href="/instrumentos/<?= View::escape((string) $instrument->id) ?>/edit">Editar</a>
                                <?php if ($instrument->isActive): ?>
                                    <a class="switch-action is-on" href="/instrumentos/<?= View::escape((string) $instrument->id) ?>/desactivar" aria-label="Marcar instrumento no vigente">
                                        <span>Vigente</span><i></i>
                                    </a>
                                <?php else: ?>
                                    <a class="switch-action is-off" href="/instrumentos/<?= View::escape((string) $instrument->id) ?>/activar" aria-label="Marcar instrumento vigente">
                                        <span>No vigente</span><i></i>
                                    </a>
                                <?php endif; ?>
                            </div>
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
