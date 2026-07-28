<?php

use Core\View;

$consentTypes ??= [];
$consents ??= [];
$typeLabels = [];

foreach ($consentTypes as $type) {
    $typeLabels[$type->code] = $type->name;
}

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Consentimiento informado</p>
            <h1>Nuevo consentimiento</h1>
            <p>Paciente: <?= View::escape($patient->fullName) ?></p>
        </div>
        <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>">Volver a ficha</a>
    </div>

    <form method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/consents" class="form-grid">
        <fieldset>
            <legend>Documento y alcance</legend>

            <label>
                Tipo de consentimiento
                <select name="consent_type" required>
                    <option value="" selected disabled>Seleccione tipo de consentimiento</option>
                    <?php foreach ($consentTypes as $type): ?>
                        <option value="<?= View::escape($type->code) ?>">
                            <?= View::escape($type->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Version del documento
                <input name="document_version" value="v1.0" required>
            </label>

            <label>
                Estado de aceptacion
                <select name="accepted">
                    <option value="1" selected>Aceptado</option>
                    <option value="0">No aceptado / revocado</option>
                </select>
            </label>
        </fieldset>

        <fieldset>
            <legend>Fechas</legend>

            <label>
                Fecha de aceptacion
                <input type="datetime-local" name="accepted_at">
            </label>

            <label>
                Fecha de revocacion
                <input type="datetime-local" name="revoked_at">
            </label>

            <p class="form-help">Si dejas la fecha vacia, el sistema usara la fecha actual segun corresponda.</p>
        </fieldset>

        <button class="button" type="submit">Guardar consentimiento</button>
    </form>
</section>

<section class="panel">
    <div class="panel-header compact">
        <div>
            <p class="eyebrow">Historial</p>
            <h2>Consentimientos registrados</h2>
            <p>Despues de guardar, el registro queda visible aqui para confirmar version, estado y fecha.</p>
        </div>
    </div>

    <?php if ($consents === []): ?>
        <p>No hay consentimientos registrados todavia.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Version</th>
                        <th>Aceptacion</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consents as $consent): ?>
                        <tr>
                            <td><?= View::escape($typeLabels[$consent->consentType] ?? $consent->consentType) ?></td>
                            <td><?= View::escape($consent->documentVersion) ?></td>
                            <td>
                                <span class="state tone-<?= $consent->accepted ? 'green' : 'red' ?>">
                                    <?= $consent->accepted ? 'Aceptado' : 'No aceptado' ?>
                                </span>
                            </td>
                            <td><?= View::escape($consent->accepted ? ($consent->acceptedAt ?: 'Sin fecha') : ($consent->revokedAt ?: 'Sin fecha')) ?></td>
                            <td>
                                <span class="state tone-<?= $consent->isActive ? 'green' : 'gray' ?>">
                                    <?= $consent->isActive ? 'Activo' : 'Historico' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="module-grid">
    <article class="module-card">
        <span class="tag">Seguridad IA</span>
        <h2>Requisito previo</h2>
        <p>El analisis con IA debe comprobar consentimiento activo antes de procesar informacion clinica.</p>
    </article>

    <article class="module-card">
        <span class="tag">Versionado</span>
        <h2>Trazabilidad</h2>
        <p>Guardar version permite saber exactamente que documento acepto el paciente.</p>
    </article>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
