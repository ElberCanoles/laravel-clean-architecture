<?php

namespace CleanArchitecture\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class BaseGenerator extends Command
{
    protected function getStub(string $name): string
    {
        $customPath = base_path("stubs/clean-architecture/$name.stub");

        if (File::exists($customPath)) {
            return File::get($customPath);
        }

        $packagePath = __DIR__ . "/../../stubs/$name.stub";

        if (! File::exists($packagePath)) {
            throw new \RuntimeException(
                "Stub '$name.stub' not found. Looked in:\n"
                . "  - $customPath\n"
                . "  - $packagePath\n"
                . 'Run: php artisan vendor:publish --tag=clean-architecture-stubs'
            );
        }

        return File::get($packagePath);
    }

    protected function getNamespacePrefix(): string
    {
        return config('clean-architecture.namespace_prefix', 'App');
    }

    protected function buildNamespace(string $context): string
    {
        return $this->getNamespacePrefix() . "\\$context";
    }

    /**
     * Validate that a context or class name is a valid PHP identifier (PascalCase).
     */
    protected function validateName(string $value, string $label): void
    {
        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $value)) {
            throw new \InvalidArgumentException(
                "Invalid $label: '$value'. Must start with an uppercase letter and contain only alphanumeric characters (e.g. 'Billing', 'Invoice')."
            );
        }
    }

    protected function toPluralStudly(string $name): string
    {
        return Str::plural($name);
    }

    protected function toKebabPlural(string $name): string
    {
        $kebab = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));

        return Str::plural($kebab);
    }

    protected function writeFile(string $filePath, string $content): bool
    {
        if (File::exists($filePath) && ! $this->option('force')) {
            $this->warn("File already exists (use --force to overwrite): $filePath");

            return false;
        }

        File::put($filePath, $content);

        return true;
    }
}
