<?php

use Core\View;

$instruments ??= [];

ob_start();
?>
<nav class="record-tabs" aria-label="Secciones de ficha paciente">
    <a href="/patients/<?= View::escape((string) $patient->id) ?>">Ficha paciente</a>
    <a class="active" href="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/create">Instrumentos</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/create">Sesiones</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/consents/create">Consentimientos</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/ai-history">Historial IA</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/vinculos">Vinculos con paciente</a>
</nav>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Instrumentos psicometricos</p>
            <h1>Registrar resultado</h1>
            <p>Paciente: <?= View::escape($patient->fullName) ?>. La interpretacion debe ser siempre revisada por el profesional.</p>
        </div>
        <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>#instrumentos">Volver a ficha</a>
    </div>

    <div class="clinical-alert" role="status">
        <span class="clinical-alert-icon" aria-hidden="true">i</span>
        <div>
            <strong>Uso clinico supervisado</strong>
            <p>Un puntaje no equivale a diagnostico. Debe contrastarse con entrevista, contexto, riesgo y criterio profesional.</p>
        </div>
    </div>

    <section class="info-card">
        <p class="eyebrow">Aplicacion guiada</p>
        <h2>Aplicar por preguntas</h2>
        <p>Para instrumentos versionados, puedes responder cada pregunta y el sistema calcula puntaje, interpretacion y guarda el detalle.</p>
        <div class="subtype-chip-list">
            <?php foreach ($instruments as $instrument): ?>
                <a class="tag tone-green" href="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/<?= View::escape((string) $instrument->id) ?>/aplicar">
                    Aplicar <?= View::escape($instrument->name) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <form class="form-grid" method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos">
        <label>
            Instrumento
            <select name="instrument_id" required>
                <option value="">Seleccione instrumento</option>
                <?php foreach ($instruments as $instrument): ?>
                    <option
                        value="<?= View::escape((string) $instrument->id) ?>"
                        data-min-score="<?= View::escape((string) $instrument->minScore) ?>"
                        data-max-score="<?= View::escape((string) $instrument->maxScore) ?>"
                    >
                        <?= View::escape($instrument->name) ?> (<?= View::escape((string) $instrument->minScore) ?> a <?= View::escape((string) $instrument->maxScore) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Fecha aplicacion
            <input type="datetime-local" name="applied_at" required>
        </label>

        <label>
            Puntaje
            <input type="number" name="score" min="0" required placeholder="Ej: 8">
        </label>

        <label class="span-2">
            Interpretacion profesional / referencial
            <input name="interpretation" placeholder="Opcional. Si queda vacio, el sistema sugiere una interpretacion referencial.">
        </label>

        <label class="span-2">
            Nota profesional
            <textarea name="professional_notes" rows="6" placeholder="Contexto, observaciones, conducta durante aplicacion, criterios clinicos relevantes"></textarea>
        </label>

        <button class="button" type="submit">Guardar resultado</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
