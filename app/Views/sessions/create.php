<?php

use Core\View;

$sessionModalities ??= [];
$riskLevels ??= [];
$associatedPeople ??= [];
$topicTypes ??= [];
$topicSubtypesByType ??= [];
$allTopicSubtypes ??= [];
$topicSubtypePayload = [];
$topicSubtypeScopePayload = [];
$participantTypeLabels = [];
$newParticipantTypes = [];

foreach ($participantTypes as $participantType) {
    $participantTypeLabels[$participantType->code] = $participantType->name;
    if ($participantType->code !== 'paciente') {
        $newParticipantTypes[] = $participantType;
    }
}

$linkedParticipantPayload = array_map(
    fn ($person): array => [
        'id' => (int) $person->id,
        'type' => $person->participantType,
        'name' => $person->displayName,
        'label' => ($participantTypeLabels[$person->participantType] ?? $person->participantType)
            . ' — ' . $person->displayName,
    ],
    $associatedPeople
);

foreach ($topicTypes as $topicType) {
    $topicKey = strtolower($topicType->name);
    $topicSubtypePayload[$topicKey] = array_map(
        fn ($subtype): string => $subtype->name,
        $topicSubtypesByType[(int) $topicType->id] ?? []
    );
    $topicSubtypeScopePayload[$topicKey] = [];
    foreach ($topicSubtypesByType[(int) $topicType->id] ?? [] as $subtype) {
        $topicSubtypeScopePayload[$topicKey][$subtype->name] = $subtype->relationIsPublic ? 'General' : 'Mío';
    }
}

