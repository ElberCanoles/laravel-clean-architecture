<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

class MakeListener extends SingleFileGenerator
{
    protected $signature = 'clean:listener {context} {name} {--event= : Domain event this listener reacts to} {--force : Overwrite existing files}';

    protected $description = 'Create an event listener in the Application layer';

    protected function subPath(): string
    {
        return 'Application/Listeners';
    }

    protected function stubName(): string
    {
        return 'listener';
    }

    protected function suffix(): string
    {
        return 'Listener';
    }

    protected function label(): string
    {
        return 'Listener';
    }

    protected function replacements(string $namespace, string $context, string $name): array
    {
        $event = $this->stringOption('event');

        if ($event === null) {
            return [
                '{{EventImport}}' => '',
                '{{EventType}}' => 'object',
                '{{EventReference}}' => 'YourEvent',
            ];
        }

        $event = $this->cleanName($event, 'event');

        // The event stub appends the Event suffix — do not double it.
        if ($event !== 'Event' && str_ends_with($event, 'Event')) {
            $event = substr($event, 0, -strlen('Event'));
        }

        return [
            '{{EventImport}}' => "use {$namespace}\\Domain\\Events\\{$event}Event;\n\n",
            '{{EventType}}' => "{$event}Event",
            '{{EventReference}}' => "{$event}Event",
        ];
    }
}
