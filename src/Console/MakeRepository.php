<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeRepository extends BaseGenerator
{
    protected $signature = 'clean:repository {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create CQRS repository interfaces and Eloquent implementations with mapper';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');

        $namespace = $this->buildNamespace($context);

        $results = [
            $this->createWriteInterface($context, $name, $namespace),
            $this->createReadInterface($context, $name, $namespace),
            $this->createWriteEloquentImplementation($context, $name, $namespace),
            $this->createReadEloquentImplementation($context, $name, $namespace),
            $this->createMapper($context, $name, $namespace),
            $this->createInMemoryWrite($context, $name, $namespace),
            $this->createInMemoryRead($context, $name, $namespace),
        ];

        return in_array(false, $results, true) ? self::FAILURE : self::SUCCESS;
    }

    protected function createWriteInterface(string $context, string $name, string $namespace): bool
    {
        $path = $this->contextPath($context, 'Domain/Repositories');
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('write-repository')
        );

        $file = "$path/{$name}WriteRepository.php";

        if (! $this->writeFile($file, $content)) {
            return false;
        }

        $this->info("Write repository interface created: $file");

        return true;
    }

    protected function createReadInterface(string $context, string $name, string $namespace): bool
    {
        $path = $this->contextPath($context, 'Application/Contracts');
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('read-repository')
        );

        $file = "$path/{$name}ReadRepository.php";

        if (! $this->writeFile($file, $content)) {
            return false;
        }

        $this->info("Read repository interface created: $file");

        return true;
    }

    protected function createWriteEloquentImplementation(string $context, string $name, string $namespace): bool
    {
        $path = $this->contextPath($context, 'Infrastructure');
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('write-eloquent-repository')
        );

        $file = "$path/{$name}WriteEloquentRepository.php";

        if (! $this->writeFile($file, $content)) {
            return false;
        }

        $this->info("Write Eloquent repository created: $file");

        return true;
    }

    protected function createReadEloquentImplementation(string $context, string $name, string $namespace): bool
    {
        $path = $this->contextPath($context, 'Infrastructure');
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('read-eloquent-repository')
        );

        $file = "$path/{$name}ReadEloquentRepository.php";

        if (! $this->writeFile($file, $content)) {
            return false;
        }

        $this->info("Read Eloquent repository created: $file");

        return true;
    }

    protected function createMapper(string $context, string $name, string $namespace): bool
    {
        $path = $this->contextPath($context, 'Infrastructure');
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('mapper')
        );

        $file = "$path/{$name}Mapper.php";

        if (! $this->writeFile($file, $content)) {
            return false;
        }

        $this->info("Mapper created: $file");

        return true;
    }

    protected function createInMemoryWrite(string $context, string $name, string $namespace): bool
    {
        $path = $this->contextPath($context, 'Infrastructure');
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('in-memory-write-repository')
        );

        $file = "$path/InMemory{$name}WriteRepository.php";

        if (! $this->writeFile($file, $content)) {
            return false;
        }

        $this->info("In-memory write repository created: $file");

        return true;
    }

    protected function createInMemoryRead(string $context, string $name, string $namespace): bool
    {
        $path = $this->contextPath($context, 'Infrastructure');
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('in-memory-read-repository')
        );

        $file = "$path/InMemory{$name}ReadRepository.php";

        if (! $this->writeFile($file, $content)) {
            return false;
        }

        $this->info("In-memory read repository created: $file");

        return true;
    }
}
