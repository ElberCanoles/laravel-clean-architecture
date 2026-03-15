<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeRepository extends BaseGenerator
{
    protected $signature = 'clean:repository {context} {name} {--force}';
    protected $description = 'Create a repository interface and Eloquent implementation';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);

        $this->createInterface($context, $name, $namespace);
        $this->createEloquentImplementation($context, $name, $namespace);
    }

    protected function createInterface(string $context, string $name, string $namespace): void
    {
        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Domain/Repositories");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('repository')
        );

        $file = "$path/{$name}Repository.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Repository interface created: $file");
        }
    }

    protected function createEloquentImplementation(string $context, string $name, string $namespace): void
    {
        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Infrastructure");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('eloquent-repository')
        );

        $file = "$path/{$name}EloquentRepository.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Eloquent repository created: $file");
        }
    }
}
