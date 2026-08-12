<?php

use Core\View;

$reviewLabels = [];
$reviewColors = [];
$sessionsById = [];

foreach ($aiReviewStatuses as $status) {
    $reviewLabels[$status->code] = $status->name;
    $reviewColors[$status->code] = $status->color ?: 'amber';
}

foreach ($sessions as $session) {
    $sessionsById[(int) $session->id] = $session;
}

$aiOutputToText = static function (array $output): string {
    $sections = [
        'Resumen clínico' => $output['resumen'] ?? '',
        'Evolución del caso' => $output['evolucion_del_caso'] ?? '',
        'Factores observados' => $output['factores_observados'] ?? [],
        'Hipótesis de trabajo' => $output['hipotesis_de_trabajo'] ?? [],
        'Factores protectores' => $output['factores_protectores'] ?? [],
        'Alertas' => $output['alertas'] ?? [],
        'Próximos pasos sugeridos' => $output['proximos_pasos_sugeridos'] ?? [],
        'Preguntas para la próxima sesión' => $output['preguntas_para_proxima_sesion'] ?? [],
    ];
    $blocks = [];

    foreach ($sections as $title => $value) {
        if (is_array($value)) {
            $value = implode("\n", array_map(fn ($item): string => '- ' . (string) $item, $value));
        }
        $value = trim((string) $value);
        if ($value !== '') {
            $blocks[] = $title . "\n" . $value;
        }
    }

    return implode("\n\n", $blocks);
};

ob_start();
?>
<nav class="record-tabs" aria-label="Secciones de ficha paciente">
    <a href="/patients/<?= View::escape((string) $patient->id) ?>">Ficha paciente</a>
    <a class="active" href="/patients/<?= View::escape((string) $patient->id) ?>/ai-history">Historial IA</a>
    <a href="/patients/<?= View::escape((string) $patient->id) ?>/sessions/create">Nueva sesion</a>
