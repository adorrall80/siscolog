<?php

use Core\View;

$metrics ??= [];
$recentSessions ??= [];
$pendingConsents ??= [];
$pendingAiReviews ??= [];
$riskAlerts ??= [];
$instrumentAlerts ??= [];

ob_start();
?>
<section class="panel dashboard-panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Panel operativo</p>
            <h1>Resumen del sistema</h1>
            <p>Indicadores rapidos para revisar actividad clinica y pendientes.</p>
        </div>
        <a class="button small" href="/">Ver menu</a>
    </div>

    <div class="summary-grid">
        <article class="summary-card">
            <span>Pacientes</span>
            <strong><?= View::escape((string) ($metrics['patients'] ?? 0)) ?></strong>
            <small><?= View::escape((string) ($metrics['activePatients'] ?? 0)) ?> activos</small>
        </article>

        <article class="summary-card">
            <span>Sesiones</span>
            <strong><?= View::escape((string) ($metrics['sessions'] ?? 0)) ?></strong>
            <small><?= View::escape((string) ($metrics['activeSessions'] ?? 0)) ?> activas para evolucion IA</small>
        </article>

        <article class="summary-card">
            <span>Consentimientos IA</span>
            <strong><?= View::escape((string) ($metrics['pendingConsents'] ?? 0)) ?></strong>
            <small>pacientes pendientes de consentimiento IA</small>
        </article>

        <article class="summary-card">
            <span>Revision IA</span>
            <strong><?= View::escape((string) ($metrics['pendingAiOutputs'] ?? 0)) ?></strong>
            <small>salidas IA pendientes de revision</small>
        </article>

        <article class="summary-card">
            <span>Alertas</span>
            <strong><?= View::escape((string) (($metrics['riskAlerts'] ?? 0) + ($metrics['instrumentAlerts'] ?? 0))) ?></strong>
            <small>riesgo clinico o instrumentos en seguimiento</small>
        </article>
    </div>

    <div class="dashboard-workgrid">
        <article class="detail-card">
            <span class="tag tone-blue">Actividad</span>
            <h2>Sesiones recientes</h2>
            <?php if ($recentSessions === []): ?>
                <p>No hay sesiones recientes en tu ambito de trabajo.</p>
            <?php else: ?>
                <div class="dashboard-list">
                    <?php foreach ($recentSessions as $session): ?>
                        <a href="/citas/<?= View::escape((string) $session['id']) ?>">
                            <strong><?= View::escape($session['patient_name']) ?></strong>
                            <span><?= View::escape($session['session_date']) ?> · <?= View::escape($session['risk_level']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="detail-card">
            <span class="tag tone-amber">Pendiente</span>
            <h2>Consentimiento IA</h2>
            <?php if ($pendingConsents === []): ?>
                <p>Todos los pacientes visibles tienen consentimiento IA activo o no hay pacientes vigentes.</p>
            <?php else: ?>
                <div class="dashboard-list">
                    <?php foreach ($pendingConsents as $patient): ?>
                        <a href="/patients/<?= View::escape((string) $patient['id']) ?>/consents/create">
                            <strong><?= View::escape($patient['full_name']) ?></strong>
                            <span><?= View::escape($patient['code']) ?> · registrar consentimiento</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="detail-card">
            <span class="tag tone-amber">IA</span>
            <h2>Revision profesional</h2>
            <?php if ($pendingAiReviews === []): ?>
                <p>No hay salidas IA pendientes de revision.</p>
            <?php else: ?>
                <div class="dashboard-list">
                    <?php foreach ($pendingAiReviews as $analysis): ?>
                        <a href="/patients/<?= View::escape((string) $analysis['patient_id']) ?>/ai-history">
                            <strong><?= View::escape($analysis['patient_name']) ?></strong>
                            <span><?= View::escape((string) $analysis['created_at']) ?> · revisar IA</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="detail-card">
            <span class="tag tone-red">Riesgo</span>
            <h2>Sesiones con riesgo alto</h2>
            <?php if ($riskAlerts === []): ?>
                <p>No hay sesiones activas con riesgo alto o critico.</p>
            <?php else: ?>
                <div class="dashboard-list">
                    <?php foreach ($riskAlerts as $alert): ?>
                        <a href="/citas/<?= View::escape((string) $alert['id']) ?>">
                            <strong><?= View::escape($alert['patient_name']) ?></strong>
                            <span><?= View::escape($alert['session_date']) ?> · <?= View::escape($alert['risk_level']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="detail-card">
            <span class="tag tone-red">Instrumentos</span>
            <h2>Puntajes en seguimiento</h2>
            <?php if ($instrumentAlerts === []): ?>
                <p>No hay instrumentos vigentes sobre punto de corte de seguimiento.</p>
            <?php else: ?>
                <div class="dashboard-list">
                    <?php foreach ($instrumentAlerts as $alert): ?>
                        <?php $tone = ((int) ($alert['critical_cutoff'] ?? 0) > 0 && (int) $alert['score'] >= (int) $alert['critical_cutoff']) ? 'critico' : 'seguimiento'; ?>
                        <a href="/patients/<?= View::escape((string) $alert['patient_id']) ?>#instrumentos">
                            <strong><?= View::escape($alert['patient_name']) ?></strong>
                            <span><?= View::escape($alert['instrument_name']) ?> <?= View::escape((string) $alert['score']) ?>/<?= View::escape((string) $alert['max_score']) ?> · <?= View::escape($tone) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="detail-card">
            <span class="tag tone-green">Accesos</span>
            <h2>Trabajo rapido</h2>
            <p>Atajos para continuar el flujo clinico diario.</p>
            <div class="table-actions">
                <a class="button small secondary" href="/patients">Pacientes</a>
                <a class="button small secondary" href="/citas">Citas</a>
                <a class="button small secondary" href="/patients/create">Nuevo paciente</a>
            </div>
        </article>
    </div>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
