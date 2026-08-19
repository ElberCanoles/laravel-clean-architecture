<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeValueObject extends SingleFileGenerator
{
    protected $signature = 'clean:value-object {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a domain value object';

    protected function subPath(): string
    {
        return 'Domain/ValueObjects';
    }

    protected function stubName(): string
    {
        return 'value-object';
    }

    protected function label(): string
    {
        return 'Value object';
    }
}
