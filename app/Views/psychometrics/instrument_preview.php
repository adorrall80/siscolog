<?php

use Core\View;

$detail ??= [];
$instrument = $detail['instrument'] ?? [];
$version = $detail['version'] ?? null;
$questions = $detail['questions'] ?? [];
$rules = $detail['rules'] ?? [];

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Instrumento psicometrico</p>
            <h1>Vista previa</h1>
            <p>Revisa estructura, preguntas, opciones y rangos antes de aplicar el instrumento a pacientes.</p>
        </div>
        <div class="actions-row">
            <a class="button small secondary" href="/instrumentos">Volver</a>
            <a class="button small secondary" href="/instrumentos/<?= View::escape((string) ($instrument['id'] ?? '')) ?>/edit">Editar</a>
        </div>
    </div>

    <div class="card-grid compact">
        <article class="metric-card">
            <span>Instrumento</span>
            <strong><?= View::escape((string) ($instrument['name'] ?? 'No registrado')) ?></strong>
            <small><?= View::escape((string) ($instrument['code'] ?? '')) ?></small>
        </article>
        <article class="metric-card">
            <span>Categoria / tipo</span>
            <strong><?= View::escape((string) ($instrument['category_name'] ?? 'Sin categoria')) ?></strong>
            <small><?= View::escape((string) ($instrument['type_name'] ?? 'Sin tipo')) ?></small>
        </article>
        <article class="metric-card">
            <span>Rango</span>
            <strong><?= View::escape((string) ($instrument['min_score'] ?? 0)) ?> a <?= View::escape((string) ($instrument['max_score'] ?? 0)) ?></strong>
            <small>Puntaje total calculado</small>
        </article>
        <article class="metric-card">
            <span>Version</span>
            <strong><?= $version ? View::escape((string) $version['version_label']) : 'Sin version' ?></strong>
            <small><?= $version ? View::escape((string) $version['status']) : 'Pendiente de configurar' ?></small>
        </article>
    </div>

    <section class="info-card">
        <p class="eyebrow">Instrucciones</p>
        <p><?= View::escape((string) ($instrument['instructions'] ?? 'Sin instrucciones registradas.')) ?></p>
        <?php if (!empty($instrument['cautions'])): ?>
            <p><strong>Advertencia clinica:</strong> <?= View::escape((string) $instrument['cautions']) ?></p>
        <?php endif; ?>
        <?php if (!empty($instrument['frequency'])): ?>
            <p><strong>Frecuencia sugerida:</strong> <?= View::escape((string) $instrument['frequency']) ?></p>
        <?php endif; ?>
    </section>

    <section class="info-card">
        <p class="eyebrow">Preguntas</p>
        <?php if ($questions === []): ?>
            <p>Este instrumento aun no tiene preguntas versionadas. Puede seguir usandose como resultado manual resumido.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Grupo</th>
                            <th>Pregunta</th>
                            <th>Tipo</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td><?= View::escape((string) $question['item_order']) ?></td>
                                <td><?= View::escape((string) ($question['group_name'] ?? 'General')) ?></td>
                                <td>
                                    <strong><?= View::escape((string) ($question['label'] ?? $question['question_text'])) ?></strong>
                                    <small><?= View::escape((string) $question['question_text']) ?></small>
                                </td>
                                <td><?= View::escape((string) $question['question_type']) ?></td>
                                <td>
                                    <?php if (($question['options'] ?? []) === []): ?>
                                        <span class="tag tone-gray">Sin opciones</span>
                                    <?php else: ?>
                                        <div class="subtype-chip-list">
                                            <?php foreach ($question['options'] as $option): ?>
                                                <span class="tag tone-green">
                                                    <?= View::escape((string) $option['label']) ?>
                                                    <?php if ($option['score'] !== null): ?>
                                                        · <?= View::escape((string) $option['score']) ?>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="info-card">
        <p class="eyebrow">Interpretacion por rango</p>
        <?php if ($rules === []): ?>
            <p>Sin rangos configurados. La interpretacion quedara a criterio profesional o por cortes simples.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Rango</th>
                            <th>Etiqueta</th>
                            <th>Interpretacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $rule): ?>
                            <tr>
                                <td>
                                    <?= View::escape($rule['min_score'] === null ? 'Sin minimo' : (string) $rule['min_score']) ?>
                                    a
                                    <?= View::escape($rule['max_score'] === null ? 'Sin maximo' : (string) $rule['max_score']) ?>
                                </td>
                                <td><strong><?= View::escape((string) $rule['label']) ?></strong></td>
                                <td><?= View::escape((string) ($rule['interpretation'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';

