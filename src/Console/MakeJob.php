<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeJob extends SingleFileGenerator
{
    protected $signature = 'clean:job {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a queued job in the Infrastructure layer';

    protected function subPath(): string
    {
        return 'Infrastructure/Jobs';
    }

    protected function stubName(): string
    {
        return 'job';
    }

    protected function suffix(): string
    {
        return 'Job';
    }

    protected function label(): string
    {
        return 'Job';
    }
}
