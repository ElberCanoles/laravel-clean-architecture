<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeEnum extends SingleFileGenerator
{
    protected $signature = 'clean:enum {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a backed enum in the Domain layer';

    protected function subPath(): string
    {
        return 'Domain/Enums';
    }

    protected function stubName(): string
    {
        return 'enum';
    }

    protected function label(): string
    {
        return 'Enum';
    }
}
