<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeRequest extends SingleFileGenerator
{
    protected $signature = 'clean:request {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a form request in the Presentation layer';

    protected function subPath(): string
    {
        return 'Presentation/Requests';
    }

    protected function stubName(): string
    {
        return 'request';
    }

    protected function suffix(): string
    {
        return 'Request';
    }

    protected function label(): string
    {
        return 'Request';
    }
}
