<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeBoundedContext extends BaseGenerator
{
    protected $signature = 'clean:context {name} {--routes=api : Route types to generate (api, web, both)} {--force}';
    protected $description = 'Create a new bounded context with DDD folder structure';

    public function handle(): int
    {
        $name = $this->argument('name');

        $this->validateName($name, 'context');

        $routes = $this->option('routes');

        if (! in_array($routes, ['api', 'web', 'both'])) {
            throw new \InvalidArgumentException(
                "Invalid --routes value: '$routes'. Must be 'api', 'web', or 'both'."
            );
        }

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
        $this->generateRoutes($base, $name, $routes);

        $this->info("Bounded context [$name] created.");

        $this->call('clean:arch-test', [
            'context' => $name,
            '--force' => $this->option('force'),
        ]);

        return self::SUCCESS;
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

    protected function generateRoutes(string $base, string $context, string $routes): void
    {
        $prefix = $this->toKebab($context);

        $stubContent = str_replace(
            '{{prefix}}',
            $prefix,
            $this->getStub('routes')
        );

        $types = $routes === 'both' ? ['api', 'web'] : [$routes];

        foreach ($types as $type) {
            $file = "$base/Presentation/Routes/$type.php";

            if ($this->writeFile($file, $stubContent)) {
                $this->info("Routes created: $file");
            }
        }
    }
}
