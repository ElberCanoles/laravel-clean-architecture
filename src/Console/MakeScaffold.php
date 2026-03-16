<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeScaffold extends BaseGenerator
{
    protected $signature = 'clean:scaffold {context} {name} {--force}';
    protected $description = 'Scaffold a full entity with repository, read model, CQRS, controller, request, resource, and sanitizer';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $force = $this->option('force');
        $plural = $this->toPluralStudly($name);

        $commands = [
            ['clean:entity', [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ]],
            ['clean:model', [
                'context' => $context,
                'name' => $name,
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
                '--force' => $force,
            ]],
            ['clean:command', [
                'context' => $context,
                'name' => "Update{$name}",
                '--entity' => $name,
                '--force' => $force,
            ]],
            ['clean:command', [
                'context' => $context,
                'name' => "Delete{$name}",
                '--entity' => $name,
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
            ['clean:test', [
                'context' => $context,
                'name' => $name,
                '--force' => $force,
            ]],
        ];

        foreach ($commands as [$command, $arguments]) {
            $this->call($command, $arguments);
        }

        $namespace = $this->buildNamespace($context);
        $this->wireServiceProviderBindings($context, $name, $namespace);
        $this->wireRoutes($context, $name, $namespace);

        $this->info("Scaffold for [$name] in [$context] created successfully.");
    }

    protected function wireServiceProviderBindings(string $context, string $name, string $namespace): void
    {
        $spPath = base_path(config('clean-architecture.contexts_path') . "/$context/Infrastructure/{$context}ServiceProvider.php");

        if (! File::exists($spPath)) {
            $this->warn("ServiceProvider not found — skipping binding wiring.");

            return;
        }

        $content = File::get($spPath);

        if (! str_contains($content, '// {bindings}')) {
            $this->warn("No binding markers found in ServiceProvider — skipping wiring.");

            return;
        }

        // Skip if already wired for this entity
        if (str_contains($content, "{$name}WriteRepository::class")) {
            return;
        }

        $binding = "\$this->app->bind(\n"
            . "            \\{$namespace}\\Domain\\Repositories\\{$name}WriteRepository::class,\n"
            . "            \\{$namespace}\\Infrastructure\\{$name}WriteEloquentRepository::class,\n"
            . "        );\n"
            . "        \$this->app->bind(\n"
            . "            \\{$namespace}\\Application\\Contracts\\{$name}ReadRepository::class,\n"
            . "            \\{$namespace}\\Infrastructure\\{$name}ReadEloquentRepository::class,\n"
            . "        );";

        $content = preg_replace_callback(
            '/([ \t]*\/\/ \{bindings\}\n)(.*?)([ \t]*\/\/ \{\/bindings\})/s',
            function ($matches) use ($binding) {
                // Keep only real code lines (remove TODO comments and blank lines)
                $lines = explode("\n", $matches[2]);
                $kept = [];

                foreach ($lines as $line) {
                    $trimmed = trim($line);

                    if ($trimmed === '' || str_starts_with($trimmed, '//')) {
                        continue;
                    }

                    $kept[] = $line;
                }

                $existing = implode("\n", $kept);
                $result = $matches[1];

                if ($existing !== '') {
                    $result .= $existing . "\n";
                }

                $result .= "        $binding\n" . $matches[3];

                return $result;
            },
            $content
        );

        File::put($spPath, $content);
    }

    protected function wireRoutes(string $context, string $name, string $namespace): void
    {
        $routesDir = base_path(config('clean-architecture.contexts_path') . "/$context/Presentation/Routes");

        if (! File::exists($routesDir)) {
            return;
        }

        $plural = $this->toKebabPlural($name);
        $controllerClass = "{$name}Controller";
        $controllerFqn = "{$namespace}\\Presentation\\Controllers\\{$controllerClass}";

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

            $route = "    Route::apiResource('$plural', {$controllerClass}::class);";

            // Add controller import if not present
            $import = "use $controllerFqn;";

            if (! str_contains($content, $import)) {
                $content = str_replace(
                    'use Illuminate\Support\Facades\Route;',
                    "use Illuminate\\Support\\Facades\\Route;\n$import",
                    $content
                );
            }

            // Insert route between markers
            $content = preg_replace_callback(
                '/([ \t]*\/\/ \{routes\}\n)(.*?)([ \t]*\/\/ \{\/routes\})/s',
                function ($matches) use ($route) {
                    $lines = explode("\n", $matches[2]);
                    $kept = [];

                    foreach ($lines as $line) {
                        $trimmed = trim($line);

                        if ($trimmed === '' || str_starts_with($trimmed, '//')) {
                            continue;
                        }

                        $kept[] = $line;
                    }

                    $existing = implode("\n", $kept);
                    $result = $matches[1];

                    if ($existing !== '') {
                        $result .= $existing . "\n";
                    }

                    $result .= $route . "\n" . $matches[3];

                    return $result;
                },
                $content
            );

            File::put($routePath, $content);
        }
    }
}
