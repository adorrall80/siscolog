<?php

use Core\View;

$detail ??= ['questions' => [], 'rules' => []];
$questionGroups ??= [];
$questions = $detail['questions'] ?? [];
$rules = $detail['rules'] ?? [];
$groupNames = array_map(static fn (array $group): string => (string) $group['name'], $questionGroups);

if ($questions === []) {
    $questions = [[
        'group_name' => 'General',
        'question_text' => '',
        'question_type' => 'likert',
        'min_value' => '',
        'max_value' => '',
        'options' => [
            ['label' => 'Nunca', 'score' => 0],
            ['label' => 'Varios dias', 'score' => 1],
            ['label' => 'Mas de la mitad de los dias', 'score' => 2],
            ['label' => 'Casi todos los dias', 'score' => 3],
        ],
    ]];
}

if ($rules === []) {
    $rules = [
        ['label' => 'Bajo', 'min_score' => 0, 'max_score' => '', 'interpretation' => ''],
    ];
}

$optionsText = static function (array $question): string {
    $options = $question['options'] ?? [];

    return implode("\n", array_map(
        static fn (array $option): string => (string) $option['label'] . '|' . (string) ($option['score'] ?? $option['value'] ?? 0),
        $options
    ));
};

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Instrumento psicometrico</p>
            <h1>Editar instrumento</h1>
            <p>Define cabecera, preguntas, tipos de respuesta, opciones y rangos. Al guardar se crea una nueva version vigente.</p>
        </div>
        <div class="actions-row">
            <a class="button small secondary" href="/instrumentos/<?= View::escape((string) $instrument->id) ?>/vista-previa">Vista previa</a>
            <a class="button small secondary" href="/instrumentos">Volver</a>
        </div>
    </div>

    <form class="form-grid" method="post" action="/instrumentos/<?= View::escape((string) $instrument->id) ?>/actualizar">
        <label>
            Codigo
            <input name="code" value="<?= View::escape($instrument->code) ?>" required>
        </label>

        <label>
            Nombre
            <input name="name" value="<?= View::escape($instrument->name) ?>" required>
        </label>

        <label class="span-2">
            Descripcion
            <input name="description" value="<?= View::escape((string) ($instrument->description ?? '')) ?>">
        </label>

        <label>
            Puntaje minimo
            <input type="number" name="min_score" value="<?= View::escape((string) $instrument->minScore) ?>" required>
        </label>

        <label>
            Puntaje maximo
            <input type="number" name="max_score" value="<?= View::escape((string) $instrument->maxScore) ?>" required>
        </label>

        <label>
            Corte seguimiento
            <input type="number" name="caution_cutoff" value="<?= View::escape($instrument->cautionCutoff === null ? '' : (string) $instrument->cautionCutoff) ?>">
        </label>

        <label>
            Corte critico
            <input type="number" name="critical_cutoff" value="<?= View::escape($instrument->criticalCutoff === null ? '' : (string) $instrument->criticalCutoff) ?>">
        </label>

        <label class="switch-field">
            Vigencia
            <input type="hidden" name="is_active" value="0">
            <input class="sr-only" type="checkbox" name="is_active" value="1" <?= $instrument->isActive ? 'checked' : '' ?>>
            <span class="switch-action <?= $instrument->isActive ? 'is-on' : 'is-off' ?>">
                <span><?= $instrument->isActive ? 'Vigente' : 'No vigente' ?></span><i></i>
            </span>
        </label>

        <section class="info-card span-2">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Preguntas</p>
                    <h2>Constructor de escala</h2>
                    <p>Define grupo, tipo de respuesta y opciones cuando corresponda. Opciones: <strong>Texto|puntaje</strong>.</p>
                </div>
                <button class="button small secondary" type="button" data-preview-instrument>Ver prueba</button>
            </div>

            <div class="builder-list" data-question-builder>
                <?php foreach ($questions as $index => $question): ?>
                    <?php
                    $selectedGroup = (string) ($question['group_name'] ?? 'General');
                    $questionType = (string) ($question['question_type'] ?? 'likert');
                    ?>
                    <article class="builder-row scale-builder-row" data-question-row>
                        <label>
                            Grupo
                            <select name="items[<?= (int) $index ?>][group_name]">
                                <?php if ($selectedGroup !== '' && !in_array($selectedGroup, $groupNames, true)): ?>
                                    <option value="<?= View::escape($selectedGroup) ?>" selected><?= View::escape($selectedGroup) ?></option>
                                <?php endif; ?>
                                <option value="General" <?= $selectedGroup === 'General' || $selectedGroup === '' ? 'selected' : '' ?>>General</option>
                                <?php foreach ($questionGroups as $group): ?>
                                    <option value="<?= View::escape((string) $group['name']) ?>" <?= $selectedGroup === (string) $group['name'] ? 'selected' : '' ?>>
                                        <?= View::escape((string) $group['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Pregunta
                            <input name="items[<?= (int) $index ?>][question]" value="<?= View::escape((string) ($question['question_text'] ?? '')) ?>" placeholder="Texto de la pregunta" required>
                        </label>

                        <label>
                            Tipo de respuesta
                            <select name="items[<?= (int) $index ?>][question_type]" data-question-type>
                                <?php foreach (['likert' => 'Radio / Likert', 'yes_no' => 'Si / No', 'select' => 'Listado', 'text_score' => 'Texto + valor', 'number' => 'Numero'] as $value => $label): ?>
                                    <option value="<?= View::escape($value) ?>" <?= $questionType === $value ? 'selected' : '' ?>><?= View::escape($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="value-limit-wrapper">
                            Min valor
                            <input class="value-limit-field" type="number" name="items[<?= (int) $index ?>][min_value]" value="<?= View::escape((string) ($question['min_value'] ?? '')) ?>" placeholder="Ej: 0">
                        </label>

                        <label class="value-limit-wrapper">
                            Max valor
                            <input class="value-limit-field" type="number" name="items[<?= (int) $index ?>][max_value]" value="<?= View::escape((string) ($question['max_value'] ?? '')) ?>" placeholder="Ej: 10">
                        </label>

                        <label class="options-wrapper">
                            Opciones
                            <textarea class="options-field" name="items[<?= (int) $index ?>][options]" rows="4" placeholder="Texto|puntaje"><?= View::escape($optionsText($question)) ?></textarea>
                        </label>

                        <p class="field-help question-type-help"></p>
                        <button class="button small secondary" type="button" data-remove-row>Quitar</button>
                    </article>
                <?php endforeach; ?>
            </div>

            <button class="button small secondary" type="button" data-add-question>Agregar pregunta</button>
        </section>

        <section class="info-card span-2">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Interpretacion</p>
                    <h2>Niveles por puntaje</h2>
                    <p>Define rangos del puntaje total y el texto que se mostrara al profesional.</p>
                </div>
            </div>

            <div class="builder-list" data-level-builder>
                <?php foreach ($rules as $index => $rule): ?>
                    <article class="builder-row level-builder-row">
                        <label>
                            Nivel
                            <input name="levels[<?= (int) $index ?>][label]" value="<?= View::escape((string) $rule['label']) ?>" placeholder="Ej: Leve" required>
                        </label>
                        <label>
                            Min
                            <input type="number" name="levels[<?= (int) $index ?>][min_score]" value="<?= View::escape((string) ($rule['min_score'] ?? 0)) ?>" required>
                        </label>
                        <label>
                            Max
                            <input type="number" name="levels[<?= (int) $index ?>][max_score]" value="<?= View::escape($rule['max_score'] === null ? '' : (string) $rule['max_score']) ?>">
                        </label>
                        <label>
                            Texto interpretativo
                            <textarea name="levels[<?= (int) $index ?>][description]" rows="3" placeholder="Interpretacion"><?= View::escape((string) ($rule['interpretation'] ?? '')) ?></textarea>
                        </label>
                        <button class="button small secondary" type="button" data-remove-row>Quitar</button>
                    </article>
                <?php endforeach; ?>
            </div>

            <button class="button small secondary" type="button" data-add-level>Agregar nivel</button>
        </section>

        <button class="button" type="submit">Guardar instrumento</button>
    </form>
</section>

<div class="modal-backdrop" data-instrument-preview-modal hidden>
    <section class="modal-panel" role="dialog" aria-modal="true">
        <div class="modal-head">
            <div>
                <h2>Prueba del instrumento</h2>
                <p data-preview-subtitle></p>
            </div>
            <button class="button small secondary" type="button" data-close-preview>Cerrar</button>
        </div>
        <div data-preview-content></div>
    </section>
</div>

<script>
(() => {
    let questionIndex = <?= count($questions) ?>;
    let levelIndex = <?= count($rules) ?>;
    const defaultOptions = "Nunca|0\nVarios dias|1\nMas de la mitad de los dias|2\nCasi todos los dias|3";
    const groupNames = <?= json_encode(array_values(array_unique(array_merge(['General'], $groupNames))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const questionBuilder = document.querySelector('[data-question-builder]');
    const levelBuilder = document.querySelector('[data-level-builder]');
    const previewModal = document.querySelector('[data-instrument-preview-modal]');
    const previewContent = document.querySelector('[data-preview-content]');
    const previewSubtitle = document.querySelector('[data-preview-subtitle]');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function groupSelect(index) {
        return `
            <select name="items[${index}][group_name]">
                ${groupNames.map((name) => `<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('')}
            </select>
        `;
    }

    function syncQuestionRow(row) {
        const type = row.querySelector('[data-question-type]')?.value || 'likert';
        const optionsWrapper = row.querySelector('.options-wrapper');
        const optionsField = row.querySelector('.options-field');
        const limitWrappers = row.querySelectorAll('.value-limit-wrapper');
        const limitFields = row.querySelectorAll('.value-limit-field');
        const help = row.querySelector('.question-type-help');
        const usesOptions = ['likert', 'select'].includes(type);
        const usesLimits = ['text_score', 'number'].includes(type);

        if (optionsWrapper) optionsWrapper.hidden = !usesOptions;
        if (optionsField) optionsField.disabled = !usesOptions;
        limitWrappers.forEach((wrapper) => wrapper.hidden = !usesLimits);
        limitFields.forEach((field) => field.disabled = !usesLimits);

        if (!help) return;
        if (type === 'yes_no') help.textContent = 'Usa automaticamente No = 0 y Si = 1.';
        if (type === 'text_score') help.textContent = 'Permite escribir texto libre y asignar un valor dentro del rango.';
        if (type === 'number') help.textContent = 'Permite ingresar solo un numero dentro del rango.';
        if (['likert', 'select'].includes(type)) help.textContent = 'Configura una opcion por linea usando Texto|puntaje.';
    }

    function syncAllRows() {
        questionBuilder.querySelectorAll('[data-question-row]').forEach(syncQuestionRow);
    }

    function removeRow(button) {
        const container = button.closest('.builder-list');
        const row = button.closest('.builder-row');
        if (!container || !row) return;

        if (container.querySelectorAll('.builder-row').length > 1) {
            row.remove();
        }
    }

    document.querySelector('[data-add-question]')?.addEventListener('click', () => {
        questionBuilder.insertAdjacentHTML('beforeend', `
            <article class="builder-row scale-builder-row" data-question-row>
                <label>Grupo${groupSelect(questionIndex)}</label>
                <label>Pregunta<input name="items[${questionIndex}][question]" placeholder="Texto de la pregunta" required></label>
                <label>Tipo de respuesta
                    <select name="items[${questionIndex}][question_type]" data-question-type>
                        <option value="likert">Radio / Likert</option>
                        <option value="yes_no">Si / No</option>
                        <option value="select">Listado</option>
                        <option value="text_score">Texto + valor</option>
                        <option value="number">Numero</option>
                    </select>
                </label>
                <label class="value-limit-wrapper">Min valor<input class="value-limit-field" type="number" name="items[${questionIndex}][min_value]" placeholder="Ej: 0"></label>
                <label class="value-limit-wrapper">Max valor<input class="value-limit-field" type="number" name="items[${questionIndex}][max_value]" placeholder="Ej: 10"></label>
                <label class="options-wrapper">Opciones<textarea class="options-field" name="items[${questionIndex}][options]" rows="4" placeholder="Texto|puntaje">${defaultOptions}</textarea></label>
                <p class="field-help question-type-help"></p>
                <button class="button small secondary" type="button" data-remove-row>Quitar</button>
            </article>
        `);
        questionIndex++;
        syncAllRows();
    });

    document.querySelector('[data-add-level]')?.addEventListener('click', () => {
        levelBuilder.insertAdjacentHTML('beforeend', `
            <article class="builder-row level-builder-row">
                <label>Nivel<input name="levels[${levelIndex}][label]" placeholder="Ej: Leve" required></label>
                <label>Min<input type="number" name="levels[${levelIndex}][min_score]" placeholder="0" required></label>
                <label>Max<input type="number" name="levels[${levelIndex}][max_score]" placeholder="Sin maximo"></label>
                <label>Texto interpretativo<textarea name="levels[${levelIndex}][description]" rows="3" placeholder="Interpretacion"></textarea></label>
                <button class="button small secondary" type="button" data-remove-row>Quitar</button>
            </article>
        `);
        levelIndex++;
    });

    document.addEventListener('click', (event) => {
        if (event.target.matches('[data-remove-row]')) {
            removeRow(event.target);
        }
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-question-type]')) {
            syncQuestionRow(event.target.closest('[data-question-row]'));
        }
    });

    function optionsFromText(text, type) {
        if (type === 'yes_no') return [{ label: 'No', score: 0 }, { label: 'Si', score: 1 }];

        const options = String(text || '')
            .split(/\r?\n/)
            .map((line) => line.trim())
            .filter(Boolean)
            .map((line) => {
                const [label, score = '0'] = line.split('|');
                return { label: label.trim(), score: Number(score.trim() || 0) };
            });

        return options.length ? options : [
            { label: 'Nunca', score: 0 },
            { label: 'Varios dias', score: 1 },
            { label: 'Mas de la mitad de los dias', score: 2 },
            { label: 'Casi todos los dias', score: 3 },
        ];
    }

    function previewQuestion(row, index) {
        const question = row.querySelector('input[name$="[question]"]')?.value || `Pregunta ${index + 1}`;
        const type = row.querySelector('[data-question-type]')?.value || 'likert';
        const optionsText = row.querySelector('.options-field')?.value || '';
        const min = row.querySelector('input[name$="[min_value]"]')?.value || '0';
        const max = row.querySelector('input[name$="[max_value]"]')?.value || '';
        const options = optionsFromText(optionsText, type);

        if (type === 'text_score') {
            return `<article class="question-card"><h2>${escapeHtml(question)}</h2><input placeholder="Respuesta libre"><input class="preview-score-control" type="number" min="${escapeHtml(min)}" ${max ? `max="${escapeHtml(max)}"` : ''} value="${escapeHtml(min)}"></article>`;
        }

        if (type === 'number') {
            return `<article class="question-card"><h2>${escapeHtml(question)}</h2><input class="preview-score-control" type="number" min="${escapeHtml(min)}" ${max ? `max="${escapeHtml(max)}"` : ''} value="${escapeHtml(min)}"></article>`;
        }

        if (type === 'select') {
            return `<article class="question-card"><h2>${escapeHtml(question)}</h2><select class="preview-score-control"><option value="">Seleccione</option>${options.map((option) => `<option value="${option.score}">${escapeHtml(option.label)} (${option.score})</option>`).join('')}</select></article>`;
        }

        return `<article class="question-card"><h2>${escapeHtml(question)}</h2><div class="option-grid">${options.map((option) => `<label class="option-card"><input class="preview-score-control" type="radio" name="preview_${index}" value="${option.score}"><span><strong>${escapeHtml(option.label)}</strong><small>Puntaje: ${option.score}</small></span></label>`).join('')}</div></article>`;
    }

    function updatePreviewTotal() {
        let total = 0;
        previewContent.querySelectorAll('.preview-score-control').forEach((control) => {
            if (control.type === 'radio' && !control.checked) return;
            if (control.tagName === 'SELECT' && control.value === '') return;
            total += Number(control.value || 0);
        });
        const target = previewContent.querySelector('[data-preview-total]');
        if (target) target.textContent = String(total);
    }

    document.querySelector('[data-preview-instrument]')?.addEventListener('click', () => {
        const name = document.querySelector('input[name="name"]')?.value || 'Instrumento';
        previewSubtitle.textContent = name;
        previewContent.innerHTML = `
            <div class="question-list">
                ${[...questionBuilder.querySelectorAll('[data-question-row]')].map(previewQuestion).join('')}
            </div>
            <div class="questionnaire-total"><strong>Total prueba: <span data-preview-total>0</span></strong></div>
        `;
        previewContent.querySelectorAll('.preview-score-control').forEach((control) => {
            control.addEventListener('input', updatePreviewTotal);
            control.addEventListener('change', updatePreviewTotal);
        });
        updatePreviewTotal();
        previewModal.hidden = false;
    });

    document.querySelector('[data-close-preview]')?.addEventListener('click', () => {
        previewModal.hidden = true;
    });

    previewModal?.addEventListener('click', (event) => {
        if (event.target === previewModal) {
            previewModal.hidden = true;
        }
    });

    syncAllRows();
})();
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';