</nav>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Historial IA</p>
            <h1><?= View::escape($patient->fullName) ?></h1>
            <p>Registro de evoluciones IA, sesiones asociadas, participantes, nota IA y revision profesional.</p>
        </div>
        <a class="button small secondary" href="/patients/<?= View::escape((string) $patient->id) ?>">Volver a ficha</a>
    </div>

    <div class="ai-history-help">
        <article>
            <span class="state tone-amber">Revision profesional</span>
            <h2>Pendiente, aceptado, editado o descartado</h2>
            <p>Indica si el profesional ya reviso la salida IA y dejo criterio clinico sobre ella.</p>
        </article>
        <article>
            <span class="state tone-green">Version IA</span>
            <h2>Vigente o historica</h2>
            <p>Indica si esa salida sigue siendo la actual. Una historica queda guardada para trazabilidad, pero fue reemplazada por una generacion posterior.</p>
        </article>
    </div>

    <?php if ($analyses === []): ?>
        <p>No hay salidas IA registradas para este paciente.</p>
    <?php else: ?>
        <div class="timeline">
            <?php foreach ($analyses as $analysis): ?>
                <?php
                $output = $analysis->output();
                $originalAiText = $aiOutputToText($output);
                $savedEvolutionText = trim((string) ($analysis->finalText ?? '')) ?: $originalAiText;
                $savedEvolutionSource = $analysis->selectedSource === 'externa' ? 'Propuesta externa' : 'Propuesta de IA';
                $sourceIds = json_decode((string) $analysis->sourceIds, true);
                $sourceIds = is_array($sourceIds) ? array_map('intval', $sourceIds) : [];
                usort($sourceIds, function (int $left, int $right) use ($sessionsById): int {
                    $leftDate = $sessionsById[$left]?->sessionDate ?? '';
                    $rightDate = $sessionsById[$right]?->sessionDate ?? '';

                    if ($leftDate === $rightDate) {
                        return $right <=> $left;
                    }

                    return strcmp($rightDate, $leftDate);
                });
                $participants = [];
                $participantsBySession = [];

                foreach ($sourceIds as $sourceId) {
                    $sourceSession = $sessionsById[$sourceId] ?? null;
                    $sessionParticipants = $sourceSession?->participants ?? [];

                    $participantsBySession[] = [
                        'id' => $sourceId,
                        'date' => $sourceSession?->sessionDate,
                        'participants' => $sessionParticipants,
                    ];

                    foreach ($sessionParticipants as $participant) {
                        $participants[] = $participant;
                    }
                }

                $participants = array_values(array_unique($participants));
                $observedFactors = $output['factores_observados'] ?? ['Sin factores registrados.'];
                $observedFactors = array_values(array_filter(
                    $observedFactors,
                    fn (string $factor): bool => strpos($factor, 'Participantes de sesion:') !== 0
                ));
                $observedFactors = $observedFactors === [] ? ['Sin factores clinicos registrados.'] : $observedFactors;
                $psychometricOutput = $output['instrumentos_psicometricos'] ?? [];
                $psychometricOutput = is_array($psychometricOutput) ? $psychometricOutput : [];
                $aiEngine = is_array($output['motor_ia'] ?? null) ? $output['motor_ia'] : [];
                ?>
                <article class="timeline-item ai-history-item <?= $analysis->isActive ? 'is-current' : 'is-historical' ?>">
                    <div class="timeline-meta">
                        <span class="state tone-<?= View::escape($reviewColors[$analysis->reviewStatus] ?? 'amber') ?>">
                            <?= View::escape($reviewLabels[$analysis->reviewStatus] ?? $analysis->reviewStatus) ?>
                        </span>
                        <span class="state tone-<?= $analysis->isActive ? 'green' : 'gray' ?>">
                            <?= $analysis->isActive ? 'Vigente' : 'Historica' ?>
                        </span>
                    </div>

                    <h2><?= View::escape($analysis->createdAt ?: 'Sin fecha') ?></h2>

                    <div class="ai-version-note <?= $analysis->isActive ? 'is-current' : 'is-historical' ?>">
                        <strong><?= $analysis->isActive ? 'Version vigente del analisis IA' : 'Version historica reemplazada' ?></strong>
                        <p>
                            <?= $analysis->isActive
                                ? 'Esta es la salida IA activa que debe usarse como referencia actual, siempre bajo revision profesional.'
                                : 'Esta salida se conserva solo para trazabilidad. Fue reemplazada por una generacion posterior luego de cambios clinicos o nueva informacion.' ?>
                        </p>
                    </div>

                    <details class="ai-history-detail" <?= $analysis->isActive ? 'open' : '' ?>>
                        <summary>
                            <?= $analysis->isActive ? 'Ver detalle de la version vigente' : 'Ver detalle de esta version historica' ?>
                        </summary>

                    <div class="ai-history-saved-evolution">
                        <div class="ai-history-saved-head">
                            <strong>Evolución final guardada en esta versión</strong>
                            <span class="state tone-blue"><?= View::escape($savedEvolutionSource) ?></span>
                        </div>
                        <div class="note-block ai-history-final-text"><?= nl2br(View::escape($savedEvolutionText ?: 'Esta versión no tiene un texto final guardado.')) ?></div>
                        <details class="ai-history-original-output">
                            <summary>Ver propuesta original de IA</summary>
                            <div class="note-block"><?= nl2br(View::escape($originalAiText ?: 'La propuesta original no está disponible para esta versión antigua.')) ?></div>
                        </details>
                    </div>

                    <dl>
                        <dt>Participantes considerados</dt>
                        <dd>
                            <div class="history-chip-list">
                                <?php if ($participants === []): ?>
                                    <span class="tag">Sin participantes registrados</span>
                                <?php else: ?>
                                    <?php foreach ($participants as $participant): ?>
                                        <span class="tag"><?= View::escape($participant) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </dd>

                        <dt>Participantes por sesion</dt>
                        <dd>
                            <div class="note-block history-factor-list">
                                <?php if ($participantsBySession === []): ?>
                                    <p>Sin participantes registrados en las sesiones asociadas a esta IA.</p>
                                <?php else: ?>
                                    <?php foreach ($participantsBySession as $sessionParticipants): ?>
                                        <p>
                                            <a class="action-link ghost" href="/patients/<?= View::escape((string) $patient->id) ?>#session-<?= View::escape((string) $sessionParticipants['id']) ?>">
                                                Sesion <?= View::escape((string) $sessionParticipants['id']) ?><?= $sessionParticipants['date'] ? ' - ' . View::escape($sessionParticipants['date']) : '' ?>
                                            </a>
                                            <?= View::escape($sessionParticipants['participants'] === [] ? 'Sin participantes registrados' : implode(', ', $sessionParticipants['participants'])) ?>
                                        </p>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </dd>

                        <dt>Nota IA</dt>
                        <dd>
                            <div class="note-block"><?= nl2br(View::escape((string) ($output['resumen'] ?? 'Sin resumen disponible.'))) ?></div>
                        </dd>

                        <dt>Evolucion del caso</dt>
                        <dd>
                            <div class="note-block"><?= nl2br(View::escape((string) ($output['evolucion_del_caso'] ?? 'Sin evolucion registrada.'))) ?></div>
                        </dd>

                        <dt>Factores observados</dt>
                        <dd>
                            <div class="note-block history-factor-list">
                                <?php foreach ($observedFactors as $factor): ?>
                                    <p><?= View::escape((string) $factor) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </dd>

                        <dt>Instrumentos psicometricos considerados</dt>
                        <dd>
                            <div class="note-block history-factor-list">
                                <?php if ($psychometricOutput === []): ?>
                                    <p>Sin instrumentos psicometricos vigentes incorporados a esta salida IA.</p>
                                <?php else: ?>
                                    <?php foreach ($psychometricOutput as $instrument): ?>
                                        <?php if (!is_array($instrument)): ?>
                                            <?php continue; ?>
                                        <?php endif; ?>
                                        <p>
                                            <strong><?= View::escape((string) ($instrument['instrumento'] ?? $instrument['estado'] ?? 'Instrumento')) ?></strong>
                                            <?php if (isset($instrument['puntaje'], $instrument['maximo'])): ?>
                                                · <?= View::escape((string) $instrument['puntaje']) ?>/<?= View::escape((string) $instrument['maximo']) ?>
                                                · <?= View::escape((string) ($instrument['fecha'] ?? 'Sin fecha')) ?>
                                            <?php endif; ?>
                                            <br>
                                            <?= View::escape((string) ($instrument['interpretacion'] ?? $instrument['descripcion'] ?? 'Sin descripcion')) ?>
                                        </p>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </dd>

                        <dt>Nota profesional</dt>
                        <dd>
                            <div class="note-block professional-note"><?= nl2br(View::escape($analysis->professionalNotes ?: 'Pendiente de nota profesional.')) ?></div>
                        </dd>

                        <dt>Fecha revision profesional</dt>
                        <dd><?= View::escape($analysis->reviewedAt ?: 'Pendiente') ?></dd>

                        <dt>Motor IA</dt>
                        <dd>
                            <?= View::escape((string) ($aiEngine['provider'] ?? 'local')) ?>
                            · <?= View::escape((string) ($aiEngine['model'] ?? $analysis->model ?? 'simulado-mvp')) ?>
                            · <?= View::escape((string) ($aiEngine['prompt_version'] ?? $analysis->promptVersion)) ?>
                            · <?= View::escape((string) ($aiEngine['mode'] ?? 'sin_detalle')) ?>
                        </dd>
                    </dl>
                    </details>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
