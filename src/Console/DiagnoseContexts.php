<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use CleanArchitecture\Kernel\ModuleLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DiagnoseContexts extends Command
{
    protected $signature = 'clean:doctor';

    protected $description = 'Diagnose bounded context discovery, wiring markers, and configuration';

    public function handle(): int
    {
        $healthy = true;

        $contextsPath = base_path((string) config('clean-architecture.contexts_path', 'src'));
        $this->components->twoColumnDetail('Contexts path', $contextsPath . (File::isDirectory($contextsPath) ? '' : ' (missing)'));

        $idType = (string) config('clean-architecture.id_type', 'uuid');
        $idTypeOk = in_array($idType, ['uuid', 'ulid'], true);
        $this->components->twoColumnDetail('Id type', $idType . ($idTypeOk ? '' : ' (invalid — must be uuid or ulid)'));
        $healthy = $idTypeOk;

        $this->components->twoColumnDetail(
            'Context cache',
            is_file(ModuleLoader::manifestPath()) ? 'cached (clean:clear to refresh)' : 'not cached'
        );

        ModuleLoader::flush();
        $contexts = ModuleLoader::contextNames();

        if ($contexts === []) {
            $this->components->twoColumnDetail('Contexts', 'none discovered');
            $this->newLine();

            return self::SUCCESS;
        }

        foreach ($contexts as $context) {
            $issues = [];
            $notes = [];

            $providerFile = "$contextsPath/$context/Infrastructure/{$context}ServiceProvider.php";

            if (! File::exists($providerFile)) {
                // Informational: directories without a provider are simply skipped.
                $notes[] = 'no ServiceProvider (auto-discovery skips it)';
            } else {
                $content = (string) File::get($providerFile);

                if (! str_contains($content, '// {bindings}')) {
                    $issues[] = 'ServiceProvider has no {bindings} markers (scaffold cannot wire)';
                }
            }

            $routesDir = "$contextsPath/$context/Presentation/Routes";

            if (File::isDirectory($routesDir) && File::glob("$routesDir/*.php") === []) {
                $issues[] = 'Routes directory is empty';
            }

            if ($issues === [] && $notes === []) {
                $this->components->twoColumnDetail("Context [$context]", 'ok');
            }

            foreach ($notes as $note) {
                $this->components->twoColumnDetail("Context [$context]", $note);
            }

            foreach ($issues as $issue) {
                $healthy = false;
                $this->components->twoColumnDetail("Context [$context]", $issue);
            }
        }

        $this->newLine();

        if (! $healthy) {
            $this->components->warn('Some checks reported issues — see above.');

            return self::FAILURE;
        }

        $this->components->info(sprintf('%d context(s) checked, no issues found.', count($contexts)));

        return self::SUCCESS;
    }
}
