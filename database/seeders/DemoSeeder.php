<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database;

final class DemoSeeder
{
    public function run(): void
    {
        if (!$this->hasInitialData()) {
            echo 'El seeder demo requiere datos iniciales. Ejecuta primero la opcion 3: cargar datos iniciales.' . PHP_EOL;
            return;
        }

        (new PatientSeeder())->run();
    }

    private function hasInitialData(): bool
    {
        $db = Database::connection();
        $checks = [
            ['patient_statuses', 'code', 'activo'],
            ['consent_types', 'code', 'analisis_ia'],
            ['session_modalities', 'code', 'presencial'],
            ['risk_levels', 'code', 'bajo'],
            ['session_topic_types', 'code', 'motivo_consulta'],
            ['session_topic_subtypes', 'name', 'Ansiedad'],
        ];

        foreach ($checks as [$table, $column, $value]) {
            $statement = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = :value");
            $statement->execute(['value' => $value]);

            if ((int) $statement->fetchColumn() === 0) {
                return false;
            }
        }

        $statement = $db->query('SELECT COUNT(*) FROM session_topic_type_subtypes');

        return (int) $statement->fetchColumn() > 0;
    }
}
