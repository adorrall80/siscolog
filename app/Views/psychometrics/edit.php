<?php

use Core\View;

$instruments ??= [];
$applicationDetail ??= null;
$appliedAt = str_replace(' ', 'T', substr((string) $result->appliedAt, 0, 16));

ob_start();
?>
<nav class="record-tabs" aria-label="Secciones de ficha paciente">
    <a href="/patients/<?= View::escape((string) $patient->id) ?>">Ficha paciente</a>
    <a class="active" href="/patients/<?= View::escape((string) $patient->id) ?>#instrumentos">Instrumentos</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/create">Sesiones</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/consents/create">Consentimientos</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/ai-history">Historial IA</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/vinculos">Vinculos con paciente</a>
</nav>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Instrumentos psicometricos</p>
            <h1>Editar resultado</h1>
            <p>Paciente: <?= View::escape($patient->fullName) ?>. Se actualiza el resultado vigente de la ficha, sin duplicarlo.</p>
        </div>
        <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>#instrumentos">Volver a ficha</a>
    </div>

    <form class="form-grid" method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/<?= View::escape((string) $result->id) ?>/actualizar">
        <label>
            Instrumento
            <select name="instrument_id" required>
                <option value="">Seleccione instrumento</option>
                <?php foreach ($instruments as $instrument): ?>
                    <option
                        value="<?= View::escape((string) $instrument->id) ?>"
                        data-min-score="<?= View::escape((string) $instrument->minScore) ?>"
                        data-max-score="<?= View::escape((string) $instrument->maxScore) ?>"
                        <?= $result->instrumentId === $instrument->id ? 'selected' : '' ?>
                    >
                        <?= View::escape($instrument->name) ?> (<?= View::escape((string) $instrument->minScore) ?> a <?= View::escape((string) $instrument->maxScore) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Fecha aplicacion
            <input type="datetime-local" name="applied_at" value="<?= View::escape($appliedAt) ?>" required>
        </label>

        <label>
            Puntaje
            <input type="number" name="score" min="0" value="<?= View::escape((string) $result->score) ?>" required>
        </label>

        <label class="span-2">
            Interpretacion profesional / referencial
            <input name="interpretation" value="<?= View::escape((string) ($result->interpretation ?? '')) ?>">
        </label>

        <label class="span-2">
            Nota profesional
            <textarea name="professional_notes" rows="6"><?= View::escape((string) ($result->professionalNotes ?? '')) ?></textarea>
        </label>

        <button class="button" type="submit">Guardar cambios</button>
    </form>

    <section class="info-card span-2">
        <div class="panel-header compact">
            <div>
                <p class="eyebrow">Detalle de aplicacion guiada</p>
                <h2>Respuestas registradas</h2>
                <p>Este detalle viene de la aplicacion pregunta por pregunta. Si el resultado fue ingresado manualmente, no tendra respuestas asociadas.</p>
            </div>
        </div>

        <?php if ($applicationDetail === null): ?>
            <p>No hay detalle de respuestas para este resultado. Probablemente fue registrado como puntaje manual.</p>
        <?php else: ?>
            <div class="form-readonly-grid">
                <div class="readonly-field">
                    <span>Version aplicada</span>
                    <strong><?= View::escape((string) ($applicationDetail['version_label'] ?? 'Sin version')) ?></strong>
                </div>
                <div class="readonly-field">
                    <span>Total calculado</span>
                    <strong><?= View::escape((string) ($applicationDetail['total_score'] ?? $result->score)) ?></strong>
                </div>
                <div class="readonly-field">
                    <span>Resultado</span>
                    <strong><?= View::escape((string) ($applicationDetail['result_label'] ?? 'Sin rango')) ?></strong>
                </div>
                <div class="readonly-field">
                    <span>Profesional</span>
                    <strong><?= View::escape((string) ($applicationDetail['professional_name'] ?? $result->professionalName ?? 'Sin profesional')) ?></strong>
                </div>
            </div>

            <?php if (!empty($applicationDetail['interpretation'])): ?>
                <div class="clinical-alert" role="status">
                    <span class="clinical-alert-icon" aria-hidden="true">i</span>
                    <div>
                        <strong>Interpretacion del rango</strong>
                        <p><?= View::escape((string) $applicationDetail['interpretation']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($applicationDetail['answers'])): ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pregunta</th>
                                <th>Respuesta</th>
                                <th>Puntaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applicationDetail['answers'] as $answer): ?>
                                <tr>
                                    <td><?= View::escape((string) ($answer['item_order'] ?? '')) ?></td>
                                    <td><?= View::escape((string) ($answer['question_snapshot'] ?? '')) ?></td>
                                    <td>
                                        <strong><?= View::escape((string) ($answer['answer_label'] ?? '')) ?></strong>
                                        <?php if (($answer['answer_value'] ?? '') !== ($answer['answer_label'] ?? '')): ?>
                                            <small>Valor: <?= View::escape((string) ($answer['answer_value'] ?? '')) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="state tone-blue"><?= View::escape((string) ($answer['answer_score'] ?? 0)) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>La aplicacion existe, pero no tiene respuestas registradas.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
