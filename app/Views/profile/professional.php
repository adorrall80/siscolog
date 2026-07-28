<?php

use Core\View;

ob_start();
?>
<section class="panel">
    <div class="panel-header compact">
        <div>
            <p class="eyebrow">Perfil profesional</p>
            <h1>Completa tus datos de atencion</h1>
            <p>Estos datos identifican tu rol profesional o terapeutico dentro del sistema. Debes completarlos para continuar usando SisColog.</p>
        </div>
    </div>

    <form class="form-grid" method="post" action="/perfil/profesional/actualizar">
        <fieldset>
            <legend>Datos basicos</legend>
            <label>
                Nombre visible
                <input name="name" required value="<?= View::escape($user->name) ?>">
            </label>
            <label>
                Email
                <input type="email" value="<?= View::escape($user->email) ?>" readonly>
            </label>
        </fieldset>

        <fieldset>
            <legend>Datos profesionales de atencion</legend>
            <label>
                Profesion o rol de atencion
                <input name="profession" required value="<?= View::escape($user->profession) ?>" placeholder="Ej: Psicologo, terapeuta, coach clinico">
            </label>
            <label>
                Especialidad o area
                <input name="specialty" required value="<?= View::escape($user->specialty) ?>" placeholder="Ej: adultos, familia, infanto juvenil">
            </label>
            <label>
                Registro profesional
                <input name="professional_license" value="<?= View::escape($user->professionalLicense) ?>" placeholder="N colegiado, registro o identificador">
            </label>
            <label>
                Telefono profesional
                <input name="phone" required value="<?= View::escape($user->phone) ?>" placeholder="+569...">
            </label>
            <label>
                Centro o consulta
                <input name="clinic_name" required value="<?= View::escape($user->clinicName) ?>" placeholder="Consulta particular, centro terapeutico">
            </label>
            <label>
                Direccion o zona de atencion
                <input name="clinic_address" value="<?= View::escape($user->clinicAddress) ?>" placeholder="Direccion, comuna o atencion online">
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
                <small>Puedes seleccionar una o varias formas de atencion.</small>
            </div>
            <label>
                Resena profesional
                <textarea name="professional_bio" rows="4" placeholder="Breve descripcion de experiencia, enfoque o tipo de atencion"><?= View::escape($user->professionalBio) ?></textarea>
            </label>
        </fieldset>

        <button class="button" type="submit">Guardar perfil profesional</button>
    </form>
</section>
<?php
$content = ob_get_clean();
$hideRightSidebar = true;
require dirname(__DIR__) . '/layout.php';
