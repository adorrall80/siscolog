<?php

use Core\View;

$modalityLabels = [
    'presencial' => 'Presencial',
    'online' => 'Online',
    'telefonica' => 'Telefonica',
];

$riskLabels = [
    'sin_riesgo' => 'Sin riesgo observado',
    'bajo' => 'Bajo',
    'medio' => 'Medio',
    'alto' => 'Alto',
    'critico' => 'Critico',
];

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Detalle de cita</p>
            <h1><?= View::escape($appointment->patientName) ?></h1>
            <p><?= View::escape(date('d/m/Y H:i', strtotime($appointment->sessionDate))) ?></p>
        </div>
        <div class="actions-row">
            <a class="button small secondary" href="/citas">Volver a citas</a>
            <a class="button small secondary" href="/citas/<?= View::escape((string) $appointment->id) ?>/editar">Editar cita</a>
            <a class="button small" href="/patients/<?= View::escape((string) $appointment->patientId) ?>">Ver ficha</a>
        </div>
    </div>

    <div class="summary-grid">
        <article class="summary-card">
            <span>Paciente</span>
            <strong><?= View::escape($appointment->patientName) ?></strong>
            <small><?= View::escape($appointment->patientCode) ?></small>
        </article>

        <article class="summary-card">
            <span>Modalidad</span>
            <strong><?= View::escape($modalityLabels[$appointment->modality] ?? $appointment->modality) ?></strong>
        </article>

        <article class="summary-card">
            <span>Riesgo</span>
            <strong><?= View::escape($riskLabels[$appointment->riskLevel] ?? $appointment->riskLevel) ?></strong>
        </article>

        <article class="summary-card">
            <span>Vigencia</span>
            <strong><?= $appointment->isActive ? 'Vigente' : 'No vigente' ?></strong>
        </article>
    </div>

    <section class="detail-grid">
        <article class="detail-card">
            <span class="tag tone-blue">Motivo</span>
            <h2>Motivo de sesion</h2>
            <p><?= View::escape($session->reason ?: 'Sin motivo registrado') ?></p>
        </article>

        <article class="detail-card">
            <span class="tag tone-green">Participantes</span>
            <h2>Quienes participaron</h2>
            <?php if ($session->participants === []): ?>
                <p>Sin participantes registrados.</p>
            <?php else: ?>
                <div class="subtype-chip-list">
                    <?php foreach ($session->participants as $participant): ?>
                        <span class="tag tone-blue"><?= View::escape($participant) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="detail-card">
            <span class="tag tone-amber">Temas</span>
            <h2>Temas tratados</h2>
            <?php if ($session->topics === []): ?>
                <p>Sin temas registrados.</p>
            <?php else: ?>
                <div class="subtype-chip-list">
                    <?php foreach ($session->topics as $topic): ?>
                        <span class="tag tone-blue"><?= View::escape($topic) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section class="panel record-card">
        <div class="panel-header compact">
            <div>
                <p class="eyebrow">Nota clinica</p>
                <h2>Informacion completa de la sesion</h2>
            </div>
        </div>

        <dl class="clinical-detail-list">
            <dt>Relato del paciente</dt>
            <dd><?= nl2br(View::escape($session->subjectiveNote ?: 'Sin relato registrado')) ?></dd>

            <dt>Observaciones del profesional</dt>
            <dd><?= nl2br(View::escape($session->objectiveNote ?: 'Sin observaciones registradas')) ?></dd>

            <dt>Impresion clinica</dt>
            <dd><?= nl2br(View::escape($session->clinicalImpression ?: 'Sin impresion registrada')) ?></dd>

            <dt>Acuerdos</dt>
            <dd><?= nl2br(View::escape($session->agreements ?: 'Sin acuerdos registrados')) ?></dd>

            <dt>Proximos pasos</dt>
            <dd><?= nl2br(View::escape($session->nextSteps ?: 'Sin proximos pasos registrados')) ?></dd>

            <dt>Ultima actualizacion</dt>
            <dd><?= View::escape($session->updatedAt ?: 'Sin fecha') ?></dd>
        </dl>
    </section>
</section>
<?php
$content = ob_get_clean();
$hideRightSidebar = true;
require dirname(__DIR__) . '/layout.php';
