<?php

use App\Models\AiAnalysisOutput;
use App\Models\ClinicalSession;
use App\Models\PatientConsent;
use App\Models\PsychometricResult;
use Core\View;

$psychometricResults ??= [];
$psychometricDetails ??= [];
$statusLabels = [];
$modalityLabels = [];
$riskLabels = [];
$consentLabels = [];

foreach ($patientStatuses as $status) {
    $statusLabels[$status->code] = $status->name;
}

foreach ($sessionModalities as $modality) {
    $modalityLabels[$modality->code] = $modality->name;
}

foreach ($riskLevels as $riskLevel) {
    $riskLabels[$riskLevel->code] = $riskLevel->name;
}

foreach ($consentTypes as $type) {
    $consentLabels[$type->code] = $type->name;
}

ob_start();
?>
<section class="panel report-shell">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Reporte clinico</p>
            <h1><?= View::escape($patient->fullName) ?></h1>
            <p>Generado: <?= View::escape($generatedAt) ?>. Uso interno profesional, no reemplaza criterio clinico.</p>
        </div>
        <div class="report-actions">
            <a class="button small secondary no-print" href="/patients/<?= View::escape((string) $patient->id) ?>">Volver a ficha</a>
            <a class="button small no-print" href="/patients/<?= View::escape((string) $patient->id) ?>/reporte/exportar">Exportar / imprimir</a>
            <?php if ($exportMode): ?>
                <button class="button small no-print" type="button" onclick="window.print()">Imprimir o guardar PDF</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="report-grid">
        <article class="report-card">
            <span>Codigo</span>
            <strong><?= View::escape($patient->code) ?></strong>
        </article>
        <article class="report-card">
            <span>Estado clinico</span>
            <strong><?= View::escape($statusLabels[$patient->status] ?? $patient->status) ?></strong>
        </article>
        <article class="report-card">
            <span>Fecha nacimiento</span>
            <strong><?= View::escape($patient->birthDate ?: 'No registrada') ?></strong>
        </article>
        <article class="report-card">
            <span>Contacto</span>
            <strong><?= View::escape($patient->email ?: $patient->phone ?: 'No registrado') ?></strong>
        </article>
    </div>

    <section class="report-section">
        <h2>Sesiones</h2>
        <?php if ($sessions === []): ?>
            <p>No hay sesiones registradas.</p>
        <?php else: ?>
            <?php foreach ($sessions as $session): ?>
                <?php /** @var ClinicalSession $session */ ?>
                <article class="report-entry">
                    <header>
                        <strong>Sesion <?= View::escape((string) $session->id) ?> · <?= View::escape($session->sessionDate) ?></strong>
                        <span><?= View::escape($session->isActive ? 'Vigente' : 'No vigente') ?></span>
                    </header>
                    <p><strong>Modalidad:</strong> <?= View::escape($modalityLabels[$session->modality] ?? $session->modality) ?> · <strong>Riesgo:</strong> <?= View::escape($riskLabels[$session->riskLevel] ?? $session->riskLevel) ?></p>
                    <p><strong>Motivo:</strong> <?= View::escape($session->reason ?: 'No registrado') ?></p>
                    <p><strong>Participantes:</strong> <?= View::escape($session->participants === [] ? 'No registrados' : implode(', ', $session->participants)) ?></p>
                    <p><strong>Temas:</strong> <?= View::escape($session->topics === [] ? 'No registrados' : implode(', ', $session->topics)) ?></p>
                    <p><strong>Impresion clinica:</strong> <?= View::escape($session->clinicalImpression ?: 'No registrada') ?></p>
                    <p><strong>Acuerdos / proximos pasos:</strong> <?= View::escape(trim((string) $session->agreements . ' ' . (string) $session->nextSteps) ?: 'No registrados') ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="report-section">
        <h2>Instrumentos psicometricos</h2>
        <?php if ($psychometricResults === []): ?>
            <p>No hay resultados psicometricos registrados.</p>
        <?php else: ?>
            <div class="report-table">
                <?php foreach ($psychometricResults as $result): ?>
                    <?php /** @var PsychometricResult $result */ ?>
                    <?php $detail = $psychometricDetails[$result->id] ?? null; ?>
                    <article>
                        <strong><?= View::escape($result->instrumentName) ?></strong>
                        <span><?= View::escape($result->appliedAt) ?> · <?= View::escape((string) $result->score) ?>/<?= View::escape((string) $result->maxScore) ?> · <?= View::escape($result->isActive ? 'Vigente' : 'No vigente') ?></span>
                        <p><?= View::escape($result->interpretation ?: 'Sin interpretacion registrada') ?></p>
                        <?php if (is_array($detail)): ?>
                            <p>
                                <strong>Aplicacion guiada:</strong>
                                <?= View::escape((string) ($detail['version_label'] ?? 'version no registrada')) ?>
                                · <?= View::escape((string) ($detail['professional_name'] ?? 'Sin profesional')) ?>
                            </p>
                            <?php if (($detail['answers'] ?? []) !== []): ?>
                                <div class="report-answer-list">
                                    <?php foreach ($detail['answers'] as $answer): ?>
                                        <p>
                                            <strong><?= View::escape((string) $answer['item_order']) ?>.</strong>
                                            <?= View::escape((string) $answer['question_snapshot']) ?>
                                            <br>
                                            Respuesta: <?= View::escape((string) ($answer['answer_label'] ?? $answer['answer_value'] ?? 'Sin respuesta')) ?>
                                            · Puntaje: <?= View::escape((string) ($answer['answer_score'] ?? 0)) ?>
                                        </p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="report-section">
        <h2>Consentimientos</h2>
        <?php if ($consents === []): ?>
            <p>No hay consentimientos registrados.</p>
        <?php else: ?>
            <?php foreach ($consents as $consent): ?>
                <?php /** @var PatientConsent $consent */ ?>
                <p>
                    <strong><?= View::escape($consentLabels[$consent->consentType] ?? $consent->consentType) ?></strong>
                    · version <?= View::escape($consent->documentVersion) ?>
                    · <?= View::escape($consent->accepted ? 'Aceptado' : 'No aceptado') ?>
                    · <?= View::escape($consent->isActive ? 'Vigente' : 'No vigente') ?>
                </p>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="report-section">
        <h2>Historial IA supervisado</h2>
        <?php if ($analyses === []): ?>
            <p>No hay salidas IA vigentes registradas.</p>
        <?php else: ?>
            <?php foreach ($analyses as $analysis): ?>
                <?php
                /** @var AiAnalysisOutput $analysis */
                $output = $analysis->output();
                ?>
                <article class="report-entry">
                    <header>
                        <strong>Analisis <?= View::escape((string) $analysis->id) ?> · <?= View::escape((string) $analysis->createdAt) ?></strong>
                        <span><?= View::escape($analysis->reviewStatus) ?></span>
                    </header>
                    <p><strong>Resumen:</strong> <?= View::escape((string) ($output['resumen'] ?? 'Sin resumen')) ?></p>
                    <p><strong>Nota profesional:</strong> <?= View::escape($analysis->professionalNotes ?: 'No registrada') ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
