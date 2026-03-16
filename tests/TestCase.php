<?php

namespace CleanArchitecture\Tests;

use CleanArchitecture\CleanArchitectureServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $tempDir;
    protected string $relativeTempDir;

    protected function setUp(): void
    {
        $this->relativeTempDir = 'clean-arch-test-' . uniqid();

        parent::setUp();

        $this->tempDir = base_path($this->relativeTempDir);
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [CleanArchitectureServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('clean-architecture.contexts_path', $this->relativeTempDir);
        $app['config']->set('clean-architecture.namespace_prefix', 'App');
        $app['config']->set('clean-architecture.arch_tests_path', $this->relativeTempDir . '/tests/Feature/Architecture');
        $app['config']->set('clean-architecture.unit_tests_path', $this->relativeTempDir . '/tests/Unit/Domain');
    }
}
