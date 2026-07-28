<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';
require __DIR__ . '/DatabaseTool.php';

echo PHP_EOL;
echo 'SisColog - Administrador de BBDD' . PHP_EOL;
echo '--------------------------------' . PHP_EOL;
echo '1. Fresh / borrar BBDD' . PHP_EOL;
echo '2. Cargar migraciones' . PHP_EOL;
echo '3. Cargar datos iniciales' . PHP_EOL;
echo '4. Cargar datos demo' . PHP_EOL;
echo PHP_EOL;
echo 'Puedes ingresar una opcion o varias separadas por coma. Ej: 1,2,3' . PHP_EOL;
echo 'Opcion: ';

$input = trim((string) fgets(STDIN));
$options = array_values(array_filter(array_map('trim', explode(',', $input))));

if ($options === []) {
    echo 'No se selecciono ninguna opcion.' . PHP_EOL;
    exit(1);
}

foreach ($options as $option) {
    echo PHP_EOL;

    match ($option) {
        '1' => DatabaseTool::fresh(),
        '2' => DatabaseTool::migrate(),
        '3' => DatabaseTool::seed('inicial'),
        '4' => DatabaseTool::seed('demo'),
        default => print "Opcion no valida: {$option}" . PHP_EOL,
    };
}

echo PHP_EOL . 'Proceso finalizado.' . PHP_EOL;
