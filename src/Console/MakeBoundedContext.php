<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeBoundedContext extends BaseGenerator
{
    protected $signature = 'clean:context {name} {--force}';
    protected $description = 'Create a new bounded context with DDD folder structure';

    public function handle(): void
    {
        $name = $this->argument('name');

        $this->validateName($name, 'context');

        $base = base_path(config('clean-architecture.contexts_path') . "/$name");
        $namespace = $this->buildNamespace($name);

        $folders = [
            'Domain/Entities',
            'Domain/ValueObjects',
            'Domain/Repositories',
            'Domain/Specifications',
            'Domain/Events',
            'Domain/Exceptions',
            'Application/Commands',
            'Application/Queries',
            'Application/ReadModels',
            'Application/Contracts',
            'Application/Sanitizers',
            'Infrastructure',
            'Presentation/Controllers',
            'Presentation/Requests',
            'Presentation/Resources',
            'Presentation/Routes',
        ];

        foreach ($folders as $folder) {
            File::makeDirectory("$base/$folder", 0755, true, true);
        }

        $this->generateServiceProvider($base, $name, $namespace);
        $this->generateRoutes($base, $name);

        $this->info("Bounded context [$name] created.");

        $this->call('clean:arch-test', [
            'context' => $name,
            '--force' => $this->option('force'),
        ]);
    }

    protected function generateServiceProvider(string $base, string $context, string $namespace): void
    {
        $content = str_replace(
            ['{{Namespace}}', '{{Context}}'],
            [$namespace, $context],
            $this->getStub('service-provider')
        );

        $file = "$base/Infrastructure/{$context}ServiceProvider.php";

        if ($this->writeFile($file, $content)) {
            $this->info("ServiceProvider created: $file");
        }
    }

    protected function generateRoutes(string $base, string $context): void
    {
        $prefix = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $context));

        $content = str_replace(
            '{{prefix}}',
            $prefix,
            $this->getStub('routes')
        );

        $file = "$base/Presentation/Routes/api.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Routes created: $file");
        }
    }
}
