<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DestroyScaffold extends BaseGenerator
{
    use Concerns\ScaffoldManifest;

    protected $signature = 'clean:destroy {context} {name} {--with-migration : Also delete the create-table migration} {--force : Skip the confirmation prompt}';

    protected $description = 'Delete every file clean:scaffold generated for an entity';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');

        $existing = array_filter(
            $this->scaffoldFiles($context, $name),
            fn (string $path): bool => File::exists($path)
        );

        $migrations = $this->option('with-migration')
            ? File::glob(database_path('migrations') . '/*_create_' . Str::snake(Str::pluralStudly($name)) . '_table.php')
            : [];

        if ($existing === [] && $migrations === []) {
            $this->info("Nothing to destroy for [$name] in [$context].");

            return self::SUCCESS;
        }

        $total = count($existing) + count($migrations);

        if (! $this->option('force') && ! $this->confirm("Delete {$total} file(s) generated for [$name] in [$context]?")) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        foreach ($existing as $label => $path) {
            File::delete($path);
            $this->components->twoColumnDetail($label, 'deleted');
        }

        foreach ($migrations as $migration) {
            File::delete($migration);
            $this->components->twoColumnDetail('Migration', 'deleted');
        }

        $this->warn('ServiceProvider bindings and route entries were left in place — remove them manually if no longer needed.');

        return self::SUCCESS;
    }
}
