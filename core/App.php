<?php

declare(strict_types=1);

namespace Core;

use App\Middleware\AuthMiddleware;
use App\Services\AuditService;

final class App
{
    public static function run(Router $router): void
    {
        $request = Request::capture();
        $middlewareResponse = (new AuthMiddleware())->handle($request);

        if ($middlewareResponse !== null) {
            $middlewareResponse->send();
            return;
        }

        $response = $router->dispatch($request);
        (new AuditService())->recordRequest($request);
        $response->send();
    }
}
