<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeSanitizer extends SingleFileGenerator
{
    protected $signature = 'clean:sanitizer {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a sanitizer in the Application layer';

    protected function subPath(): string
    {
        return 'Application/Sanitizers';
    }

    protected function stubName(): string
    {
        return 'sanitizer';
    }

    protected function suffix(): string
    {
        return 'Sanitizer';
    }

    protected function label(): string
    {
        return 'Sanitizer';
    }
}
