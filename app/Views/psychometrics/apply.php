<?php

use Core\View;

$detail ??= [];
$instrument = $detail['instrument'] ?? [];
$version = $detail['version'] ?? null;
$questions = $detail['questions'] ?? [];
$rules = $detail['rules'] ?? [];

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
            <p class="eyebrow">Aplicacion guiada</p>
            <h1><?= View::escape((string) ($instrument['name'] ?? 'Instrumento')) ?></h1>
            <p>Paciente: <?= View::escape($patient->fullName) ?>. Se guardara detalle de preguntas, respuestas, puntaje y nota profesional.</p>
        </div>
        <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/create">Volver</a>
    </div>

    <div class="clinical-alert" role="status">
        <span class="clinical-alert-icon" aria-hidden="true">i</span>
        <div>
            <strong>Instrumento supervisado</strong>
            <p><?= View::escape((string) ($instrument['cautions'] ?? 'El resultado no equivale a diagnostico y debe interpretarse con criterio profesional.')) ?></p>
        </div>
    </div>

    <?php if ($version === null || $questions === []): ?>
        <section class="info-card">
            <p>Este instrumento aun no tiene preguntas versionadas. Puedes registrarlo como resultado manual por ahora.</p>
            <a class="button small" href="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/create">Registrar manual</a>
        </section>
    <?php else: ?>
        <form class="form-grid" method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/<?= View::escape((string) $instrument['id']) ?>/aplicar">
            <label>
                Fecha aplicacion
                <input type="datetime-local" name="applied_at" required>
            </label>

            <label>
                Version
                <input value="<?= View::escape((string) $version['version_label']) ?>" readonly>
            </label>

            <section class="info-card span-2">
                <p class="eyebrow">Instrucciones</p>
                <p><?= View::escape((string) ($instrument['instructions'] ?? 'Responde cada pregunta segun corresponda.')) ?></p>
            </section>

            <section class="info-card span-2">
                <p class="eyebrow">Preguntas</p>
                <?php foreach ($questions as $question): ?>
                    <article class="question-card">
                        <div>
                            <span class="tag tone-blue"><?= View::escape((string) ($question['group_name'] ?? 'General')) ?></span>
                            <h2><?= View::escape((string) $question['question_text']) ?></h2>
                        </div>

                        <?php $questionType = (string) ($question['question_type'] ?? 'likert'); ?>
                        <?php if ($questionType === 'text_score'): ?>
                            <div class="question-free-score">
                                <label>
                                    Respuesta
                                    <input name="answers[<?= View::escape((string) $question['id']) ?>][text]" placeholder="Respuesta u observacion" required>
                                </label>
                                <label>
                                    Valor
                                    <input
                                        class="score-control"
                                        type="number"
                                        name="answers[<?= View::escape((string) $question['id']) ?>][score]"
                                        min="<?= View::escape($question['min_value'] === null ? '0' : (string) $question['min_value']) ?>"
                                        <?= $question['max_value'] === null ? '' : 'max="' . View::escape((string) $question['max_value']) . '"' ?>
                                        value="<?= View::escape($question['min_value'] === null ? '0' : (string) $question['min_value']) ?>"
                                        required
                                    >
                                </label>
                            </div>
                        <?php elseif ($questionType === 'number'): ?>
                            <label>
                                Valor
                                <input
                                    class="score-control"
                                    type="number"
                                    name="answers[<?= View::escape((string) $question['id']) ?>]"
                                    min="<?= View::escape($question['min_value'] === null ? '0' : (string) $question['min_value']) ?>"
                                    <?= $question['max_value'] === null ? '' : 'max="' . View::escape((string) $question['max_value']) . '"' ?>
                                    value="<?= View::escape($question['min_value'] === null ? '0' : (string) $question['min_value']) ?>"
                                    required
                                >
                            </label>
                        <?php elseif ($questionType === 'select'): ?>
                            <label>
                                Seleccione respuesta
                                <select class="score-control" name="answers[<?= View::escape((string) $question['id']) ?>]" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach (($question['options'] ?? []) as $option): ?>
                                        <?php $optionScore = $option['score'] === null ? 0 : (int) $option['score']; ?>
                                        <option
                                            value="<?= View::escape((string) $optionScore) ?>"
                                            data-option-id="<?= View::escape((string) $option['id']) ?>"
                                            data-score="<?= View::escape((string) $optionScore) ?>"
                                        >
                                            <?= View::escape((string) $option['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php elseif (($question['options'] ?? []) === []): ?>
                            <p>Esta pregunta no tiene opciones configuradas todavia.</p>
                        <?php else: ?>
                            <div class="option-grid">
                                <?php foreach ($question['options'] as $option): ?>
                                    <label class="option-card">
                                        <input
                                            class="score-control"
                                            type="radio"
                                            name="answers[<?= View::escape((string) $question['id']) ?>]"
                                            value="<?= View::escape((string) ($option['score'] === null ? 0 : (int) $option['score'])) ?>"
                                            data-option-id="<?= View::escape((string) $option['id']) ?>"
                                            data-score="<?= View::escape((string) ($option['score'] === null ? 0 : (int) $option['score'])) ?>"
                                            required
                                        >
                                        <span>
                                            <strong><?= View::escape((string) $option['label']) ?></strong>
                                            <small>Valor: <?= View::escape($option['score'] === null ? '0' : (string) $option['score']) ?></small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="questionnaire-total span-2" aria-live="polite">
                <strong>Total: <span data-application-total>0</span></strong>
                <button class="button small secondary" type="button" data-clear-application>Limpiar respuestas</button>
            </section>

            <section class="info-card span-2">
                <p class="eyebrow">Rangos configurados</p>
                <?php if ($rules === []): ?>
                    <p>Sin rangos configurados.</p>
                <?php else: ?>
                    <div class="subtype-chip-list">
                        <?php foreach ($rules as $rule): ?>
                            <span class="tag tone-green">
                                <?= View::escape((string) $rule['label']) ?>:
                                <?= View::escape($rule['min_score'] === null ? 'sin minimo' : (string) $rule['min_score']) ?>
                                -
                                <?= View::escape($rule['max_score'] === null ? 'sin maximo' : (string) $rule['max_score']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <label class="span-2">
                Nota profesional
                <textarea name="professional_notes" rows="6" placeholder="Observaciones durante la aplicacion, contexto, conducta, criterios clinicos relevantes"></textarea>
            </label>

            <button class="button" type="submit">Guardar aplicacion</button>
        </form>
    <?php endif; ?>
</section>
<script>
(() => {
    const totalLabel = document.querySelector('[data-application-total]');
    const clearButton = document.querySelector('[data-clear-application]');

    function selectedScore(control) {
        if (control.type === 'radio') {
            return control.checked ? Number(control.dataset.score || control.value || 0) : 0;
        }

        if (control.tagName === 'SELECT') {
            const option = control.options[control.selectedIndex];
            return option && option.value !== '' ? Number(option.dataset.score || option.value || 0) : 0;
        }

        if (control.type === 'number') {
            clampNumber(control);
            return Number(control.value || 0);
        }

        return Number(control.value || 0);
    }

    function clampNumber(control) {
        if (control.value === '') {
            return;
        }

        let value = Number(control.value);
        const min = control.min === '' ? null : Number(control.min);
        const max = control.max === '' ? null : Number(control.max);

        if (Number.isNaN(value)) {
            control.value = min === null ? '0' : String(min);
            return;
        }

        if (min !== null && value < min) {
            value = min;
        }

        if (max !== null && value > max) {
            value = max;
        }

        control.value = String(value);
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.score-control').forEach((control) => {
            total += selectedScore(control);
        });

        if (totalLabel) {
            totalLabel.textContent = String(total);
        }
    }

    document.querySelectorAll('.score-control').forEach((control) => {
        control.addEventListener('input', calculateTotal);
        control.addEventListener('change', calculateTotal);
    });

    clearButton?.addEventListener('click', () => {
        document.querySelectorAll('.score-control').forEach((control) => {
            if (control.type === 'radio') {
                control.checked = false;
                return;
            }

            if (control.tagName === 'SELECT') {
                control.value = '';
                return;
            }

            if (control.type === 'number') {
                control.value = control.min || '0';
                return;
            }

            control.value = '';
        });

        document.querySelectorAll('.question-free-score input:not(.score-control)').forEach((control) => {
            control.value = '';
        });

        calculateTotal();
    });

    calculateTotal();
})();
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
