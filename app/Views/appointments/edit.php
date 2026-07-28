<?php

use Core\View;

$sessionModalities ??= [];
$riskLevels ??= [];
$associatedPeople ??= [];
$participantTypes ??= [];
$topicTypes ??= [];
$topicSubtypesByType ??= [];
$allTopicSubtypes ??= [];
$currentTopics ??= [];
$currentParticipants ??= [];
$topicSubtypePayload = [];
$topicSubtypeScopePayload = [];

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

$participantTypePayload = array_map(
    fn ($type): array => [
        'code' => $type->code,
        'name' => $type->name,
    ],
    $participantTypes
);

$dateValue = str_replace(' ', 'T', substr($session->sessionDate, 0, 16));

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Editar cita</p>
            <h1><?= View::escape($appointment->patientName) ?></h1>
            <p>Actualiza fecha, motivo, nota clinica, temas y participantes.</p>
        </div>
        <div class="actions-row">
            <a class="button small secondary" href="/citas/<?= View::escape((string) $appointment->id) ?>">Cancelar</a>
            <a class="button small" href="/patients/<?= View::escape((string) $appointment->patientId) ?>">Ver ficha</a>
        </div>
    </div>

    <form method="post" action="/citas/<?= View::escape((string) $appointment->id) ?>/actualizar" class="form-grid">
        <datalist id="associated-people-suggestions">
            <?php foreach ($associatedPeople as $person): ?>
                <option value="<?= View::escape($person->displayName) ?>"></option>
            <?php endforeach; ?>
        </datalist>
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
        <script type="application/json" data-session-participant-types><?= json_encode($participantTypePayload, JSON_UNESCAPED_UNICODE) ?></script>
        <input type="hidden" name="replace_participants" value="1">
        <input type="hidden" name="replace_topics" value="1">

        <fieldset>
            <legend>Datos de la cita</legend>

            <label>
                Fecha y hora
                <input type="datetime-local" name="session_date" required value="<?= View::escape($dateValue) ?>">
            </label>

            <label>
                Modalidad
                <select name="modality" required>
                    <?php foreach ($sessionModalities as $modality): ?>
                        <option value="<?= View::escape($modality->code) ?>" <?= $session->modality === $modality->code ? 'selected' : '' ?>>
                            <?= View::escape($modality->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Nivel de riesgo
                <select name="risk_level" required>
                    <?php foreach ($riskLevels as $riskLevel): ?>
                        <option value="<?= View::escape($riskLevel->code) ?>" <?= $session->riskLevel === $riskLevel->code ? 'selected' : '' ?>>
                            <?= View::escape($riskLevel->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Motivo de sesion o foco
                <textarea name="reason" rows="4"><?= View::escape($session->reason) ?></textarea>
            </label>
        </fieldset>

        <fieldset>
            <legend>Participantes y temas</legend>

            <div class="checkbox-group">
                <span>Participantes actuales</span>
                <div class="subtype-chip-list" data-existing-session-participants>
                    <?php if ($currentParticipants === []): ?>
                        <span class="tag tone-gray">Sin participantes registrados</span>
                    <?php else: ?>
                        <?php foreach ($currentParticipants as $index => $participant): ?>
                            <span class="subtype-chip is-associated" data-existing-session-participant data-participant-key="<?= View::escape(strtolower($participant['text'])) ?>">
                                <?= View::escape($participant['text']) ?>
                                <input type="hidden" name="participant_rows[<?= View::escape((string) $index) ?>][type]" value="<?= View::escape($participant['type']) ?>">
                                <input type="hidden" name="participant_rows[<?= View::escape((string) $index) ?>][text]" value="<?= View::escape($participant['text']) ?>">
                                <input type="hidden" name="participant_rows[<?= View::escape((string) $index) ?>][origin]" value="<?= View::escape($participant['origin']) ?>">
                                <input type="hidden" name="participant_rows[<?= View::escape((string) $index) ?>][person_id]" value="<?= View::escape((string) ($participant['person_id'] ?? '')) ?>">
                                <button type="button" class="chip-remove" aria-label="Quitar participante <?= View::escape($participant['text']) ?>">x</button>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="participant-grid">
                    <div class="participant-card">
                        <label>
                            Tipo de participante
                            <select data-session-participant-type>
                                <option value="">Seleccione participante</option>
                                <?php foreach ($participantTypes as $participantType): ?>
                                    <option value="<?= View::escape($participantType->code) ?>">
                                        <?= View::escape($participantType->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <div class="participant-card">
                        <label>
                            Nombre o descripcion
                            <input
                                data-session-participant-text
                                list="associated-people-suggestions"
                                placeholder="Ej: Juan Perez, abuelo materno"
                            >
                        </label>
                    </div>
                </div>
                <p class="form-help">Si el tema o subtipo no existe, puedes escribirlo. La nueva relación quedará guardada como privada para tu usuario.</p>

                <div class="actions-row">
                    <button class="button small secondary" type="button" data-add-session-participant>Agregar participante</button>
                </div>
                <div class="subtype-chip-list" data-session-participant-selected>
                    <span class="tag tone-gray" data-empty-session-participant>Sin participantes nuevos agregados</span>
                </div>
                <div data-session-participant-hidden></div>
                <small data-session-participant-help>Agrega uno o varios participantes antes de guardar. La lista final reemplazara los participantes activos de esta cita.</small>
            </div>

            <div class="checkbox-group">
                <span>Temas actuales</span>
                <div class="subtype-chip-list" data-existing-session-topics>
                    <?php if ($currentTopics === []): ?>
                        <span class="tag tone-gray">Sin temas registrados</span>
                    <?php else: ?>
                        <?php foreach ($currentTopics as $topic): ?>
                            <span class="subtype-chip is-associated" data-existing-session-topic>
                                <?= View::escape($topic['label']) ?>
                                <input type="hidden" name="topics[<?= View::escape((string) $topic['type_id']) ?>][<?= View::escape((string) $topic['subtype_id']) ?>]" value="1">
                                <button type="button" class="chip-remove" aria-label="Quitar tema <?= View::escape($topic['label']) ?>">x</button>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

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

                <div class="actions-row">
                    <button class="button small secondary" type="button" data-add-session-topic>Agregar tema</button>
                    <button class="button small secondary" type="button" data-clear-session-topic>Limpiar selección</button>
                </div>
                <div class="subtype-chip-list" data-session-topic-selected>
                    <span class="tag tone-gray" data-empty-session-topic>Sin temas nuevos agregados</span>
                </div>
                <div data-session-topic-hidden></div>
                <small data-session-topic-help>Los nuevos temas se suman a los actuales. Si no existe el tipo o subtipo, se creara y quedara relacionado.</small>
            </div>
        </fieldset>

        <fieldset class="single-column">
            <legend>Nota clinica estructurada</legend>

            <label>
                Relato del paciente
                <textarea name="subjective_note" rows="5"><?= View::escape($session->subjectiveNote) ?></textarea>
            </label>

            <label>
                Observaciones del profesional
                <textarea name="objective_note" rows="5"><?= View::escape($session->objectiveNote) ?></textarea>
            </label>

            <label>
                Impresion clinica
                <textarea name="clinical_impression" rows="5"><?= View::escape($session->clinicalImpression) ?></textarea>
            </label>

            <label>
                Acuerdos
                <textarea name="agreements" rows="4"><?= View::escape($session->agreements) ?></textarea>
            </label>

            <label>
                Proximos pasos
                <textarea name="next_steps" rows="4"><?= View::escape($session->nextSteps) ?></textarea>
            </label>
        </fieldset>

        <button class="button" type="submit">Guardar cambios</button>
    </form>
</section>
<?php
$content = ob_get_clean();
$hideRightSidebar = true;
require dirname(__DIR__) . '/layout.php';
