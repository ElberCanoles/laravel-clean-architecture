<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeSpecification extends SingleFileGenerator
{
    protected $signature = 'clean:specification {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a domain specification';

    protected function subPath(): string
    {
        return 'Domain/Specifications';
    }

    protected function stubName(): string
    {
        return 'specification';
    }

    protected function suffix(): string
    {
        return 'Specification';
    }

    protected function label(): string
    {
        return 'Specification';
    }
}
