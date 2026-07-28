<?php

declare(strict_types=1);

namespace Core;

final class Response
{
    public function __construct(
        private readonly string $content,
        private readonly int $status = 200,
        private readonly array $headers = []
    ) {
    }

    public static function view(string $view, array $data = [], int $httpStatus = 200): self
    {
        $viewPath = dirname(__DIR__) . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewPath)) {
            return new self('View not found', 500);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = (string) ob_get_clean();

        return new self(self::withCsrf($content), $httpStatus);
    }

    public static function redirect(string $location): self
    {
        return new self('', 302, ['Location' => $location]);
    }

    public static function json(array $payload, int $httpStatus = 200): self
    {
        return new self(
            json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}',
            $httpStatus,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }

    private static function withCsrf(string $content): string
    {
        if (stripos($content, '<html') === false) {
            return $content;
        }

        if (preg_match('/<meta\b[^>]*\bname\s*=\s*([\'"])csrf-token\1/i', $content) !== 1) {
            $content = str_ireplace('</head>', '    ' . Csrf::meta() . PHP_EOL . '</head>', $content);
        }

        $field = PHP_EOL . Csrf::field();

        return preg_replace_callback('/<form\b[^>]*>/i', static function (array $matches) use ($field): string {
            $tag = $matches[0];

            if (preg_match('/\bmethod\s*=\s*(["\']?)post\1/i', $tag) !== 1) {
                return $tag;
            }

            return $tag . $field;
        }, $content) ?? $content;
    }
}
