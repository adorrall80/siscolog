<?php

use Core\View;

$contact = $patient->emergencyContact;
$sessionCount = count($sessions);
$consentCount = count($consents);
$analysisCount = count($analyses);
$psychometricResults ??= [];
$psychometricCount = count($psychometricResults);
$psychometricActiveCount = 0;
$psychometricAlertCount = 0;
$psychometricTrends = [];
$acceptedConsentCount = 0;
$activeConsentCount = 0;
$statusLabels = [];
$statusColors = [];
$riskLabels = [];
$riskColors = [];
$modalityLabels = [];
$modalityColors = [];
$consentLabels = [];
$consentColors = [];
$participantLabels = [];
$reviewLabels = [];
$reviewColors = [];

foreach ($consents as $consent) {
    if ($consent->accepted) {
        $acceptedConsentCount++;
    }

    if ($consent->isActive) {
        $activeConsentCount++;
    }
}

foreach ($psychometricResults as $result) {
    if (!$result->isActive) {
        continue;
    }

    $psychometricActiveCount++;

    if ($result->severityTone() !== 'green') {
        $psychometricAlertCount++;
    }

    $trendKey = $result->instrumentName;
    $psychometricTrends[$trendKey] ??= [
        'name' => $result->instrumentName,
        'code' => $result->instrumentCode,
        'max_score' => $result->maxScore,
        'points' => [],
    ];
    $psychometricTrends[$trendKey]['points'][] = [
        'date' => $result->appliedAt,
        'score' => $result->score,
        'tone' => $result->severityTone(),
        'interpretation' => $result->interpretation ?: 'Sin interpretacion registrada',
    ];
}

foreach ($psychometricTrends as &$trend) {
    usort($trend['points'], fn (array $a, array $b): int => strcmp($a['date'], $b['date']));
}
unset($trend);

foreach ($patientStatuses as $status) {
    $statusLabels[$status->code] = $status->name;
    $statusColors[$status->code] = $status->color ?: 'green';
}

foreach ($riskLevels as $riskLevel) {
    $riskLabels[$riskLevel->code] = $riskLevel->name;
    $riskColors[$riskLevel->code] = $riskLevel->color ?: 'green';
}

foreach ($sessionModalities as $modality) {
    $modalityLabels[$modality->code] = $modality->name;
    $modalityColors[$modality->code] = $modality->color ?: 'green';
}

foreach ($consentTypes as $type) {
    $consentLabels[$type->code] = $type->name;
    $consentColors[$type->code] = $type->color ?: 'green';
}

foreach ($participantTypes as $participantType) {
    $participantLabels[$participantType->code] = $participantType->name;
}

foreach ($aiReviewStatuses as $status) {
    $reviewLabels[$status->code] = $status->name;
    $reviewColors[$status->code] = $status->color ?: 'amber';
}

ob_start();
?>
<nav class="record-tabs" aria-label="Secciones de ficha paciente">
    <a class="active" href="/patients/<?= View::escape((string) $patient->id) ?>">Ficha paciente</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/vinculos">Vinculos con paciente</a>
    <a href="#sesiones">Sesiones H.Clinica</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/reporte">Informes</a>
    <a href="#instrumentos">Instrumentos</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/create">Sesiones</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/consents/create">Consentimientos</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/ai-history">Historial IA</a>
    <a href="#">Recetas</a>
    <a href="#">Archivos</a>
</nav>

<section class="action-toolbar">
    <strong><?= View::escape($patient->fullName) ?></strong>
    <span class="toolbar-code"><?= View::escape($patient->code) ?></span>
    <span class="state tone-<?= View::escape($statusColors[$patient->status] ?? 'green') ?>">
        <?= View::escape($statusLabels[$patient->status] ?? $patient->status) ?>
    </span>
    <a class="button small" href="/patients/<?= View::escape((string) $patient->id) ?>/edit">Guardar / editar</a>
    <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/create">Nueva sesion</a>
    <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>/consents/create">Consentimiento</a>
    <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>/reporte">Reporte</a>
    <a class="icon-action" href="#">?</a>
    <a class="icon-action" href="#">@</a>
</section>

