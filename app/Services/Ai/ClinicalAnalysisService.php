<?php

declare(strict_types=1);

namespace App\Services\Ai;

final class ClinicalAnalysisService
{
    public function buildPrompt(array $clinicalContext): string
    {
        $context = json_encode($clinicalContext, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Actua como asistente clinico para un profesional de salud mental.
No diagnostiques de forma definitiva.
No reemplaces el juicio clinico.
Responde solo en JSON valido.

Analiza el siguiente contexto clinico:
{$context}

Incluye:
- resumen_clinico
- temas_recurrentes
- hipotesis_clinicas
- factores_riesgo
- factores_protectores
- alertas
- preguntas_sugeridas
- limites_del_analisis
PROMPT;
    }
}
