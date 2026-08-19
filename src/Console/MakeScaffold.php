<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use CleanArchitecture\Kernel\MarkerBlockWriter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeScaffold extends BaseGenerator
{
    protected $signature = 'clean:scaffold {context} {name} {--id-type= : Primary key type (uuid, ulid)} {--force : Overwrite existing files}';

    protected $description = 'Scaffold a full entity with repository, read model, CQRS, controller, request, resource, and sanitizer';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');

        $force = $this->option('force');
        $plural = $this->toPluralStudly($name);

        // Resolved once so every generated file shares the same identifier strategy.
        $idType = $this->resolveIdType();

        $commands = [
            ['clean:entity', [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ]],
            ['clean:model', [
                'context' => $context,
                'name' => $name,
                '--id-type' => $idType,
                '--force' => $force,
            ]],
            ['clean:repository', [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ]],
            ['clean:read-model', [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ]],
            ['clean:command', [
                'context' => $context,
                'name' => "Create{$name}",
                '--entity' => $name,
                '--crud' => 'create',
                '--id-type' => $idType,
                '--force' => $force,
            ]],
            ['clean:command', [
                'context' => $context,
                'name' => "Update{$name}",
                '--entity' => $name,
                '--crud' => 'update',
                '--force' => $force,
            ]],
            ['clean:command', [
                'context' => $context,
                'name' => "Delete{$name}",
                '--entity' => $name,
                '--crud' => 'delete',
                '--force' => $force,
            ]],
            ['clean:query', [
                'context' => $context,
                'name' => "Get{$name}",
                '--entity' => $name,
                '--force' => $force,
            ]],
            ['clean:query', [
                'context' => $context,
                'name' => "List{$plural}",
                '--entity' => $name,
                '--collection' => true,
                '--force' => $force,
            ]],
            ['clean:controller', [
                'context' => $context,
                'name' => $name,
                '--entity' => $name,
                '--id-type' => $idType,
                '--force' => $force,
            ]],
            ['clean:request', [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ]],
            ['clean:resource', [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ]],
            ['clean:sanitizer', [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ]],
        ];

        $failed = false;

        foreach ($commands as [$command, $arguments]) {
            if ($this->call($command, $arguments) !== self::SUCCESS) {
                $failed = true;
            }
        }

        $namespace = $this->buildNamespace($context);
        $wiredBindings = $this->wireServiceProviderBindings($context, $name, $namespace);
        $wiredRoutes = $this->wireRoutes($context, $name, $namespace);
        $migrated = $this->generateMigration($name, $idType);

        if ($failed || ! $wiredBindings || ! $wiredRoutes || ! $migrated) {
            $this->warn("Scaffold for [$name] in [$context] completed with warnings — review the output above.");

            return self::FAILURE;
        }

        $this->info("Scaffold for [$name] in [$context] created successfully.");

        return self::SUCCESS;
    }

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

    protected function wireRoutes(string $context, string $name, string $namespace): bool
    {
        $routesDir = $this->contextPath($context, 'Presentation/Routes');

        if (! File::isDirectory($routesDir)) {
            return true;
        }

        $plural = $this->toKebabPlural($name);
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

            // Skip if already wired
            if (str_contains($content, "'$plural'")) {
                continue;
            }

            $routeMethod = $routeFile === 'api.php' ? 'apiResource' : 'resource';

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

    protected function generateMigration(string $name, string $idType): bool
    {
        $table = Str::snake(Str::pluralStudly($name));
        $migrationPath = database_path('migrations');

        File::makeDirectory($migrationPath, 0755, true, true);

        $existing = File::glob("$migrationPath/*_create_{$table}_table.php");

        if (! empty($existing)) {
            if (! $this->option('force')) {
                $this->warn("Migration already exists for table '$table' (use --force to overwrite).");

                return false;
            }

            // Overwrite in place — a fresh timestamp would stack a second
            // migration for the same table and break `php artisan migrate`.
            $file = $existing[0];
        } else {
            $file = "$migrationPath/" . date('Y_m_d_His') . "_create_{$table}_table.php";
        }

        $stub = $this->getStub('migration');
        $this->warnIfStubIgnoresIdType($stub, 'migration', '{{idType}}');

        $content = str_replace(
            ['{{table}}', '{{idType}}'],
            [$table, $idType],
            $stub
        );

        if (File::put($file, $content) === false) {
            $this->components->error("Could not write file: $file");

            return false;
        }

        $this->info("Migration created: $file");

        return true;
    }
}
