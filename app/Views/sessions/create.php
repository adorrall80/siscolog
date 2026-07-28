<?php

use Core\View;

$sessionModalities ??= [];
$riskLevels ??= [];
$associatedPeople ??= [];
$topicTypes ??= [];
$topicSubtypesByType ??= [];
$allTopicSubtypes ??= [];
$topicSubtypePayload = [];

foreach ($topicTypes as $topicType) {
    $topicSubtypePayload[strtolower($topicType->name)] = array_map(
        fn ($subtype): string => $subtype->name,
        $topicSubtypesByType[(int) $topicType->id] ?? []
    );
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
        <datalist id="associated-people-suggestions">
            <?php foreach ($associatedPeople as $person): ?>
                <option value="<?= View::escape($person->displayName) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <datalist id="session-topic-types">
            <?php foreach ($topicTypes as $topicType): ?>
                <option value="<?= View::escape($topicType->name) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <datalist id="session-topic-subtypes"></datalist>
        <script type="application/json" data-session-topic-map><?= json_encode($topicSubtypePayload, JSON_UNESCAPED_UNICODE) ?></script>
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
                                placeholder="Seleccione categoria"
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
                                placeholder="Seleccione subtipo"
                            >
                        </label>
                    </div>
                </div>
                <div class="actions-row">
                    <button class="button small secondary" type="button" data-add-session-topic>Agregar tema</button>
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
                            Otro participante
                            <input
                                name="participants[otro][new_name]"
                                list="associated-people-suggestions"
                                placeholder="Seleccione o escriba participante"
                            >
                        </label>
                    </div>
                </div>
                <small>Si ya fue usado antes, aparecera como sugerencia al escribir. Si no existe, escribelo y quedara disponible para futuras sesiones.</small>
            </div>
        </fieldset>

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
