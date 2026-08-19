<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeDomainEvent extends SingleFileGenerator
{
    protected $signature = 'clean:domain-event {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a domain event';

    protected function subPath(): string
    {
        return 'Domain/Events';
    }

    protected function stubName(): string
    {
        return 'domain-event';
    }

    protected function suffix(): string
    {
        return 'Event';
    }

    protected function label(): string
    {
        return 'Domain event';
    }
}
