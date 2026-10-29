<?php

namespace CleanArchitecture\Tests;

use CleanArchitecture\CleanArchitectureServiceProvider;
use CleanArchitecture\Kernel\ModuleLoader;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $tempDir;

    protected string $relativeTempDir;

    /** Sibling of the contexts dir holding the per-test database, bootstrap, and stubs paths. */
    protected string $envDir;

    protected function setUp(): void
    {
        $this->relativeTempDir = 'clean-arch-test-' . uniqid();

        parent::setUp();

        // The scan is memoized per process and the temp dir changes per test.
        ModuleLoader::flush();

        $this->tempDir = base_path($this->relativeTempDir);
        File::ensureDirectoryExists($this->tempDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);
        File::deleteDirectory($this->envDir);
        ModuleLoader::flush();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [CleanArchitectureServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // Every path a test can write to is private to the test, so the suite
        // is safe under --parallel: migrations, the clean:cache manifest (and
        // Laravel's own bootstrap caches), and published stubs never collide
        // between processes. They live next to the contexts dir, not inside
        // it, because every subdirectory of contexts_path counts as a context.
        $this->envDir = $app->basePath($this->relativeTempDir . '-env');
        $app->useDatabasePath($this->envDir . '/database');
        $app->useBootstrapPath($this->envDir . '/bootstrap');

        if (! is_dir($this->envDir . '/bootstrap/cache')) {
            mkdir($this->envDir . '/bootstrap/cache', 0755, true);
        }

        $app['config']->set('clean-architecture.contexts_path', $this->relativeTempDir);
        $app['config']->set('clean-architecture.stubs_path', $this->relativeTempDir . '-env/stubs');
        $app['config']->set('clean-architecture.namespace_prefix', 'Src');
        $app['config']->set('clean-architecture.arch_tests_path', $this->relativeTempDir . '/tests/Feature/Architecture');
        $app['config']->set('clean-architecture.unit_tests_path', $this->relativeTempDir . '/tests/Unit/Domain');
        $app['config']->set('clean-architecture.handler_tests_path', $this->relativeTempDir . '/tests/Unit/Application');
        $app['config']->set('clean-architecture.feature_tests_path', $this->relativeTempDir . '/tests/Feature');
    }
}
