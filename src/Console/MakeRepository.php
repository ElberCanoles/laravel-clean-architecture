<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeRepository extends BaseGenerator
{
    protected $signature = 'clean:repository {context} {name} {--force}';
    protected $description = 'Create CQRS repository interfaces and Eloquent implementations with mapper';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $namespace = $this->buildNamespace($context);

        $this->createWriteInterface($context, $name, $namespace);
        $this->createReadInterface($context, $name, $namespace);
        $this->createWriteEloquentImplementation($context, $name, $namespace);
        $this->createReadEloquentImplementation($context, $name, $namespace);
        $this->createMapper($context, $name, $namespace);
    }

    protected function createWriteInterface(string $context, string $name, string $namespace): void
    {
        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Domain/Repositories");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('write-repository')
        );

        $file = "$path/{$name}WriteRepository.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Write repository interface created: $file");
        }
    }

    protected function createReadInterface(string $context, string $name, string $namespace): void
    {
        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Application/Contracts");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('read-repository')
        );

        $file = "$path/{$name}ReadRepository.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Read repository interface created: $file");
        }
    }

    protected function createWriteEloquentImplementation(string $context, string $name, string $namespace): void
    {
        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Infrastructure");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('write-eloquent-repository')
        );

        $file = "$path/{$name}WriteEloquentRepository.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Write Eloquent repository created: $file");
        }
    }

    protected function createReadEloquentImplementation(string $context, string $name, string $namespace): void
    {
        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Infrastructure");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('read-eloquent-repository')
        );

        $file = "$path/{$name}ReadEloquentRepository.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Read Eloquent repository created: $file");
        }
    }

    protected function createMapper(string $context, string $name, string $namespace): void
    {
        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Infrastructure");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('mapper')
        );

        $file = "$path/{$name}Mapper.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Mapper created: $file");
        }
    }
}
