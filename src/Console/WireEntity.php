<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class WireEntity extends BaseGenerator
{
    use Concerns\WiresContext;

    protected $signature = 'clean:wire {context} {name}';

    protected $description = 'Wire ServiceProvider bindings and resource routes for an entity generated piece by piece';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');

        if (! File::isDirectory($this->contextPath($context))) {
            $this->components->error("Context [$context] does not exist — run clean:context first.");

            return self::INVALID;
        }

        $namespace = $this->buildNamespace($context);
        $wiredBindings = $this->wireServiceProviderBindings($context, $name, $namespace);
        $wiredRoutes = $this->wireRoutes($context, $name, $namespace);

        if (! $wiredBindings || ! $wiredRoutes) {
            return self::FAILURE;
        }

        $this->info("Wiring completed for [$name] in [$context].");

        return self::SUCCESS;
    }
}
