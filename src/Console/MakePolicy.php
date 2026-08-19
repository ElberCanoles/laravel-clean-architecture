<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakePolicy extends SingleFileGenerator
{
    protected $signature = 'clean:policy {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create an authorization policy in the Presentation layer';

    protected function subPath(): string
    {
        return 'Presentation/Policies';
    }

    protected function stubName(): string
    {
        return 'policy';
    }

    protected function suffix(): string
    {
        return 'Policy';
    }

    protected function label(): string
    {
        return 'Policy';
    }
}
