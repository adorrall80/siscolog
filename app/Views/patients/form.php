<?php

use Core\View;

$patient ??= null;
$contact = $patient?->emergencyContact;
$patientStatuses ??= [];
?>

<fieldset>
    <legend>Datos del paciente</legend>

    <label>
        Codigo interno
        <input name="code" value="<?= View::escape($patient?->code) ?>" required>
    </label>

    <label>
        Nombre completo
        <input name="full_name" value="<?= View::escape($patient?->fullName) ?>" required>
    </label>

    <label>
        Fecha de nacimiento
        <input type="date" name="birth_date" value="<?= View::escape($patient?->birthDate) ?>">
    </label>

    <label>
        Email
        <input type="email" name="email" value="<?= View::escape($patient?->email) ?>">
    </label>

    <label>
        Telefono
        <input name="phone" value="<?= View::escape($patient?->phone) ?>">
    </label>

    <?php if ($patient): ?>
        <label>
            Estado del caso
            <select name="status">
                <?php foreach ($patientStatuses as $status): ?>
                    <option value="<?= View::escape($status->code) ?>" <?= $patient->status === $status->code ? 'selected' : '' ?>>
                        <?= View::escape($status->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>
</fieldset>

<fieldset>
    <legend>Contacto de emergencia</legend>

    <label>
        Nombre contacto
        <input name="emergency_name" value="<?= View::escape($contact?->name) ?>">
    </label>

    <label>
        Relacion
        <input name="emergency_relationship" value="<?= View::escape($contact?->relationship) ?>">
    </label>

    <label>
        Telefono emergencia
        <input name="emergency_phone" value="<?= View::escape($contact?->phone) ?>">
    </label>

    <label>
        Email emergencia
        <input type="email" name="emergency_email" value="<?= View::escape($contact?->email) ?>">
    </label>
</fieldset>
