<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeResource extends SingleFileGenerator
{
    protected $signature = 'clean:resource {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create an API resource in the Presentation layer';

    protected function subPath(): string
    {
        return 'Presentation/Resources';
    }

    protected function stubName(): string
    {
        return 'resource';
    }

    protected function suffix(): string
    {
        return 'Resource';
    }

    protected function label(): string
    {
        return 'Resource';
    }
}
