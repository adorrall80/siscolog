<?php

use Core\View;

$item ??= null;
?>

<fieldset>
    <legend>Datos principales</legend>

    <input type="hidden" name="code" value="<?= View::escape($item?->code ?? '') ?>">

    <label>
        Nombre
        <input name="name" required value="<?= View::escape($item?->name) ?>" placeholder="ej: Activo">
    </label>

    <label>
        Color
        <select name="color">
            <?php foreach (['green' => 'Verde', 'amber' => 'Amarillo', 'blue' => 'Azul', 'red' => 'Rojo', 'gray' => 'Gris'] as $value => $label): ?>
                <option value="<?= View::escape($value) ?>" <?= ($item?->color ?? 'green') === $value ? 'selected' : '' ?>>
                    <?= View::escape($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <input type="hidden" name="sort_order" value="<?= View::escape((string) ($item?->sortOrder ?? 0)) ?>">
</fieldset>

<fieldset class="single-column">
    <legend>Configuracion</legend>

    <label>
        Descripcion
        <textarea name="description" rows="4"><?= View::escape($item?->description) ?></textarea>
    </label>

    <div class="switch-field">
        <span>Vigencia</span>
        <input type="hidden" name="is_active" value="0">
        <label class="toggle-switch">
            <input type="checkbox" name="is_active" value="1" <?= ($item?->isActive ?? true) ? 'checked' : '' ?>>
            <span></span>
            <strong><?= ($item?->isActive ?? true) ? 'Vigente' : 'No vigente' ?></strong>
        </label>
    </div>
</fieldset>
