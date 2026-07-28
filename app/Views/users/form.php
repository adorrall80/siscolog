<?php

use Core\View;

$user ??= null;
$isEdit = $user !== null;
?>

<fieldset>
    <legend>Datos de acceso</legend>

    <label>
        Nombre
        <input name="name" required value="<?= View::escape($user?->name) ?>" placeholder="Ej: Profesional clinico">
    </label>

    <label>
        Email
        <input type="email" name="email" required value="<?= View::escape($user?->email) ?>" placeholder="usuario@siscolog.local">
    </label>

    <label>
        <?= $isEdit ? 'Nueva contrasena' : 'Contrasena' ?>
        <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="6" placeholder="<?= $isEdit ? 'Dejar vacio para no cambiar' : 'Minimo 6 caracteres' ?>">
    </label>

    <label>
        Rol
        <select name="role" required>
            <?php if (!$isEdit): ?>
                <option value="" selected disabled>Seleccione rol</option>
            <?php endif; ?>
            <?php foreach ($roles as $role): ?>
                <?php $selected = $isEdit ? $user->role === $role->code : false; ?>
                <option value="<?= View::escape($role->code) ?>" <?= $selected ? 'selected' : '' ?>>
                    <?= View::escape($role->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
</fieldset>

<fieldset>
    <legend>Datos profesionales de atencion</legend>

    <label>
        Profesion o rol de atencion
        <input name="profession" value="<?= View::escape($user?->profession) ?>" placeholder="Ej: Psicologo, terapeuta">
    </label>

    <label>
        Especialidad o area
        <input name="specialty" value="<?= View::escape($user?->specialty) ?>" placeholder="Ej: adultos, familia, infanto juvenil">
    </label>

    <label>
        Registro profesional
        <input name="professional_license" value="<?= View::escape($user?->professionalLicense) ?>" placeholder="N colegiado o registro">
    </label>

    <label>
        Telefono profesional
        <input name="phone" value="<?= View::escape($user?->phone) ?>" placeholder="+569...">
    </label>

    <label>
        Centro o consulta
        <input name="clinic_name" value="<?= View::escape($user?->clinicName) ?>" placeholder="Consulta particular o centro">
    </label>

    <label>
        Direccion o zona
        <input name="clinic_address" value="<?= View::escape($user?->clinicAddress) ?>" placeholder="Direccion, comuna u online">
    </label>

    <?php $selectedAttentionModalities = is_array($selectedAttentionModalities ?? null) ? $selectedAttentionModalities : []; ?>
    <div class="checkbox-group">
        <span>Modalidades de atencion</span>
        <div class="checkbox-grid">
            <?php foreach (($attentionModalities ?? []) as $modality): ?>
                <label class="check-card">
                    <input
                        type="checkbox"
                        name="attention_modalities[]"
                        value="<?= View::escape($modality->code) ?>"
                        <?= in_array($modality->code, $selectedAttentionModalities, true) ? 'checked' : '' ?>
                    >
                    <span><?= View::escape($modality->name) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <small>Opcional en administracion. Obligatorio cuando el profesional completa su perfil.</small>
    </div>

    <label>
        Resena profesional
        <textarea name="professional_bio" rows="3" placeholder="Breve descripcion"><?= View::escape($user?->professionalBio) ?></textarea>
    </label>
</fieldset>

<fieldset>
    <legend>Estado</legend>

    <label>
        Estado de usuario
        <select name="status" required>
            <?php if (!$isEdit): ?>
                <option value="" selected disabled>Seleccione estado</option>
            <?php endif; ?>
            <?php foreach ($statuses as $status): ?>
                <?php $selected = $isEdit ? $user->status === $status->code : false; ?>
                <option value="<?= View::escape($status->code) ?>" <?= $selected ? 'selected' : '' ?>>
                    <?= View::escape($status->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Activo
        <select name="is_active">
            <option value="1" <?= !$isEdit || $user->isActive ? 'selected' : '' ?>>Si</option>
            <option value="0" <?= $isEdit && !$user->isActive ? 'selected' : '' ?>>No</option>
        </select>
    </label>
</fieldset>
