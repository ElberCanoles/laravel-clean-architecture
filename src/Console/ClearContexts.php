<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use CleanArchitecture\Kernel\ModuleLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearContexts extends Command
{
    protected $signature = 'clean:clear';

    protected $description = 'Remove the cached bounded context manifest';

    public function handle(): int
    {
        File::delete(ModuleLoader::manifestPath());
        ModuleLoader::flush();

        $this->components->info('Bounded context cache cleared.');

        return self::SUCCESS;
    }
}