<section class="clinical-record">
    <div class="record-main">
        <section class="panel record-card">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Ficha clinica</p>
                    <h1><?= View::escape($patient->fullName) ?></h1>
                </div>
            </div>

            <div class="form-readonly-grid">
                <div class="readonly-field">
                    <span>Codigo interno</span>
                    <strong><?= View::escape($patient->code) ?></strong>
                </div>

                <div class="readonly-field">
                    <span>Fecha nacimiento</span>
                    <strong><?= View::escape($patient->birthDate ?: 'No registrada') ?></strong>
                </div>

                <div class="readonly-field">
                    <span>Email</span>
                    <strong><?= View::escape($patient->email ?: 'No registrado') ?></strong>
                </div>

                <div class="readonly-field">
                    <span>Telefono</span>
                    <strong><?= View::escape($patient->phone ?: 'No registrado') ?></strong>
                </div>

                <div class="readonly-field">
                    <span>Contacto emergencia</span>
                    <strong><?= View::escape($contact?->name ?: 'No registrado') ?></strong>
                </div>

                <div class="readonly-field">
                    <span>Telefono emergencia</span>
                    <strong><?= View::escape($contact?->phone ?: 'No registrado') ?></strong>
                </div>
            </div>
        </section>

        <section class="module-grid">
            <article class="module-card">
                <span class="tag">Consentimiento</span>
                <h2><?= View::escape((string) $acceptedConsentCount) ?> aceptado<?= $acceptedConsentCount === 1 ? '' : 's' ?></h2>
                <p><?= View::escape((string) $activeConsentCount) ?> activo<?= $activeConsentCount === 1 ? '' : 's' ?> de <?= View::escape((string) $consentCount) ?> registrado<?= $consentCount === 1 ? '' : 's' ?>.</p>
            </article>

            <article class="module-card">
                <span class="tag">Sesiones</span>
                <h2><?= View::escape((string) $sessionCount) ?> registrada<?= $sessionCount === 1 ? '' : 's' ?></h2>
                <p>Historial clinico ordenado por fecha, con modalidad y nivel de riesgo.</p>
            </article>

            <article class="module-card">
                <span class="tag">IA</span>
                <h2><?= $canAnalyzeWithAi ? 'Habilitada' : 'Bloqueada' ?></h2>
                <p><?= $canAnalyzeWithAi ? 'Consentimiento IA activo. Todo analisis queda pendiente de revision profesional.' : 'Falta consentimiento activo para analisis asistido por IA.' ?></p>
            </article>

            <article class="module-card">
                <span class="tag">Instrumentos</span>
                <h2><?= View::escape((string) $psychometricActiveCount) ?> vigente<?= $psychometricActiveCount === 1 ? '' : 's' ?></h2>
                <p><?= View::escape((string) $psychometricAlertCount) ?> con puntaje de seguimiento de <?= View::escape((string) $psychometricCount) ?> resultado<?= $psychometricCount === 1 ? '' : 's' ?>.</p>
            </article>
        </section>

        <section class="panel record-card" id="instrumentos">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Instrumentos psicometricos</p>
                    <h2>Resultados del paciente</h2>
                    <p>Escalas y puntajes historicos. Son apoyo referencial, no diagnostico automatico.</p>
                </div>
                <a class="button small" href="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/create">Registrar</a>
            </div>

            <?php if ($psychometricCount === 0): ?>
                <p>No hay resultados psicometricos registrados todavia.</p>
            <?php else: ?>
                <?php if ($psychometricTrends !== []): ?>
                    <div class="psychometric-trends" aria-label="Evolucion psicometrica">
                        <?php foreach ($psychometricTrends as $trend): ?>
                            <article class="psychometric-trend-card">
                                <header>
                                    <div>
                                        <strong><?= View::escape($trend['name']) ?></strong>
                                        <small><?= View::escape($trend['code']) ?> · max <?= View::escape((string) $trend['max_score']) ?></small>
                                    </div>
                                    <span class="tag"><?= View::escape((string) count($trend['points'])) ?> aplicacion<?= count($trend['points']) === 1 ? '' : 'es' ?></span>
                                </header>
                                <div class="psychometric-bars">
                                    <?php foreach ($trend['points'] as $point): ?>
                                        <?php
                                        $height = $trend['max_score'] > 0
                                            ? max(8, min(100, (int) round(($point['score'] / $trend['max_score']) * 100)))
                                            : 8;
                                        $title = $trend['name'] . ' | ' . $point['date'] . ' | ' . $point['score'] . '/' . $trend['max_score'] . ' | ' . $point['interpretation'];
                                        ?>
                                        <span class="psychometric-bar-item" title="<?= View::escape($title) ?>">
                                            <i class="tone-<?= View::escape($point['tone']) ?>" style="height: <?= View::escape((string) $height) ?>%;"></i>
                                            <small><?= View::escape((string) $point['score']) ?></small>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <p>Lectura rapida: compara puntajes en el tiempo, siempre junto a entrevista y contexto clinico.</p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Instrumento</th>
                                <th>Puntaje</th>
                                <th>Interpretacion</th>
                                <th>Profesional</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($psychometricResults as $result): ?>
                                <tr>
                                    <td><?= View::escape($result->appliedAt) ?></td>
                                    <td>
                                        <strong><?= View::escape($result->instrumentName) ?></strong>
                                        <small><?= View::escape($result->instrumentCode) ?></small>
                                    </td>
                                    <td>
                                        <span class="state tone-<?= View::escape($result->severityTone()) ?>">
                                            <?= View::escape((string) $result->score) ?> / <?= View::escape((string) $result->maxScore) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= View::escape($result->interpretation ?: 'Sin interpretacion registrada') ?>
                                        <?php if ($result->professionalNotes): ?>
                                            <small><?= View::escape($result->professionalNotes) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= View::escape($result->professionalName ?: 'Sin profesional') ?></td>
                                    <td>
                                        <span class="state tone-<?= $result->isActive ? 'green' : 'red' ?>">
                                            <?= $result->isActive ? 'Vigente' : 'No vigente' ?>
                                        </span>
                                    </td>
                                    <td class="table-actions">
                                        <a class="action-link ghost" href="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/<?= View::escape((string) $result->id) ?>/edit">Editar</a>
                                        <?php if ($result->isActive): ?>
                                            <a class="switch-action is-on" href="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/<?= View::escape((string) $result->id) ?>/desactivar" aria-label="Desactivar resultado psicometrico">
                                                <span></span>
                                                Vigente
                                            </a>
                                        <?php else: ?>
                                            <a class="switch-action is-off" href="/patients/<?= View::escape((string) $patient->id) ?>/instrumentos/<?= View::escape((string) $result->id) ?>/activar" aria-label="Activar resultado psicometrico">
                                                <span></span>
                                                No vigente
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel record-card">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Consentimiento informado</p>
                    <h2>Documentos del paciente</h2>
                </div>
                <a class="button small" href="/patients/<?= View::escape((string) $patient->id) ?>/consents/create">Registrar</a>
            </div>

            <?php if ($consentCount === 0): ?>
                <p>No hay consentimientos registrados todavia.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Version</th>
                                <th>Aceptado</th>
                                <th>Fecha</th>
                                <th>Activo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consents as $consent): ?>
                                <tr>
                                    <td>
                                        <span class="state tone-<?= View::escape($consentColors[$consent->consentType] ?? 'green') ?>">
                                            <?= View::escape($consentLabels[$consent->consentType] ?? $consent->consentType) ?>
                                        </span>
                                    </td>
                                    <td><?= View::escape($consent->documentVersion) ?></td>
                                    <td><?= $consent->accepted ? 'Si' : 'No' ?></td>
                                    <td><?= View::escape($consent->acceptedAt ?: ($consent->revokedAt ?: 'Sin fecha')) ?></td>
                                    <td>
                                        <span class="state tone-<?= $consent->isActive ? 'green' : 'red' ?>">
                                            <?= $consent->isActive ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="table-actions">
                                        <?php if ($consent->isActive): ?>
                                            <a class="switch-action is-on" href="/patients/<?= View::escape((string) $patient->id) ?>/consents/<?= View::escape((string) $consent->id) ?>/desactivar" aria-label="Desactivar consentimiento">
                                                <span></span>
                                                Vigente
                                            </a>
                                        <?php else: ?>
                                            <a class="switch-action is-off" href="/patients/<?= View::escape((string) $patient->id) ?>/consents/<?= View::escape((string) $consent->id) ?>/activar" aria-label="Activar consentimiento">
                                                <span></span>
                                                No vigente
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel record-card">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Analisis IA supervisado</p>
                    <h2>Salidas generadas</h2>
                </div>
                <form method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/ai-analysis">
                    <button class="button small" type="submit" <?= $canAnalyzeWithAi ? '' : 'disabled' ?>>
                        Generar evolucion IA del caso
                    </button>
                </form>
                <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>/ai-history">Historial IA</a>
            </div>

            <?php if (!$canAnalyzeWithAi): ?>
                <div class="clinical-alert clinical-alert-danger" role="alert">
                    <span class="clinical-alert-icon" aria-hidden="true">!</span>
                    <div>
                        <strong>IA bloqueada por consentimiento</strong>
                        <p>Debes registrar un consentimiento activo y aceptado de tipo "Analisis asistido por IA" antes de generar una evolucion IA.</p>
                    </div>
                </div>
            <?php else: ?>
                <p class="form-help">La evolucion IA usa solo sesiones activas. Si cambian los datos clinicos, elimina la salida anterior y deja solo la ultima version.</p>
            <?php endif; ?>

            <?php if ($analysisCount === 0): ?>
                <p>No hay analisis IA registrados todavia.</p>
            <?php else: ?>
                <div class="timeline compact-timeline">
                    <?php foreach ($analyses as $analysis): ?>
                        <?php $output = $analysis->output(); ?>
                        <article class="timeline-item">
                            <div class="timeline-meta">
                                <span class="state tone-<?= View::escape($reviewColors[$analysis->reviewStatus] ?? 'amber') ?>">
                                    <?= View::escape($reviewLabels[$analysis->reviewStatus] ?? $analysis->reviewStatus) ?>
                                </span>
                                <span class="state tone-<?= $analysis->isActive ? 'green' : 'red' ?>">
                                    <?= $analysis->isActive ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </div>

                            <h2><?= View::escape($analysis->createdAt ?: 'Sin fecha') ?></h2>
                            <p><?= View::escape((string) ($output['resumen'] ?? 'Sin resumen disponible.')) ?></p>

                            <dl>
                                <dt>Evolucion del caso</dt>
                                <dd><?= View::escape((string) ($output['evolucion_del_caso'] ?? 'Pendiente de comparar sesiones.')) ?></dd>
                            <?php
                            $observedFactors = array_values(array_filter(
                                $output['factores_observados'] ?? ['Sin factores registrados.'],
                                fn (string $factor): bool => strpos($factor, 'Participantes de sesion:') !== 0
                            ));
                            $observedFactors = $observedFactors === [] ? ['Sin factores clinicos registrados.'] : $observedFactors;
                            ?>
                            <dt>Factores observados</dt>
                            <dd><?= View::escape(implode(' | ', $observedFactors)) ?></dd>
                                <dt>Hipotesis de trabajo</dt>
                                <dd><?= View::escape(implode(' | ', $output['hipotesis_de_trabajo'] ?? ['Pendiente de evaluacion profesional.'])) ?></dd>
                                <dt>Factores protectores</dt>
                                <dd><?= View::escape(implode(' | ', $output['factores_protectores'] ?? ['No registrados.'])) ?></dd>
                                <dt>Alertas</dt>
                                <dd><?= View::escape(implode(' | ', $output['alertas'] ?? ['Sin alertas registradas.'])) ?></dd>
                                <dt>Preguntas proxima sesion</dt>
                                <dd><?= View::escape(implode(' | ', $output['preguntas_para_proxima_sesion'] ?? ['Pendiente de definir.'])) ?></dd>
                                <dt>Limite de uso</dt>
                                <dd><?= View::escape((string) ($output['limites'] ?? 'Requiere revision profesional.')) ?></dd>
                                <?php if ($analysis->professionalNotes): ?>
                                    <dt>Nota profesional</dt>
                                    <dd><?= View::escape($analysis->professionalNotes) ?></dd>
                                <?php endif; ?>
                                <?php if ($analysis->reviewedAt): ?>
                                    <dt>Revisado</dt>
                                    <dd><?= View::escape($analysis->reviewedAt) ?></dd>
                                <?php endif; ?>
                            </dl>

                            <form class="inline-search" method="post" action="/patients/<?= View::escape((string) $patient->id) ?>/ai-analysis/<?= View::escape((string) $analysis->id) ?>/review">
                                <label>
                                    Estado revision
                                    <select name="review_status">
                                        <?php foreach ($aiReviewStatuses as $status): ?>
                                            <option value="<?= View::escape($status->code) ?>" <?= $analysis->reviewStatus === $status->code ? 'selected' : '' ?>>
                                                <?= View::escape($status->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="review-notes">
                                    Nota profesional
                                    <textarea name="professional_notes" rows="4" placeholder="Criterio clinico, ajuste o descarte"><?= View::escape($analysis->professionalNotes ?: '') ?></textarea>
                                </label>
                                <button class="button small" type="submit">Guardar revision</button>
                            </form>

                            <div class="table-actions">
                                <?php if ($analysis->isActive): ?>
                                    <a class="switch-action is-on" href="/patients/<?= View::escape((string) $patient->id) ?>/ai-analysis/<?= View::escape((string) $analysis->id) ?>/desactivar" aria-label="Desactivar analisis IA">
                                        <span></span>
                                        Vigente
                                    </a>
                                <?php else: ?>
                                    <a class="switch-action is-off" href="/patients/<?= View::escape((string) $patient->id) ?>/ai-analysis/<?= View::escape((string) $analysis->id) ?>/activar" aria-label="Activar analisis IA">
                                        <span></span>
                                        No vigente
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="record-history">
        <section class="panel" id="sesiones">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Historico sesiones</p>
                    <h2>Sesiones clinicas</h2>
                </div>
                <a class="button small" href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/create">Registrar</a>
            </div>

            <?php if ($sessionCount === 0): ?>
                <p>No hay sesiones registradas todavia.</p>
            <?php else: ?>
                <div class="timeline compact-timeline">
                    <?php foreach ($sessions as $session): ?>
                        <article class="timeline-item" id="session-<?= View::escape((string) $session->id) ?>">
                            <div class="timeline-meta">
                                <span class="tag tone-<?= View::escape($modalityColors[$session->modality] ?? 'green') ?>">
                                    <?= View::escape($modalityLabels[$session->modality] ?? $session->modality) ?>
                                </span>
                                <span class="risk tone-<?= View::escape($riskColors[$session->riskLevel] ?? 'green') ?>">
                                    <?= View::escape($riskLabels[$session->riskLevel] ?? $session->riskLevel) ?>
                                </span>
                                <span class="state tone-<?= $session->isActive ? 'green' : 'red' ?>">
                                    <?= $session->isActive ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </div>
                            <h2><?= View::escape($session->sessionDate) ?></h2>
                            <p><?= View::escape($session->reason ?: 'Sin motivo registrado') ?></p>

                            <?php if ($session->participants !== []): ?>
                                <p>
                                    <strong>Participantes:</strong>
                                    <?= View::escape(implode(', ', array_map(
                                        fn (string $participant): string => $participantLabels[$participant] ?? $participant,
                                        $session->participants
                                    ))) ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($session->topics !== []): ?>
                                <div class="session-topic-list">
                                    <strong>Temas tratados:</strong>
                                    <div class="subtype-chip-list">
                                        <?php foreach ($session->topics as $topic): ?>
                                            <span class="tag tone-blue"><?= View::escape($topic) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($session->clinicalImpression): ?>
                                <dl>
                                    <dt>Impresion clinica</dt>
                                    <dd><?= View::escape($session->clinicalImpression) ?></dd>
                                </dl>
                            <?php endif; ?>

                            <div class="table-actions">
                                <a class="action-link ghost" href="/citas/<?= View::escape((string) $session->id) ?>">Ver cita</a>
                                <a class="action-link ghost" href="/citas/<?= View::escape((string) $session->id) ?>/editar">Editar cita</a>
                                <?php if ($session->isActive): ?>
                                    <a class="switch-action is-on" href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/<?= View::escape((string) $session->id) ?>/desactivar" aria-label="Desactivar sesion clinica">
                                        <span></span>
                                        Vigente
                                    </a>
                                <?php else: ?>
                                    <a class="switch-action is-off" href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/<?= View::escape((string) $session->id) ?>/activar" aria-label="Activar sesion clinica">
                                        <span></span>
                                        No vigente
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </aside>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
