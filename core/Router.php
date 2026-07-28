<?php

declare(strict_types=1);

namespace Core;

final class Router
{
    private array $routes = [];

    public function get(string $uri, array $action): void
    {
        $this->add('GET', $uri, $action);
    }

    public function post(string $uri, array $action): void
    {
        $this->add('POST', $uri, $action);
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->match($request);

        if ($route === null) {
            return Response::view('errors.not_found', [
                'errorTitle' => 'Página no encontrada',
                'errorMessage' => 'La dirección ingresada no corresponde a una página disponible en SisColog.',
                'backUrl' => '/',
                'backLabel' => 'Volver al menú',
            ], 404);
        }

        [$controllerClass, $method, $request] = $route;
        $controller = new $controllerClass();

        return $controller->{$method}($request);
    }

    private function add(string $method, string $uri, array $action): void
    {
        $this->routes[$method][] = [
            'uri' => '/' . trim($uri, '/'),
            'action' => $action,
        ];
    }

    private function match(Request $request): ?array
    {
        foreach ($this->routes[$request->method] ?? [] as $route) {
            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['uri']);
            $pattern = '#^' . $pattern . '$#';

            if (!preg_match($pattern, $request->uri, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn (string|int $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            return [
                $route['action'][0],
                $route['action'][1],
                $request->withParams($params),
            ];
        }

        return null;
    }
}
