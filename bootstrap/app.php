<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\' => dirname(__DIR__) . '/app/',
        'Core\\' => dirname(__DIR__) . '/core/',
        'Database\\' => dirname(__DIR__) . '/database/',
    ];

    foreach ($prefixes as $prefix => $basePath) {
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $basePath . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (is_file($file)) {
                require $file;
            }
        }
    }
});

Core\Env::load(dirname(__DIR__) . '/.env');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
