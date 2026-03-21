<?php

namespace CleanArchitecture\Kernel;

use Illuminate\Support\Facades\File;

class ModuleLoader
{
    /** @return list<string> */
    public static function load(): array
    {
        $contextsPath = base_path(config('clean-architecture.contexts_path', 'src'));
        $providers = [];

        if (! File::isDirectory($contextsPath)) {
            return $providers;
        }

        $directories = File::directories($contextsPath);
        $namespacePrefix = config('clean-architecture.namespace_prefix', 'Src');

        foreach ($directories as $contextPath) {
            $contextName = basename($contextPath);
            $providerClass = "$namespacePrefix\\$contextName\\Infrastructure\\{$contextName}ServiceProvider";
            $providerFile = "$contextPath/Infrastructure/{$contextName}ServiceProvider.php";

            if (File::exists($providerFile)) {
                $providers[] = $providerClass;
            }
        }

        return $providers;
    }

    /**
     * Register PSR-4 autoloading for all bounded contexts in src/.
     */
    public static function registerAutoload(): void
    {
        $contextsPath = base_path(config('clean-architecture.contexts_path', 'src'));

        if (! File::isDirectory($contextsPath)) {
            return;
        }

        $namespacePrefix = config('clean-architecture.namespace_prefix', 'Src');
        $directories = File::directories($contextsPath);

        /** @var \Composer\Autoload\ClassLoader $loader */
        $loader = require base_path('vendor/autoload.php');

        foreach ($directories as $contextPath) {
            $contextName = basename($contextPath);
            $namespace = "$namespacePrefix\\$contextName\\";

            $loader->addPsr4($namespace, $contextPath . '/');
        }
    }
}
