<?php

namespace CleanArchitecture\Console;

class MakeScaffold extends BaseGenerator
{
    protected $signature = 'clean:scaffold {context} {name} {--force}';
    protected $description = 'Scaffold a full entity with repository, read model, CQRS, controller, request, resource, and sanitizer';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $force = $this->option('force');

        $commands = [
            'clean:entity' => [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ],
            'clean:repository' => [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ],
            'clean:read-model' => [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ],
            'clean:command' => [
                'context' => $context,
                'name' => "Create{$name}",
                '--entity' => $name,
                '--force' => $force,
            ],
            'clean:query' => [
                'context' => $context,
                'name' => "Get{$name}",
                '--entity' => $name,
                '--force' => $force,
            ],
            'clean:controller' => [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ],
            'clean:request' => [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ],
            'clean:resource' => [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ],
            'clean:sanitizer' => [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ],
        ];

        foreach ($commands as $command => $arguments) {
            $this->call($command, $arguments);
        }

        $this->info("Scaffold for [$name] in [$context] created successfully.");
    }
}
