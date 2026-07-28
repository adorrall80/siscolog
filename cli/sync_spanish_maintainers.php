<?php

declare(strict_types=1);

use Core\Database;
use Database\Seeders\MaintainerSeeder;
use Database\Seeders\PatientSeeder;
use Database\Seeders\UserSeeder;

require dirname(__DIR__) . '/bootstrap/app.php';

$db = Database::connection();

$seeders = [
    MaintainerSeeder::class,
    UserSeeder::class,
    PatientSeeder::class,
];

foreach ($seeders as $seederClass) {
    (new $seederClass())->run();
}

$referenceMaps = [
    'patients.status' => [
        'active' => 'activo',
        'paused' => 'pausado',
        'closed' => 'cerrado',
    ],
    'users.status' => [
        'active' => 'activo',
        'inactive' => 'inactivo',
    ],
    'users.role' => [
        'admin' => 'administrador',
        'professional' => 'profesional',
    ],
    'clinical_sessions.risk_level' => [
        'none' => 'sin_riesgo',
        'low' => 'bajo',
        'medium' => 'medio',
        'high' => 'alto',
        'critical' => 'critico',
    ],
    'patient_consents.consent_type' => [
        'clinical_record' => 'registro_clinico',
        'data_processing' => 'tratamiento_datos',
        'ai_analysis' => 'analisis_ia',
    ],
    'ai_analysis_outputs.review_status' => [
        'pending' => 'pendiente',
        'accepted' => 'aceptado',
        'edited' => 'editado',
        'discarded' => 'descartado',
    ],
];

$maintainerMaps = [
    'patient_statuses' => [
        'active' => 'activo',
        'paused' => 'pausado',
        'closed' => 'cerrado',
    ],
    'risk_levels' => [
        'none' => 'sin_riesgo',
        'low' => 'bajo',
        'medium' => 'medio',
        'high' => 'alto',
        'critical' => 'critico',
    ],
    'consent_types' => [
        'clinical_record' => 'registro_clinico',
        'data_processing' => 'tratamiento_datos',
        'ai_analysis' => 'analisis_ia',
    ],
    'user_roles' => [
        'admin' => 'administrador',
        'professional' => 'profesional',
    ],
    'user_statuses' => [
        'active' => 'activo',
        'inactive' => 'inactivo',
    ],
    'ai_review_statuses' => [
        'pending' => 'pendiente',
        'accepted' => 'aceptado',
        'edited' => 'editado',
        'discarded' => 'descartado',
    ],
];

$db->beginTransaction();

try {
    foreach ($referenceMaps as $target => $map) {
        [$table, $column] = explode('.', $target, 2);

        foreach ($map as $oldCode => $newCode) {
            if (!tableExists($db, $table)) {
                continue;
            }

            $statement = $db->prepare("UPDATE {$table} SET {$column} = :new_code WHERE {$column} = :old_code");
            $statement->execute([
                'new_code' => $newCode,
                'old_code' => $oldCode,
            ]);
        }
    }

    foreach ($maintainerMaps as $table => $map) {
        if (!tableExists($db, $table)) {
            continue;
        }

        foreach ($map as $oldCode => $newCode) {
            $delete = $db->prepare("DELETE FROM {$table} WHERE code = :old_code AND EXISTS (SELECT 1 FROM (SELECT id FROM {$table} WHERE code = :new_code) AS existing)");
            $delete->execute([
                'old_code' => $oldCode,
                'new_code' => $newCode,
            ]);
        }
    }

    $db->commit();
} catch (Throwable $exception) {
    $db->rollBack();
    throw $exception;
}

echo 'Mantenedores sincronizados a codigos en espanol.' . PHP_EOL;

function tableExists(PDO $db, string $table): bool
{
    $statement = $db->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = :table'
    );
    $statement->execute(['table' => $table]);

    return (int) $statement->fetchColumn() > 0;
}
