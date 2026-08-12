<?php

declare(strict_types=1);

namespace App\Services;

use Core\Env;
use RuntimeException;

final class AiProviderService
{
    private const PROMPT_VERSION = 'clinical-evolution-v3-professional';

    public function configured(): bool
    {
        return $this->provider() !== '' && $this->apiKey() !== '';
    }

    public function provider(): string
    {
        return strtolower(trim((string) Env::get('AI_PROVIDER', '')));
    }

    public function model(): string
    {
        return trim((string) Env::get('AI_MODEL', '')) ?: 'gpt-4.1-mini';
    }

    public function promptVersion(): string
    {
        return self::PROMPT_VERSION;
    }

    public function promptForReview(array $context): string
    {
        return $this->promptTemplate()
            . "\n\nCONTEXTO CLINICO ENVIADO:\n"
            . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function generateClinicalEvolution(array $context): ?array
    {
        if (!$this->configured()) {
            return null;
        }

        if ($this->provider() !== 'openai') {
            throw new RuntimeException('Proveedor IA no soportado. Usa AI_PROVIDER=openai o deja AI_API_KEY vacio para modo MVP local.');
        }

        return $this->callOpenAi($context);
    }

    private function callOpenAi(array $context): array
    {
        $payload = [
            'model' => $this->model(),
            'input' => [
                [
                    'role' => 'system',
                    'content' => $this->promptTemplate(),
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_object',
                ],
            ],
        ];

        $response = file_get_contents('https://api.openai.com/v1/responses', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey(),
                ]),
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'ignore_errors' => true,
                'timeout' => 40,
            ],
        ]));

        if ($response === false) {
            throw new RuntimeException('No se pudo conectar con el proveedor IA.');
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('El proveedor IA retorno una respuesta no valida.');
        }

        if (isset($decoded['error'])) {
            $message = is_array($decoded['error'])
                ? (string) ($decoded['error']['message'] ?? 'Error desconocido.')
                : (string) $decoded['error'];
            throw new RuntimeException('El proveedor IA rechazo la solicitud: ' . $message);
        }

        $text = $this->extractOutputText($decoded);
        $output = json_decode($text, true);

        if (!is_array($output)) {
            throw new RuntimeException('La IA no retorno JSON clinico valido.');
        }

        $output['motor_ia'] = [
            'provider' => $this->provider(),
            'model' => $this->model(),
            'prompt_version' => self::PROMPT_VERSION,
            'mode' => 'api',
        ];

        return $output;
    }

    private function extractOutputText(array $decoded): string
    {
        if (isset($decoded['output_text']) && is_string($decoded['output_text'])) {
            return $decoded['output_text'];
        }

        foreach (($decoded['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                if (isset($content['text']) && is_string($content['text'])) {
                    return $content['text'];
                }
            }
        }

        throw new RuntimeException('La respuesta IA no contiene texto de salida.');
    }

    private function promptTemplate(): string
    {
        $path = dirname(__DIR__, 2) . '/resources/prompts/clinical_evolution_v2.txt';

        if (is_file($path)) {
            return (string) file_get_contents($path);
        }

        return 'Devuelve exclusivamente JSON clinico supervisado. No diagnostiques. No reemplaces criterio profesional.';
    }

    private function apiKey(): string
    {
        return trim((string) Env::get('AI_API_KEY', ''));
    }
}
