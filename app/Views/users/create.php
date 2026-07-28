<?php

use Core\View;

ob_start();
?>
<section class="action-toolbar">
    <strong>Nuevo usuario</strong>
    <span class="toolbar-code">Acceso al sistema</span>
    <a class="button small secondary" href="/usuarios">Volver</a>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Administracion</p>
            <h1>Crear usuario</h1>
            <p>Asigna rol, estado y acceso inicial para el equipo clinico.</p>
        </div>
    </div>

    <form method="post" action="/usuarios" class="form-grid">
        <?php require __DIR__ . '/form.php'; ?>

        <button class="button" type="submit">Guardar usuario</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
