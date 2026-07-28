<?php

use Core\View;

ob_start();
?>
<?php
$activeTable = $table;
$tableSlug ??= $table;
$contextQuery ??= '';
require __DIR__ . '/_menu.php';
?>

<section class="action-toolbar">
    <strong>Editar mantenedor</strong>
    <span class="toolbar-code"><?= View::escape($tableLabel) ?></span>
    <a class="button small secondary" href="/maintainers/<?= View::escape($tableSlug) ?><?= View::escape($contextQuery) ?>">Volver</a>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow"><?= View::escape($tableLabel) ?></p>
            <h1>Editar valor</h1>
            <p>Actualiza el valor mantenedor usado por selects, estados y validaciones.</p>
        </div>
    </div>

    <form method="post" action="/maintainers/<?= View::escape($tableSlug) ?>/<?= View::escape((string) $item->id) ?>/update<?= View::escape($contextQuery) ?>" class="form-grid">
        <?php require __DIR__ . '/form.php'; ?>

        <button class="button" type="submit">Actualizar valor</button>
    </form>
</section>

<?php if ($table === \App\Services\MaintainerService::SESSION_TOPIC_TYPES): ?>
    <section class="panel">
        <div class="panel-header compact">
            <div>
                <p class="eyebrow">Subtipos</p>
                <h2>Subtipos de <?= View::escape($item->name) ?></h2>
                <p>Los verdes ya pertenecen a este tipo. Los amarillos existen en el sistema, pero todavia no estan asociados.</p>
            </div>
            <span class="state tone-blue"><?= View::escape((string) count($topicSubtypes ?? [])) ?> subtipos</span>
        </div>

        <div class="topic-subtype-legend">
            <span class="tag tone-green">Verde: asociado a <?= View::escape($item->name) ?></span>
            <span class="tag tone-amber">Amarillo: disponible para asociar</span>
        </div>

        <section class="subtype-associated-panel" data-associated-subtypes>
            <h3>Subtemas asociados</h3>
            <div class="subtype-chip-list">
                <?php if (($topicSubtypes ?? []) === []): ?>
                    <span class="tag tone-gray" data-empty-associated>Sin subtemas asociados</span>
                <?php endif; ?>
                <?php foreach (($topicSubtypes ?? []) as $subtype): ?>
                    <span
                        class="subtype-chip <?= $subtype->relationIsActive ? 'is-associated' : 'is-disabled' ?>"
                        data-associated-subtype
                        data-relation-id="<?= View::escape((string) $subtype->relationId) ?>"
                        data-subtype-id="<?= View::escape((string) $subtype->id) ?>"
                    >
                        <?= View::escape($subtype->name) ?>
                        <span class="tag tone-<?= $subtype->relationIsPublic ? 'blue' : 'green' ?>">
                            <?= $subtype->relationIsPublic ? 'General' : 'Privada' ?>
                        </span>
                        <?php if ($subtype->relationCreatedByUserName): ?>
                            <small>Creada por <?= View::escape($subtype->relationCreatedByUserName) ?></small>
                        <?php endif; ?>
                        <?php if ($subtype->relationIsPublic): ?>
                            <a
                                class="action-link ghost"
                                href="/maintainers/tipos-tema-sesion/<?= View::escape((string) $item->id) ?>/subtipos/<?= View::escape((string) $subtype->relationId) ?>/privatizar"
                            >Hacer privada</a>
                        <?php else: ?>
                            <a
                                class="action-link ghost"
                                href="/maintainers/tipos-tema-sesion/<?= View::escape((string) $item->id) ?>/subtipos/<?= View::escape((string) $subtype->relationId) ?>/publicar"
                            >Publicar</a>
                        <?php endif; ?>
                        <button
                            type="button"
                            class="chip-remove"
                            data-remove-subtype-relation
                            data-remove-url="/maintainers/tipos-tema-sesion/<?= View::escape((string) $item->id) ?>/subtipos/<?= View::escape((string) $subtype->relationId) ?>/quitar"
                            aria-label="Quitar relacion <?= View::escape($subtype->name) ?>"
                        >x</button>
                    </span>
                <?php endforeach; ?>
            </div>
        </section>

        <form method="post" action="/maintainers/tipos-tema-sesion/<?= View::escape((string) $item->id) ?>/subtipos" class="form-grid" data-topic-subtype-form>
            <input type="hidden" name="async" value="1">
            <fieldset class="single-column">
                <legend>Asociar subtipo existente</legend>

                <label>
                    Buscar, si no esta se agrega
                    <input name="subtype_search" data-subtype-search placeholder="Escribe para filtrar, ej: ansiedad, duelo, regulacion">
                </label>

                <input type="hidden" name="existing_subtype_id" value="0" data-subtype-selected-id>

                <div class="subtype-search-list" data-subtype-list>
                    <?php foreach (($availableTopicSubtypes ?? []) as $availableSubtype): ?>
                        <button
                            type="button"
                            class="subtype-option is-available"
                            data-subtype-option
                            data-subtype-id="<?= View::escape((string) $availableSubtype->id) ?>"
                            data-subtype-name="<?= View::escape(strtolower($availableSubtype->name)) ?>"
                            data-subtype-label="<?= View::escape($availableSubtype->name) ?>"
                        >
                            <?= View::escape($availableSubtype->name) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <p class="form-help" data-subtype-selected-label>Busca y selecciona uno existente. Si no esta, se agregara como nuevo subtipo.</p>
            </fieldset>

            <input type="hidden" name="subtype_name" value="">
            <input type="hidden" name="subtype_sort_order" value="0">
            <input type="hidden" name="subtype_code" value="">
            <input type="hidden" name="subtype_color" value="">
            <input type="hidden" name="subtype_description" value="">

            <button class="button small" type="submit">Agregar subtipo</button>
        </form>
    </section>
<?php endif; ?>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
