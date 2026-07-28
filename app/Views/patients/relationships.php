<?php

use Core\View;

$familyGraph ??= ['nodes' => [], 'relationships' => []];
$familyNodes = $familyGraph['nodes'] ?? [];
$familyRelationships = $familyGraph['relationships'] ?? [];
$sessionParticipantCount = 0;
$mapOnlyParticipantCount = 0;

foreach ($familyNodes as $node) {
    if (($node['kind'] ?? '') === 'patient') {
        continue;
    }

    if (($node['participation_status'] ?? 'NP') === 'SP') {
        $sessionParticipantCount++;
    } else {
        $mapOnlyParticipantCount++;
    }
}
$participantTypes ??= [];
$manualPersonTypes = array_values(array_filter(
    $participantTypes,
    fn ($type): bool => $type->code !== 'paciente'
));
$relationshipSuggestions = [
    'Padre de',
    'Madre de',
    'Hermano de',
    'Hermana de',
    'Tio de',
    'Tia de',
    'Abuelo de',
    'Abuela de',
    'Pareja de',
    'Cuidador de',
    'Vive con',
    'Apoyo de',
    'Conflicto con',
];

ob_start();
?>
<nav class="record-tabs" aria-label="Secciones de ficha paciente">
    <a href="/patients/<?= View::escape((string) $patient->id) ?>">Ficha paciente</a>
    <a class="active" href="/patients/<?= View::escape((string) $patient->id) ?>/vinculos">Vinculos con paciente</a>
    <a href="#">Sesiones H.Clinica</a>
    <a href="#">Informes</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/create">Sesiones</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/consents/create">Consentimientos</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/ai-history">Historial IA</a>
    <a href="#">Recetas</a>
    <a href="#">Archivos</a>
</nav>

<section class="action-toolbar">
    <strong><?= View::escape($patient->fullName) ?></strong>
    <span class="toolbar-code"><?= View::escape($patient->code) ?></span>
    <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>">Volver a ficha</a>
    <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/create">Nueva sesion</a>
</section>

