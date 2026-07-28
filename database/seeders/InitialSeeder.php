<?php

declare(strict_types=1);

namespace Database\Seeders;

final class InitialSeeder
{
    public function run(): void
    {
        (new MaintainerSeeder())->run();
        (new PsychometricInstrumentSeeder())->run();
        (new UserSeeder())->run();
    }
}
