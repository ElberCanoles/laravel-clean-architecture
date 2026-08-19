<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeReadModel extends SingleFileGenerator
{
    protected $signature = 'clean:read-model {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create an application read model';

    protected function subPath(): string
    {
        return 'Application/ReadModels';
    }

    protected function stubName(): string
    {
        return 'read-model';
    }

    protected function suffix(): string
    {
        return 'ReadModel';
    }

    protected function label(): string
    {
        return 'Read model';
    }
}