<section class="panel record-card" id="family-map">
    <div class="panel-header compact">
        <div>
            <p class="eyebrow">Vinculos con paciente</p>
            <h1>Mapa familiar y red de apoyo</h1>
            <p>Los nodos pueden venir de sesiones o ser personas agregadas para entender la vida del paciente. Tu defines las flechas entre ellos.</p>
        </div>
    </div>

    <div class="family-legend">
        <span class="family-node-status is-sp">SP</span>
        <small>Si participo en alguna sesion</small>
        <span class="family-node-status is-np">NP</span>
        <small>No participo; agregado solo para el mapa/vinculos</small>
    </div>

    <div class="family-summary">
        <article>
            <span>Personas desde sesiones</span>
            <strong><?= View::escape((string) $sessionParticipantCount) ?></strong>
            <small>Participantes con evidencia en sesiones registradas.</small>
        </article>
        <article>
            <span>Personas solo del mapa</span>
            <strong><?= View::escape((string) $mapOnlyParticipantCount) ?></strong>
            <small>Personas relevantes agregadas por el profesional.</small>
        </article>
        <article>
            <span>Relaciones activas</span>
            <strong data-family-relationship-count><?= View::escape((string) count($familyRelationships)) ?></strong>
            <small>Flechas creadas en este modelo de vinculos.</small>
        </article>
    </div>

    <div class="family-workflow" aria-label="Como usar el mapa de vinculos">
        <article>
            <span>1</span>
            <strong>Mover personas</strong>
            <small>Arrastra cada nodo para ordenar el mapa como te haga sentido clinico.</small>
        </article>
        <article>
            <span>2</span>
            <strong>Elegir origen y destino</strong>
            <small>Haz clic en dos personas: primero desde, luego hacia.</small>
        </article>
        <article>
            <span>3</span>
            <strong>Guardar vinculo</strong>
            <small>Escribe la relacion, por ejemplo "Madre de", "Vive con" o "Apoyo de".</small>
        </article>
    </div>

    <form class="family-person-form" method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/family-people">
        <label>
            Tipo de persona
            <select name="participant_type" required>
                <option value="">Seleccione tipo de persona</option>
                <?php foreach ($manualPersonTypes as $participantType): ?>
                    <option value="<?= View::escape($participantType->code) ?>">
                        <?= View::escape($participantType->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Nombre visible
            <input name="display_name" placeholder="Ej: Tio Juan, Amiga Carla, Abuelo Luis" required>
        </label>
        <button class="button small secondary" type="submit">Agregar persona al mapa</button>
        <small>Usalo para personas importantes del caso que aun no aparecen en sesiones. Quedaran como NP hasta que participen.</small>
    </form>

    <form class="family-relation-form is-visual" method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/family-relationships" data-family-relation-form>
        <datalist id="family-relationship-suggestions">
            <?php foreach ($relationshipSuggestions as $suggestion): ?>
                <option value="<?= View::escape($suggestion) ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <input type="hidden" name="from_node_key" data-family-from-input>
        <input type="hidden" name="to_node_key" data-family-to-input>
        <input type="hidden" name="replace_existing_relationship" value="0" data-family-replace-confirmed>
        <input type="hidden" name="replace_relationship_id" value="" data-family-replace-id>

        <div class="family-pick-state">
            <span>1. Desde: <strong data-family-from-label>clic en una persona</strong></span>
            <span>2. Hacia: <strong data-family-to-label>clic en otra persona</strong></span>
        </div>

        <label>
            Relacion
            <input name="relationship_label" list="family-relationship-suggestions" placeholder="Ej: Padre de, Hermano de, Vive con" required data-family-relation-input>
        </label>

        <button class="button small" type="submit" data-family-save disabled>Unir participantes</button>
        <button class="button small secondary" type="button" data-family-clear>Limpiar seleccion</button>
        <div class="family-relation-preview" data-family-relation-preview title="Selecciona dos personas y escribe la relacion">
            <span>
                <small>Desde</small>
                <strong data-family-preview-from>seleccione origen</strong>
            </span>
            <span>
                <small>Relacion</small>
                <strong data-family-preview-relation>escriba relacion</strong>
            </span>
            <span>
                <small>Hacia</small>
                <strong data-family-preview-to>seleccione destino</strong>
            </span>
        </div>
        <div class="family-replace-warning" data-family-replace-warning hidden role="dialog" aria-modal="true" aria-labelledby="family-replace-title">
            <div class="family-replace-dialog">
                <span class="family-replace-icon" aria-hidden="true">!</span>
                <div>
                    <strong id="family-replace-title">Ya existe una relacion entre estas personas</strong>
                    <p>Para no llenar el mapa con flechas duplicadas, el sistema mantiene una sola relacion activa entre el mismo origen y destino.</p>
                    <p>Actual: <span data-family-current-relation></span></p>
                    <p>Nueva: <span data-family-new-relation></span></p>
                    <div class="family-replace-actions">
                        <button class="button small" type="button" data-family-confirm-replace>Si, reemplazar</button>
                        <button class="button small secondary" type="button" data-family-cancel-replace>Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
        <small data-family-help>Haz clic en dos personas del diagrama para armar la flecha. No necesitas usar listados.</small>
    </form>

    <?php if (count($familyNodes) <= 1): ?>
        <div class="clinical-alert" role="status">
            <span class="clinical-alert-icon" aria-hidden="true">i</span>
            <div>
                <strong>Sin participantes para diagrama</strong>
                <p>Cuando registres participantes con nombre en las sesiones, apareceran aqui para relacionarlos.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="family-map" data-family-map>
            <svg class="family-map-lines" data-family-lines aria-hidden="true"></svg>

            <div class="family-node-canvas">
                <?php foreach ($familyNodes as $node): ?>
                    <?php $roleClass = preg_replace('/[^a-z0-9]+/', '-', strtolower($node['role'])) ?: 'participante'; ?>
                    <?php $participationStatus = (string) ($node['participation_status'] ?? 'NP'); ?>
                    <?php $participationLabel = $participationStatus === 'SP' ? 'Si participo en alguna sesion' : 'No participo; agregado solo para el mapa/vinculos'; ?>
                    <?php $nodeTooltip = $node['label'] . ' | Tipo: ' . $node['role'] . ' | ' . $participationStatus . ': ' . $participationLabel; ?>
                    <article
                        class="family-node role-<?= View::escape($roleClass) ?> <?= $node['kind'] === 'patient' ? 'is-patient' : '' ?>"
                        data-family-node="<?= View::escape($node['key']) ?>"
                        data-family-node-label="<?= View::escape($node['label']) ?>"
                        data-family-x="<?= View::escape((string) ($node['x'] ?? 50)) ?>"
                        data-family-y="<?= View::escape((string) ($node['y'] ?? 50)) ?>"
                        style="left: <?= View::escape((string) ($node['x'] ?? 50)) ?>%; top: <?= View::escape((string) ($node['y'] ?? 50)) ?>%;"
                        role="button"
                        tabindex="0"
                        title="<?= View::escape($nodeTooltip) ?>"
                        aria-label="<?= View::escape($nodeTooltip) ?>"
                    >
                        <span
                            class="family-node-status <?= $participationStatus === 'SP' ? 'is-sp' : 'is-np' ?>"
                            title="<?= View::escape($participationStatus . ': ' . $participationLabel) ?>"
                        >
                            <?= View::escape($participationStatus) ?>
                        </span>
                        <span class="person-icon" aria-hidden="true"></span>
                        <div>
                            <strong title="<?= View::escape($node['label']) ?>"><?= View::escape($node['label']) ?></strong>
                            <small title="<?= View::escape($node['role']) ?>"><?= View::escape($node['role']) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="family-relation-data" aria-hidden="true">
                <?php foreach ($familyRelationships as $relationship): ?>
                    <span
                        data-family-relation
                        data-id="<?= View::escape((string) $relationship['id']) ?>"
                        data-from="<?= View::escape($relationship['from']) ?>"
                        data-from-label="<?= View::escape($relationship['from_label']) ?>"
                        data-to="<?= View::escape($relationship['to']) ?>"
                        data-to-label="<?= View::escape($relationship['to_label']) ?>"
                        data-label="<?= View::escape($relationship['label']) ?>"
                        data-delete-url="/patients/<?= View::escape((string) $patient->id) ?>/family-relationships/<?= View::escape((string) $relationship['id']) ?>/desactivar"
                    ></span>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($familyRelationships === []): ?>
            <p class="form-help">Aun no hay flechas. Ejemplo: une Paciente con Papa como "Padre de", luego Papa con Tio Juan como "Hermano de".</p>
        <?php else: ?>
            <div class="family-relation-list">
                <?php foreach ($familyRelationships as $relationship): ?>
                    <article>
                        <strong><?= View::escape($relationship['from_label']) ?></strong>
                        <span><?= View::escape($relationship['label']) ?></span>
                        <strong><?= View::escape($relationship['to_label']) ?></strong>
                        <a class="action-link ghost" href="/patients/<?= View::escape((string) $patient->id) ?>/family-relationships/<?= View::escape((string) $relationship['id']) ?>/desactivar">Quitar</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$hideRightSidebar = true;
require dirname(__DIR__) . '/layout.php';