$allTopicSubtypePayload = array_map(
    fn ($subtype): string => $subtype->name,
    $allTopicSubtypes
);

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Registro clinico</p>
            <h1>Nueva sesion</h1>
            <p>Paciente: <?= View::escape($patient->fullName) ?></p>
        </div>
        <a class="button small" href="/patients/<?= View::escape((string) $patient->id) ?>">Volver a ficha</a>
    </div>

    <form method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/sessions" class="form-grid">
        <script type="application/json" data-linked-participants><?= json_encode($linkedParticipantPayload, JSON_UNESCAPED_UNICODE) ?></script>
        <datalist id="session-topic-types">
            <?php foreach ($topicTypes as $topicType): ?>
                <option
                    value="<?= View::escape($topicType->name) ?>"
                    label="<?= $topicType->isPublic ? 'General' : 'Mío' ?>"
                ></option>
            <?php endforeach; ?>
        </datalist>
        <datalist id="session-topic-subtypes"></datalist>
        <script type="application/json" data-session-topic-map><?= json_encode($topicSubtypePayload, JSON_UNESCAPED_UNICODE) ?></script>
        <script type="application/json" data-session-topic-scope-map><?= json_encode($topicSubtypeScopePayload, JSON_UNESCAPED_UNICODE) ?></script>
        <script type="application/json" data-session-topic-all-subtypes><?= json_encode($allTopicSubtypePayload, JSON_UNESCAPED_UNICODE) ?></script>

        <fieldset>
            <legend>Datos de la sesion</legend>

            <label>
                Fecha y hora
                <input type="datetime-local" name="session_date" required>
            </label>

            <label>
                Modalidad
                <select name="modality" required>
                    <option value="" selected disabled>Seleccione modalidad</option>
                    <?php foreach ($sessionModalities as $modality): ?>
                        <option value="<?= View::escape($modality->code) ?>">
                            <?= View::escape($modality->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Nivel de riesgo
                <select name="risk_level" required>
                    <option value="" selected disabled>Seleccione nivel de riesgo</option>
                    <?php foreach ($riskLevels as $riskLevel): ?>
                        <option value="<?= View::escape($riskLevel->code) ?>">
                            <?= View::escape($riskLevel->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Motivo de sesion o foco
                <textarea name="reason" rows="4"></textarea>
            </label>

            <div class="checkbox-group">
                <span>Temas tratados</span>
                <div class="participant-grid">
                    <div class="participant-card">
                        <label>
                            Tipo de tema
                            <input
                                name="topic_type_text"
                                list="session-topic-types"
                                data-session-topic-type
                                placeholder="Seleccione o escriba un tema nuevo"
                            >
                        </label>
                    </div>

                    <div class="participant-card">
                        <label>
                            Subtipo
                            <input
                                name="topic_subtype_text"
                                list="session-topic-subtypes"
                                data-session-topic-subtype
                                placeholder="Seleccione o escriba un subtipo nuevo"
                            >
                        </label>
                    </div>
                </div>
                <p class="form-help">Si el tema o subtipo no existe, puedes escribirlo. La nueva relación quedará guardada como privada para tu usuario.</p>
                <div class="actions-row">
                    <button class="button small secondary" type="button" data-add-session-topic>Agregar tema</button>
                    <button class="button small secondary" type="button" data-clear-session-topic>Limpiar selección</button>
                </div>
                <div class="subtype-chip-list" data-session-topic-selected>
                    <span class="tag tone-gray" data-empty-session-topic>Sin temas agregados todavia</span>
                </div>
                <div data-session-topic-hidden></div>
                <small data-session-topic-help>El tema es obligatorio. Puedes agregar uno o varios pares tipo-subtipo. Si el tipo o subtipo no existe, se creara y se asociara al guardar.</small>
            </div>

            <div class="checkbox-group">
                <span>Participantes de la sesion</span>
                <div class="participant-grid">
                    <div class="participant-card">
                        <label class="check-card">
                            <input type="checkbox" name="participants[paciente][selected]" value="1" checked>
                            <span>Paciente</span>
                        </label>
                    </div>

                    <div class="participant-card">
                        <label>
                            Participante vinculado
                            <select data-linked-participant-select>
                                <option value="">Seleccione una persona vinculada</option>
                                <?php foreach ($linkedParticipantPayload as $linkedParticipant): ?>
                                    <option value="<?= View::escape((string) $linkedParticipant['id']) ?>">
                                        <?= View::escape($linkedParticipant['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="button small secondary" type="button" data-add-linked-participant>Agregar participante</button>
                    </div>
                </div>
                <div class="subtype-chip-list" data-linked-participant-selected>
                    <span class="tag tone-gray" data-empty-linked-participant>Sin participantes adicionales</span>
                </div>
                <div data-linked-participant-hidden></div>
                <small data-linked-participant-help>
                    Solo se muestran personas vinculadas a este paciente.
                    Si falta alguien, puedes agregarlo aquí sin salir de la sesión.
                </small>
                <div class="form-actions compact">
                    <button
                        class="button small secondary"
                        type="button"
                        data-open-new-session-participant
                    >
                        Agregar nueva persona
                    </button>
                </div>
            </div>
        </fieldset>

        <div class="modal-backdrop" data-new-session-participant-modal data-save-message="La persona se guardará definitivamente cuando guardes la sesión." hidden>
            <section class="modal-panel session-participant-dialog" role="dialog" aria-modal="true" aria-labelledby="new-session-participant-title">
                <div class="modal-head">
                    <div>
                        <p class="eyebrow">Participante de la sesión</p>
                        <h2 id="new-session-participant-title">Agregar nueva persona</h2>
                    </div>
                    <button class="icon-button" type="button" data-close-new-session-participant aria-label="Cerrar">×</button>
                </div>
                <p>Esta persona participará en la sesión y quedará vinculada al paciente, disponible para futuras sesiones y para el mapa.</p>
                <div class="form-grid two-columns">
                    <label>
                        Tipo de persona
                        <select data-new-session-participant-type>
                            <option value="">Seleccione tipo de persona</option>
                            <?php foreach ($newParticipantTypes as $participantType): ?>
                                <option value="<?= View::escape($participantType->code) ?>"><?= View::escape($participantType->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Nombre visible
                        <input type="text" maxlength="150" placeholder="Ej: Tío Juan, Amiga Carla, Abuelo Luis" data-new-session-participant-name>
                    </label>
                </div>
                <small data-new-session-participant-error>La persona se guardará definitivamente cuando guardes la sesión.</small>
                <div class="form-actions">
                    <button class="button secondary" type="button" data-close-new-session-participant>Cancelar</button>
                    <button class="button" type="button" data-confirm-new-session-participant>Agregar participante</button>
                </div>
            </section>
        </div>

        <fieldset class="single-column">
            <legend>Nota clinica estructurada</legend>

            <label>
                Relato del paciente
                <textarea name="subjective_note" rows="5"></textarea>
            </label>

            <label>
                Observaciones del profesional
                <textarea name="objective_note" rows="5"></textarea>
            </label>

            <label>
                Impresion clinica
                <textarea name="clinical_impression" rows="5"></textarea>
            </label>

            <label>
                Acuerdos
                <textarea name="agreements" rows="4"></textarea>
            </label>

            <label>
                Proximos pasos
                <textarea name="next_steps" rows="4"></textarea>
            </label>
        </fieldset>

        <button class="button" type="submit">Guardar sesion</button>
    </form>
</section>

<?php
$content = ob_get_clean();
$hideRightSidebar = true;
require dirname(__DIR__) . '/layout.php';
