<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeDomainService extends SingleFileGenerator
{
    protected $signature = 'clean:domain-service {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a domain service';

    protected function subPath(): string
    {
        return 'Domain/Services';
    }

    protected function stubName(): string
    {
        return 'domain-service';
    }

    protected function label(): string
    {
        return 'Domain service';
    }
}
