<?php ob_start(); ?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Nuevo registro</p>
            <h1>Crear paciente</h1>
        </div>
        <span class="status-pill">Modulo central</span>
    </div>

    <form method="post" action="/patients" class="form-grid">
        <?php require __DIR__ . '/form.php'; ?>

        <button class="button" type="submit">Guardar</button>
    </form>
</section>

<section class="module-grid">
    <article class="module-card">
        <span class="tag">Propuesta</span>
        <h2>Ficha modular</h2>
        <p>Podemos separar identidad, contacto, antecedentes, consentimiento y emergencia en bloques configurables.</p>
    </article>

    <article class="module-card">
        <span class="tag">Buenas practicas</span>
        <h2>Datos sensibles</h2>
        <p>El formulario debe pedir solo datos necesarios y registrar consentimiento antes de usar IA.</p>
    </article>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
