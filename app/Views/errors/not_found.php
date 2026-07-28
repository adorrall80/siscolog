<?php

use Core\View;

$errorTitle ??= 'Página no encontrada';
$errorMessage ??= 'El contenido que buscas no existe, fue eliminado o ya no está disponible.';
$backUrl ??= '/';
$backLabel ??= 'Volver al menú';

ob_start();
?>
<section class="error-state" role="alert">
    <div class="error-state-code" aria-hidden="true">404</div>

    <div class="error-state-content">
        <p class="eyebrow">Contenido no disponible</p>
        <h1><?= View::escape($errorTitle) ?></h1>
        <p><?= View::escape($errorMessage) ?></p>

        <div class="actions-row">
            <a class="button" href="<?= View::escape($backUrl) ?>">
                <?= View::escape($backLabel) ?>
            </a>
            <a class="button secondary" href="/">Ir al menú principal</a>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$hideRightSidebar = true;
require dirname(__DIR__) . '/layout.php';
