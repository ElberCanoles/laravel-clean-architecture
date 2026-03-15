<?php

namespace CleanArchitecture;

use CleanArchitecture\Console\MakeArchTest;
use CleanArchitecture\Console\MakeBoundedContext;
use CleanArchitecture\Console\MakeCommand;
use CleanArchitecture\Console\MakeEntity;
use CleanArchitecture\Console\MakeQuery;
use CleanArchitecture\Console\MakeReadModel;
use CleanArchitecture\Console\MakeRepository;
use CleanArchitecture\Console\MakeSpecification;
use CleanArchitecture\Console\MakeValueObject;
use CleanArchitecture\Kernel\ModuleLoader;
use Illuminate\Support\ServiceProvider;

class CleanArchitectureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/clean-architecture.php', 'clean-architecture');

        if (config('clean-architecture.auto_load', true)) {
            ModuleLoader::registerAutoload();
        }

        if (config('clean-architecture.auto_discover', true)) {
            $this->registerContextProviders();
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeBoundedContext::class,
                MakeCommand::class,
                MakeQuery::class,
                MakeEntity::class,
                MakeRepository::class,
                MakeValueObject::class,
                MakeSpecification::class,
                MakeReadModel::class,
                MakeArchTest::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/clean-architecture.php' => config_path('clean-architecture.php'),
            ], 'clean-architecture-config');

            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/clean-architecture'),
            ], 'clean-architecture-stubs');
        }
    }

    protected function registerContextProviders(): void
    {
        $providers = ModuleLoader::load();

        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }
}
