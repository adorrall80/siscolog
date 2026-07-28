<?php

declare(strict_types=1);

use Core\Database;
use Database\Seeders\DemoSeeder;
use Database\Seeders\InitialSeeder;

final class DatabaseTool
{
    public static function fresh(): void
    {
        $db = Database::connection();

        echo 'Limpiando base de datos...' . PHP_EOL;

        $db->exec('SET FOREIGN_KEY_CHECKS = 0');

        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $db->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', (string) $table) . '`');
            echo "Dropped: {$table}" . PHP_EOL;
        }

        $db->exec('SET FOREIGN_KEY_CHECKS = 1');

        echo 'Base limpia.' . PHP_EOL;
    }

    public static function migrate(): void
    {
        $db = Database::connection();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $executed = $db->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob(dirname(__DIR__) . '/database/migrations/*.php') ?: [];

        sort($files);

        foreach ($files as $file) {
            $migration = basename($file);

            if (in_array($migration, $executed, true)) {
                continue;
            }

            $definition = require $file;
            $db->exec($definition['up']);

            $statement = $db->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
            $statement->execute(['migration' => $migration]);

            echo "Migrated: {$migration}" . PHP_EOL;
        }

        echo 'Migraciones finalizadas.' . PHP_EOL;
    }

    public static function seed(string $target): void
    {
        if (!self::hasTables()) {
            echo 'No hay tablas creadas. Primero ejecuta la opcion 2: cargar migraciones.' . PHP_EOL;
            return;
        }

        $seeders = match (strtolower($target)) {
            'inicial', 'initial' => [InitialSeeder::class],
            'demo' => [DemoSeeder::class],
            'todo', 'all' => [InitialSeeder::class, DemoSeeder::class],
            default => null,
        };

        if ($seeders === null) {
            echo 'Uso: php www\\cli\\seed.php [inicial|demo|todo]' . PHP_EOL;
            return;
        }

        foreach ($seeders as $seederClass) {
            $seeder = new $seederClass();
            $seeder->run();

            echo "Seeded: {$seederClass}" . PHP_EOL;
        }

        echo 'Seeders finalizados.' . PHP_EOL;
    }

    public static function hasTables(): bool
    {
        $db = Database::connection();
        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        return count($tables) > 0;
    }
}
