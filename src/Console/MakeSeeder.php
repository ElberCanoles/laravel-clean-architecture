<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeSeeder extends SingleFileGenerator
{
    protected $signature = 'clean:seeder {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a database seeder in the Infrastructure layer';

    protected function subPath(): string
    {
        return 'Infrastructure/Database/Seeders';
    }

    protected function stubName(): string
    {
        return 'seeder';
    }

    protected function suffix(): string
    {
        return 'Seeder';
    }

    protected function label(): string
    {
        return 'Seeder';
    }
}
