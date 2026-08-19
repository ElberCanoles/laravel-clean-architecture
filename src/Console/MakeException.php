<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeException extends SingleFileGenerator
{
    protected $signature = 'clean:exception {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a domain exception';

    protected function subPath(): string
    {
        return 'Domain/Exceptions';
    }

    protected function stubName(): string
    {
        return 'domain-exception';
    }

    protected function suffix(): string
    {
        return 'Exception';
    }

    protected function label(): string
    {
        return 'Domain exception';
    }
}
