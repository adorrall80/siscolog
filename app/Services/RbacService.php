<?php

declare(strict_types=1);

namespace App\Services;

use Core\Request;

final class RbacService
{
    public function canAccess(Request $request, string $role): bool
    {
        if ($role === 'administrador') {
            return true;
        }

        if (!in_array($role, ['profesional', 'supervisor'], true)) {
            return false;
        }

        if ($role === 'profesional') {
            return $this->matchesAny($request, $this->professionalRules());
        }

        return $this->matchesAny($request, $this->supervisorRules());
    }

    private function professionalRules(): array
    {
        return [
            ['*', '#^/$#'],
            ['GET', '#^/panel$#'],
            ['GET', '#^/logout$#'],
            ['GET', '#^/perfil/profesional$#'],
            ['POST', '#^/perfil/profesional/actualizar$#'],
            ['GET', '#^/patients(?:/.*)?$#'],
            ['POST', '#^/patients$#'],
            ['POST', '#^/patients/\d+/update$#'],
            ['GET', '#^/patients/\d+/(activar|desactivar)$#'],
            ['GET', '#^/citas(?:/.*)?$#'],
            ['POST', '#^/citas/\d+/actualizar$#'],
            ['GET', '#^/informes$#'],
            ['GET', '#^/patients/\d+/reporte(?:/exportar)?$#'],
            ['POST', '#^/patients/\d+/sessions$#'],
            ['GET', '#^/patients/\d+/sessions/\d+/(activar|desactivar)$#'],
            ['POST', '#^/patients/\d+/consents$#'],
            ['GET', '#^/patients/\d+/consents/\d+/(activar|desactivar)$#'],
            ['POST', '#^/patients/\d+/instrumentos$#'],
            ['GET', '#^/patients/\d+/instrumentos(?:/.*)?$#'],
            ['POST', '#^/patients/\d+/instrumentos/\d+/actualizar$#'],
            ['POST', '#^/patients/\d+/ai-analysis$#'],
            ['GET', '#^/patients/\d+/ai-history$#'],
            ['POST', '#^/patients/\d+/ai-analysis/\d+/review$#'],
            ['GET', '#^/patients/\d+/ai-analysis/\d+/(activar|desactivar)$#'],
            ['GET', '#^/patients/\d+/vinculos$#'],
            ['GET', '#^/patients/\d+/vinculos/pdf$#'],
            ['POST', '#^/patients/\d+/family-people$#'],
            ['POST', '#^/patients/\d+/family-people/\d+/desactivar$#'],
            ['POST', '#^/patients/\d+/family-relationships$#'],
            ['POST', '#^/patients/\d+/family-node-position$#'],
            ['POST', '#^/patients/\d+/family-relationships/\d+/desactivar$#'],
            ['GET', '#^/patients/\d+/family-relationships/\d+/desactivar$#'],
        ];
    }

    private function supervisorRules(): array
    {
        return [
            ['*', '#^/$#'],
            ['GET', '#^/panel$#'],
            ['GET', '#^/logout$#'],
            ['GET', '#^/patients$#'],
            ['GET', '#^/patients/\d+$#'],
            ['GET', '#^/patients/\d+/vinculos$#'],
            ['GET', '#^/patients/\d+/vinculos/pdf$#'],
            ['GET', '#^/patients/\d+/ai-history$#'],
            ['POST', '#^/patients/\d+/ai-analysis/\d+/review$#'],
            ['GET', '#^/citas(?:/\d+)?$#'],
            ['GET', '#^/informes$#'],
            ['GET', '#^/patients/\d+/reporte(?:/exportar)?$#'],
        ];
    }

    private function matchesAny(Request $request, array $rules): bool
    {
        foreach ($rules as [$method, $pattern]) {
            if ($method !== '*' && $method !== $request->method) {
                continue;
            }

            if (preg_match($pattern, $request->uri) === 1) {
                return true;
            }
        }

        return false;
    }
}
