<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeMapper extends SingleFileGenerator
{
    protected $signature = 'clean:mapper {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create an entity-model mapper in the Infrastructure layer';

    protected function subPath(): string
    {
        return 'Infrastructure';
    }

    protected function stubName(): string
    {
        return 'mapper';
    }

    protected function suffix(): string
    {
        return 'Mapper';
    }

    protected function label(): string
    {
        return 'Mapper';
    }
}
