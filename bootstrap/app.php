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
    $sessionPath = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'siscolog_sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }
    session_save_path($sessionPath);
    session_start();
}
