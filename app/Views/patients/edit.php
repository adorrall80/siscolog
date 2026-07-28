<?php

use Core\View;

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Ficha paciente</p>
            <h1>Editar <?= View::escape($patient->fullName) ?></h1>
        </div>
        <a class="button small" href="/patients/<?= View::escape((string) $patient->id) ?>">Volver a ficha</a>
    </div>

    <form method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/update" class="form-grid">
        <?php require __DIR__ . '/form.php'; ?>

        <button class="button" type="submit">Actualizar paciente</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
