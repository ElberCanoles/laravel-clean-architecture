<?php

declare(strict_types=1);

namespace CleanArchitecture\Console\Concerns;

use CleanArchitecture\Kernel\MarkerBlockWriter;
use Illuminate\Support\Facades\File;

trait WiresContext
{
    protected function wireServiceProviderBindings(string $context, string $name, string $namespace): bool
    {
        $spPath = $this->contextPath($context, "Infrastructure/{$context}ServiceProvider.php");

        if (! File::exists($spPath)) {
            $this->warn('ServiceProvider not found — skipping binding wiring.');

            return true;
        }

        $content = File::get($spPath);

        if (! str_contains($content, '// {bindings}')) {
            $this->warn('No binding markers found in ServiceProvider — skipping wiring.');

            return true;
        }

        // Skip if already wired for this entity. The leading backslash anchors the
        // match so an entity whose name is a suffix of another (User vs SuperUser)
        // is not mistaken for an existing binding.
        if (str_contains($content, "\\{$name}WriteRepository::class")) {
            return true;
        }

        $binding = "\$this->app->bind(\n"
            . "            \\{$namespace}\\Domain\\Repositories\\{$name}WriteRepository::class,\n"
            . "            \\{$namespace}\\Infrastructure\\{$name}WriteEloquentRepository::class,\n"
            . "        );\n"
            . "        \$this->app->bind(\n"
            . "            \\{$namespace}\\Application\\Contracts\\{$name}ReadRepository::class,\n"
            . "            \\{$namespace}\\Infrastructure\\{$name}ReadEloquentRepository::class,\n"
            . '        );';

        $updated = MarkerBlockWriter::insert($content, 'bindings', "        $binding");

        // A PCRE failure (e.g. backtrack limit) returns null; writing it would
        // truncate the user's ServiceProvider to an empty file.
        if ($updated === null) {
            $this->components->error(
                'Could not wire bindings in ServiceProvider (' . preg_last_error_msg() . ') — file left untouched.'
            );

            return false;
        }

        if (File::put($spPath, $updated) === false) {
            $this->components->error("Could not write file: $spPath");

            return false;
        }

        return true;
    }

    protected function wireRoutes(string $context, string $name, string $namespace, ?string $pluralKebab = null): bool
    {
        $routesDir = $this->contextPath($context, 'Presentation/Routes');

        if (! File::isDirectory($routesDir)) {
            return true;
        }

        $plural = $pluralKebab ?? $this->toKebabPlural($name);
        $controllerClass = "{$name}Controller";
        $controllerFqn = "{$namespace}\\Presentation\\Controllers\\{$controllerClass}";
        $wired = true;

        foreach (['api.php', 'web.php'] as $routeFile) {
            $routePath = "$routesDir/$routeFile";

            if (! File::exists($routePath)) {
                continue;
            }

            $content = File::get($routePath);

            if (! str_contains($content, '// {routes}')) {
                $this->warn("No route markers found in $routeFile — skipping route wiring.");

                continue;
            }

            $routeMethod = $routeFile === 'api.php' ? 'apiResource' : 'resource';

            // Skip if already wired — anchored to the resource call so a context
            // prefix that matches the plural (People/people) is not a false hit.
            if (str_contains($content, "Route::{$routeMethod}('{$plural}'")) {
                continue;
            }

            // Add the controller import; fall back to the FQCN in the route line
            // so the file never references a class it does not import.
            $controllerRef = $controllerClass;
            $import = "use $controllerFqn;";

            if (! str_contains($content, $import)) {
                $anchor = 'use Illuminate\Support\Facades\Route;';

                if (str_contains($content, $anchor)) {
                    $content = str_replace($anchor, "$anchor\n$import", $content);
                } elseif (preg_match_all('/^[ \t]*use\s+[^;]+;/m', $content, $useMatches, PREG_OFFSET_CAPTURE) > 0 && $useMatches[0] !== []) {
                    $lastUse = $useMatches[0][array_key_last($useMatches[0])];
                    $position = (int) $lastUse[1] + strlen($lastUse[0]);
                    $content = substr($content, 0, $position) . "\n$import" . substr($content, $position);
                } else {
                    $this->warn("Could not add the controller import to $routeFile — using the fully qualified class name.");
                    $controllerRef = "\\$controllerFqn";
                }
            }

            $route = "    Route::{$routeMethod}('$plural', {$controllerRef}::class);";

            $updated = MarkerBlockWriter::insert($content, 'routes', $route);

            // A PCRE failure returns null; writing it would truncate the routes file.
            if ($updated === null) {
                $this->components->error(
                    "Could not wire routes in $routeFile (" . preg_last_error_msg() . ') — file left untouched.'
                );
                $wired = false;

                continue;
            }

            if (File::put($routePath, $updated) === false) {
                $this->components->error("Could not write file: $routePath");
                $wired = false;
            }
        }

        return $wired;
    }
}
