<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeFactory extends SingleFileGenerator
{
    protected $signature = 'clean:factory {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a model factory in the Infrastructure layer';

    protected function subPath(): string
    {
        return 'Infrastructure/Database/Factories';
    }

    protected function stubName(): string
    {
        return 'factory';
    }

    protected function suffix(): string
    {
        return 'ModelFactory';
    }

    protected function label(): string
    {
        return 'Factory';
    }
}
