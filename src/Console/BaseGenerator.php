<?php

namespace CleanArchitecture\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

abstract class BaseGenerator extends Command
{
    protected function getStub(string $name): string
    {
        $customPath = base_path("stubs/clean-architecture/$name.stub");

        if (File::exists($customPath)) {
            return File::get($customPath);
        }

        return File::get(__DIR__ . "/../../stubs/$name.stub");
    }

    protected function getNamespacePrefix(): string
    {
        return config('clean-architecture.namespace_prefix', 'App');
    }

    protected function buildNamespace(string $context): string
    {
        return $this->getNamespacePrefix() . "\\$context";
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
