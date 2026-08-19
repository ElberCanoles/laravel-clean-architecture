<?php

declare(strict_types=1);

namespace CleanArchitecture\Kernel;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\File;

class ModuleLoader
{
    /** @var array<string, string>|null Context name => absolute path, memoized per request. */
    private static ?array $contexts = null;

    /**
     * Forget the per-request scan — used by tests and by clean:cache/clean:clear.
     */
    public static function flush(): void
    {
        self::$contexts = null;
    }

    public static function manifestPath(): string
    {
        return base_path('bootstrap/cache/clean-architecture.php');
    }

    /**
     * Provider FQCNs for every context that ships one, resolved by convention.
     *
     * @return list<string>
     */
    public static function load(): array
    {
        $manifest = self::manifest();

        if ($manifest !== null) {
            return $manifest['providers'];
        }

        $providers = [];

        foreach (self::contexts() as $contextName => $contextPath) {
            $providerFile = "$contextPath/Infrastructure/{$contextName}ServiceProvider.php";

            if (File::exists($providerFile)) {
                $providers[] = self::providerClass($contextName);
            }
        }

        return $providers;
    }

    /**
     * Register PSR-4 autoloading for all bounded contexts through Composer's
     * already-registered class loader.
     */
    public static function registerAutoload(): void
    {
        $manifest = self::manifest();
        $map = $manifest !== null ? $manifest['psr4'] : self::psr4Map();

        if ($map === []) {
            return;
        }

        $loader = self::composerLoader();

        if ($loader === null) {
            return;
        }

        foreach ($map as $namespace => $path) {
            $loader->addPsr4($namespace, $path);
        }
    }

    /**
     * @return array<string, string> Namespace prefix => absolute path
     */
    public static function psr4Map(): array
    {
        $prefix = (string) config('clean-architecture.namespace_prefix', 'Src');
        $map = [];

        foreach (self::contexts() as $contextName => $contextPath) {
            $map["$prefix\\$contextName\\"] = $contextPath . '/';
        }

        return $map;
    }

    /**
     * @return list<string> Discovered context names
     */
    public static function contextNames(): array
    {
        return array_keys(self::contexts());
    }

    /**
     * @return array<string, string> Context name => absolute path
     */
    protected static function contexts(): array
    {
        if (self::$contexts !== null) {
            return self::$contexts;
        }

        $contextsPath = base_path((string) config('clean-architecture.contexts_path', 'src'));

        if (! File::isDirectory($contextsPath)) {
            return self::$contexts = [];
        }

        $contexts = [];

        foreach (File::directories($contextsPath) as $contextPath) {
            $contexts[basename($contextPath)] = $contextPath;
        }

        return self::$contexts = $contexts;
    }

    protected static function providerClass(string $contextName): string
    {
        $prefix = (string) config('clean-architecture.namespace_prefix', 'Src');

        return "$prefix\\$contextName\\Infrastructure\\{$contextName}ServiceProvider";
    }

    /**
     * The manifest written by clean:cache, if any — skips filesystem scans
     * entirely on cached production boots.
     *
     * @return array{providers: list<string>, psr4: array<string, string>}|null
     */
    protected static function manifest(): ?array
    {
        $path = self::manifestPath();

        if (! is_file($path)) {
            return null;
        }

        $manifest = require $path;

        if (! is_array($manifest) || ! isset($manifest['providers'], $manifest['psr4'])) {
            return null;
        }

        return $manifest;
    }

    /**
     * Composer's registered class loader. Re-requiring vendor/autoload.php
     * fatals in monorepos or with a custom vendor dir, so ask Composer for the
     * loader it already registered and only fall back to the classic require.
     */
    protected static function composerLoader(): ?ClassLoader
    {
        $loaders = ClassLoader::getRegisteredLoaders();

        if ($loaders !== []) {
            return array_values($loaders)[0];
        }

        $autoload = base_path('vendor/autoload.php');

        return is_file($autoload) ? require $autoload : null;
    }
}
