<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeEntity extends SingleFileGenerator
{
    protected $signature = 'clean:entity {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a domain entity';

    protected function subPath(): string
    {
        return 'Domain/Entities';
    }

    protected function stubName(): string
    {
        return 'entity';
    }

    protected function label(): string
    {
        return 'Entity';
    }
}
