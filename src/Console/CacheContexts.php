<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use CleanArchitecture\Kernel\ModuleLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CacheContexts extends Command
{
    protected $signature = 'clean:cache';

    protected $description = 'Cache discovered bounded contexts, providers, and PSR-4 mappings for faster boots';

    public function handle(): int
    {
        // Rebuild from a fresh scan — never from a stale manifest.
        File::delete(ModuleLoader::manifestPath());
        ModuleLoader::flush();

        $manifest = [
            'providers' => ModuleLoader::load(),
            'psr4' => ModuleLoader::psr4Map(),
        ];

        File::ensureDirectoryExists(dirname(ModuleLoader::manifestPath()));

        $content = '<?php return ' . var_export($manifest, true) . ';' . PHP_EOL;

        if (File::put(ModuleLoader::manifestPath(), $content) === false) {
            $this->components->error('Could not write ' . ModuleLoader::manifestPath());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Bounded contexts cached: %d context(s), %d provider(s).',
            count($manifest['psr4']),
            count($manifest['providers'])
        ));

        return self::SUCCESS;
    }
}
