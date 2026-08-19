<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeScaffold extends BaseGenerator
{
    use Concerns\ScaffoldManifest;
    use Concerns\WiresContext;

    protected $signature = 'clean:scaffold {context} {name} {--id-type= : Primary key type (uuid, ulid)} {--table= : Override the derived table name} {--plural= : Override the derived plural form} {--dry-run : List what would be generated without writing anything} {--force : Overwrite existing files}';

    protected $description = 'Scaffold a full entity with repository, read model, CQRS, controller, request, resource, and sanitizer';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');

        $force = $this->option('force');
        $plural = $this->pluralName($name);

        // Resolved once so every generated file shares the same identifier strategy.
        $idType = $this->resolveIdType();

        if ($this->option('dry-run')) {
            return $this->renderPlan($context, $name);
        }

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
                '--table' => $this->stringOption('table'),
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
        $wiredRoutes = $this->wireRoutes($context, $name, $namespace, $this->toKebab($plural));
        $migrated = $this->generateMigration($name, $idType);

        if ($failed || ! $wiredBindings || ! $wiredRoutes || ! $migrated) {
            $this->warn("Scaffold for [$name] in [$context] completed with warnings — review the output above.");

            return self::FAILURE;
        }

        $this->info("Scaffold for [$name] in [$context] created successfully.");

        return self::SUCCESS;
    }

    protected function renderPlan(string $context, string $name): int
    {
        $basePath = base_path();

        foreach ($this->scaffoldFiles($context, $name) as $label => $path) {
            $relative = ltrim(str_replace($basePath, '', $path), '/\\');
            $suffix = File::exists($path) ? ' (exists — needs --force)' : '';
            $this->components->twoColumnDetail($label, $relative . $suffix);
        }

        $this->components->twoColumnDetail('Migration', 'database/migrations/*_create_' . Str::snake(Str::pluralStudly($name)) . '_table.php');
        $this->components->twoColumnDetail('Wiring', "{$context}ServiceProvider bindings + resource route");
        $this->newLine();
        $this->info('Dry run — nothing was written.');

        return self::SUCCESS;
    }

    protected function pluralName(string $name): string
    {
        $plural = $this->stringOption('plural');

        return $plural !== null ? $this->cleanName($plural, 'plural') : $this->toPluralStudly($name);
    }

    protected function generateMigration(string $name, string $idType): bool
    {
        $table = $this->stringOption('table') ?? Str::snake(Str::pluralStudly($name));
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
