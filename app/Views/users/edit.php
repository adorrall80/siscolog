<?php

use Core\View;

ob_start();
?>
<section class="action-toolbar">
    <strong>Editar usuario</strong>
    <span class="toolbar-code">Acceso al sistema</span>
    <a class="button small secondary" href="/usuarios">Volver</a>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Administracion</p>
            <h1>Editar usuario</h1>
            <p>Actualiza rol, estado o datos de acceso. La contrasena solo cambia si completas el campo.</p>
        </div>
    </div>

    <form method="post" action="/usuarios/<?= View::escape((string) $user->id) ?>/actualizar" class="form-grid">
        <?php require __DIR__ . '/form.php'; ?>

        <button class="button" type="submit">Actualizar usuario</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
