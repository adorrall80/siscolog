<?php

use Core\View;

$logs ??= [];
$actions ??= [];
$entityTypes ??= [];
$users ??= [];
$filters ??= ['q' => '', 'action' => '', 'entity_type' => '', 'user_id' => 0, 'date_from' => '', 'date_to' => ''];

$actionLabels = [
    'login_exitoso' => 'Login exitoso',
    'login_fallido' => 'Login fallido',
    'logout' => 'Logout',
    'ver_paciente' => 'Ver paciente',
    'crear_paciente' => 'Crear paciente',
    'editar_paciente' => 'Editar paciente',
    'cambiar_vigencia_paciente' => 'Cambiar vigencia paciente',
    'crear_sesion' => 'Crear sesion',
    'editar_sesion' => 'Editar sesion',
    'ver_sesion' => 'Ver sesion',
    'cambiar_vigencia_sesion' => 'Cambiar vigencia sesion',
    'crear_consentimiento' => 'Crear consentimiento',
    'cambiar_vigencia_consentimiento' => 'Cambiar vigencia consentimiento',
    'registrar_instrumento_psicometrico' => 'Registrar instrumento psicometrico',
    'editar_resultado_psicometrico' => 'Editar resultado psicometrico',
    'cambiar_vigencia_instrumento_psicometrico' => 'Cambiar vigencia instrumento psicometrico',
    'editar_instrumento_psicometrico' => 'Editar instrumento psicometrico',
    'cambiar_vigencia_instrumento' => 'Cambiar vigencia instrumento',
    'ver_centro_informes' => 'Ver centro de informes',
    'ver_reporte_clinico' => 'Ver reporte clinico',
    'exportar_reporte_clinico' => 'Exportar reporte clinico',
    'generar_analisis_ia' => 'Generar analisis IA',
    'ver_historial_ia' => 'Ver historial IA',
    'revisar_analisis_ia' => 'Revisar analisis IA',
    'cambiar_vigencia_analisis_ia' => 'Cambiar vigencia analisis IA',
    'ver_vinculos' => 'Ver vinculos',
    'agregar_persona_vinculo' => 'Agregar persona a vinculos',
    'agregar_vinculo' => 'Agregar vinculo',
    'quitar_vinculo' => 'Quitar vinculo',
    'crear_usuario' => 'Crear usuario',
    'editar_usuario' => 'Editar usuario',
    'cambiar_vigencia_usuario' => 'Cambiar vigencia usuario',
];

$entityLabels = [
    'auth' => 'Autenticacion',
    'patient' => 'Paciente',
    'session' => 'Sesion',
    'consent' => 'Consentimiento',
    'psychometric_result' => 'Resultado psicometrico',
    'psychometric_instrument' => 'Instrumento psicometrico',
    'report' => 'Informe',
    'ai_analysis' => 'Analisis IA',
    'family_relationship' => 'Vinculo',
    'user' => 'Usuario',
];

ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Seguridad y trazabilidad</p>
            <h1>Auditoria del sistema</h1>
            <p>Registro de accesos y acciones sensibles. No guarda notas clinicas completas, solo trazabilidad de operacion.</p>
        </div>
    </div>

    <form class="inline-search" method="get" action="/auditoria">
        <label>
            Buscar
            <input name="q" value="<?= View::escape((string) ($filters['q'] ?? '')) ?>" placeholder="Usuario, accion o entidad">
        </label>
        <label>
            Usuario
            <select name="user_id">
                <option value="0">Todos los usuarios</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= View::escape((string) $user->id) ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $user->id ? 'selected' : '' ?>>
                        <?= View::escape($user->name) ?> · <?= View::escape($user->email) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Accion
            <select name="action">
                <option value="">Todas las acciones</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= View::escape($action) ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>>
                        <?= View::escape($actionLabels[$action] ?? $action) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Entidad
            <select name="entity_type">
                <option value="">Todas las entidades</option>
                <?php foreach ($entityTypes as $entityType): ?>
                    <option value="<?= View::escape($entityType) ?>" <?= ($filters['entity_type'] ?? '') === $entityType ? 'selected' : '' ?>>
                        <?= View::escape($entityLabels[$entityType] ?? $entityType) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Desde
            <input type="date" name="date_from" value="<?= View::escape((string) ($filters['date_from'] ?? '')) ?>">
        </label>
        <label>
            Hasta
            <input type="date" name="date_to" value="<?= View::escape((string) ($filters['date_to'] ?? '')) ?>">
        </label>
        <button class="button small" type="submit">Filtrar</button>
        <a class="button small secondary" href="/auditoria">Limpiar</a>
    </form>

    <?php if ($logs === []): ?>
        <div class="clinical-alert" role="status">
            <span class="clinical-alert-icon" aria-hidden="true">i</span>
            <div>
                <strong>Sin registros de auditoria</strong>
                <p>Cuando se realicen acciones sensibles, apareceran aqui.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Accion</th>
                        <th>Entidad</th>
                        <th>IP</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <strong><?= View::escape((string) $log->createdAt) ?></strong>
                            </td>
                            <td>
                                <?= View::escape($log->userName ?? 'Sistema / anonimo') ?>
                                <?php if ($log->userId !== null): ?>
                                    <small>ID usuario: <?= View::escape((string) $log->userId) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="tag"><?= View::escape($actionLabels[$log->action] ?? $log->action) ?></span>
                            </td>
                            <td>
                                <?= View::escape($entityLabels[$log->entityType] ?? $log->entityType) ?>
                                <?php if ($log->entityId !== null): ?>
                                    <small>ID: <?= View::escape((string) $log->entityId) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= View::escape($log->ipAddress ?? 'Sin IP') ?>
                            </td>
                            <td>
                                <small><?= View::escape((string) ($log->metadata['method'] ?? '')) ?> <?= View::escape((string) ($log->metadata['uri'] ?? '')) ?></small>
                                <?php if ($log->userAgent !== null && $log->userAgent !== ''): ?>
                                    <small title="<?= View::escape($log->userAgent) ?>"><?= View::escape(substr($log->userAgent, 0, 70)) ?></small>
                                <?php endif; ?>
                                <details class="audit-metadata">
                                    <summary>Ver metadata</summary>
                                    <pre><?= View::escape(json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}') ?></pre>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
