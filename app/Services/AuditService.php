<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use Core\Request;
use Core\Session;
use Throwable;

final class AuditService
{
    private AuditLogRepository $logs;

    public function __construct()
    {
        $this->logs = new AuditLogRepository();
    }

    public function record(string $action, string $entityType, ?int $entityId, Request $request, ?array $user = null, array $metadata = []): void
    {
        try {
            $currentUser = $user ?? $this->currentUser();

            $this->logs->create([
                'user_id' => (int) ($currentUser['id'] ?? 0) ?: null,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => $this->ipAddress($request),
                'user_agent' => substr((string) $request->header('User-Agent', ''), 0, 255),
                'metadata' => $metadata + [
                    'method' => $request->method,
                    'uri' => $request->uri,
                ],
            ]);
        } catch (Throwable) {
            // La auditoria no debe romper la operacion clinica principal.
        }
    }

    public function recordRequest(Request $request): void
    {
        $user = $this->currentUser();

        if ($user === []) {
            return;
        }

        $event = $this->eventFor($request);

        if ($event === null) {
            return;
        }

        $this->record(
            $event['action'],
            $event['entity_type'],
            $event['entity_id'],
            $request,
            $user,
            ['source' => 'route']
        );
    }

    /**
     * @return array{logs: array, actions: string[], entityTypes: string[]}
     */
    public function dashboard(array $filters): array
    {
        return [
            'logs' => $this->logs->latest($filters),
            'actions' => $this->logs->distinctActions(),
            'entityTypes' => $this->logs->distinctEntityTypes(),
        ];
    }

    /**
     * @return array{action: string, entity_type: string, entity_id: ?int}|null
     */
    private function eventFor(Request $request): ?array
    {
        $method = $request->method;
        $uri = $request->uri;

        $rules = [
            ['GET', '#^/patients/(\d+)$#', 'ver_paciente', 'patient', 1],
            ['POST', '#^/patients$#', 'crear_paciente', 'patient', null],
            ['POST', '#^/patients/(\d+)/update$#', 'editar_paciente', 'patient', 1],
            ['GET', '#^/patients/(\d+)/(activar|desactivar)$#', 'cambiar_vigencia_paciente', 'patient', 1],
            ['POST', '#^/patients/(\d+)/sessions$#', 'crear_sesion', 'patient', 1],
            ['GET', '#^/patients/(\d+)/sessions/(\d+)/(activar|desactivar)$#', 'cambiar_vigencia_sesion', 'session', 2],
            ['GET', '#^/citas/(\d+)$#', 'ver_sesion', 'session', 1],
            ['POST', '#^/citas/(\d+)/actualizar$#', 'editar_sesion', 'session', 1],
            ['POST', '#^/patients/(\d+)/consents$#', 'crear_consentimiento', 'patient', 1],
            ['GET', '#^/patients/(\d+)/consents/(\d+)/(activar|desactivar)$#', 'cambiar_vigencia_consentimiento', 'consent', 2],
            ['POST', '#^/patients/(\d+)/instrumentos$#', 'registrar_instrumento_psicometrico', 'patient', 1],
            ['POST', '#^/patients/(\d+)/instrumentos/(\d+)/actualizar$#', 'editar_resultado_psicometrico', 'psychometric_result', 2],
            ['GET', '#^/patients/(\d+)/instrumentos/(\d+)/(activar|desactivar)$#', 'cambiar_vigencia_instrumento_psicometrico', 'psychometric_result', 2],
            ['POST', '#^/instrumentos/(\d+)/actualizar$#', 'editar_instrumento_psicometrico', 'psychometric_instrument', 1],
            ['GET', '#^/instrumentos/(\d+)/(activar|desactivar)$#', 'cambiar_vigencia_instrumento', 'psychometric_instrument', 1],
            ['GET', '#^/informes$#', 'ver_centro_informes', 'report', null],
            ['GET', '#^/patients/(\d+)/reporte$#', 'ver_reporte_clinico', 'patient', 1],
            ['GET', '#^/patients/(\d+)/reporte/exportar$#', 'exportar_reporte_clinico', 'patient', 1],
            ['POST', '#^/patients/(\d+)/ai-analysis$#', 'generar_analisis_ia', 'patient', 1],
            ['GET', '#^/patients/(\d+)/ai-history$#', 'ver_historial_ia', 'patient', 1],
            ['POST', '#^/patients/(\d+)/ai-analysis/(\d+)/review$#', 'revisar_analisis_ia', 'ai_analysis', 2],
            ['GET', '#^/patients/(\d+)/ai-analysis/(\d+)/(activar|desactivar)$#', 'cambiar_vigencia_analisis_ia', 'ai_analysis', 2],
            ['GET', '#^/patients/(\d+)/vinculos$#', 'ver_vinculos', 'patient', 1],
            ['POST', '#^/patients/(\d+)/family-people$#', 'agregar_persona_vinculo', 'patient', 1],
            ['POST', '#^/patients/(\d+)/family-relationships$#', 'agregar_vinculo', 'patient', 1],
            ['POST', '#^/patients/(\d+)/family-relationships/(\d+)/desactivar$#', 'quitar_vinculo', 'family_relationship', 2],
            ['GET', '#^/patients/(\d+)/family-relationships/(\d+)/desactivar$#', 'quitar_vinculo', 'family_relationship', 2],
            ['POST', '#^/usuarios$#', 'crear_usuario', 'user', null],
            ['POST', '#^/usuarios/(\d+)/actualizar$#', 'editar_usuario', 'user', 1],
            ['GET', '#^/usuarios/(\d+)/(activar|desactivar)$#', 'cambiar_vigencia_usuario', 'user', 1],
        ];

        foreach ($rules as [$ruleMethod, $pattern, $action, $entityType, $entityGroup]) {
            if ($method !== $ruleMethod || preg_match($pattern, $uri, $matches) !== 1) {
                continue;
            }

            return [
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityGroup === null ? null : (int) $matches[$entityGroup],
            ];
        }

        return null;
    }

    private function ipAddress(Request $request): ?string
    {
        $forwarded = trim((string) $request->header('X-Forwarded-For', ''));

        if ($forwarded !== '') {
            return substr(trim(explode(',', $forwarded)[0]), 0, 45);
        }

        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null;
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
