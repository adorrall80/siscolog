<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';
require __DIR__ . '/DatabaseTool.php';

$target = strtolower((string) ($argv[1] ?? 'inicial'));

DatabaseTool::seed($target);
