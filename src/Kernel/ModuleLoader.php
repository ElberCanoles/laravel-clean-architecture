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
        $prefix = PackageConfig::namespacePrefix();
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

        $contextsPath = base_path(PackageConfig::contextsPath());

        if (! File::isDirectory($contextsPath)) {
            return self::$contexts = [];
        }

        $contexts = [];

        foreach (File::directories($contextsPath) as $contextPath) {
            if (is_string($contextPath)) {
                $contexts[basename($contextPath)] = $contextPath;
            }
        }

        return self::$contexts = $contexts;
    }

    protected static function providerClass(string $contextName): string
    {
        $prefix = PackageConfig::namespacePrefix();

        return "$prefix\\$contextName\\Infrastructure\\{$contextName}ServiceProvider";
    }

    /**
     * The manifest written by clean:cache, if any — skips filesystem scans
     * entirely on cached production boots. Anything that is not the exact
     * shape clean:cache writes is ignored, so a corrupt file falls back to
     * scanning instead of breaking the boot.
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

        if (! is_array($manifest) || ! is_array($manifest['providers'] ?? null) || ! is_array($manifest['psr4'] ?? null)) {
            return null;
        }

        $providers = [];

        foreach ($manifest['providers'] as $provider) {
            if (is_string($provider)) {
                $providers[] = $provider;
            }
        }

        $psr4 = [];

        foreach ($manifest['psr4'] as $namespace => $directory) {
            if (is_string($namespace) && is_string($directory)) {
                $psr4[$namespace] = $directory;
            }
        }

        return ['providers' => $providers, 'psr4' => $psr4];
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

        if (! is_file($autoload)) {
            return null;
        }

        $loader = require $autoload;

        return $loader instanceof ClassLoader ? $loader : null;
    }
}
