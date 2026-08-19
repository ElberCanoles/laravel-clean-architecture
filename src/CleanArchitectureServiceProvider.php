<?php

declare(strict_types=1);

namespace CleanArchitecture;

use CleanArchitecture\Console\CacheContexts;
use CleanArchitecture\Console\ClearContexts;
use CleanArchitecture\Console\MakeArchTest;
use CleanArchitecture\Console\MakeBoundedContext;
use CleanArchitecture\Console\MakeCommand;
use CleanArchitecture\Console\MakeController;
use CleanArchitecture\Console\MakeDomainEvent;
use CleanArchitecture\Console\MakeEntity;
use CleanArchitecture\Console\MakeException;
use CleanArchitecture\Console\MakeMapper;
use CleanArchitecture\Console\MakeModel;
use CleanArchitecture\Console\MakeQuery;
use CleanArchitecture\Console\MakeReadModel;
use CleanArchitecture\Console\MakeRepository;
use CleanArchitecture\Console\MakeRequest;
use CleanArchitecture\Console\MakeResource;
use CleanArchitecture\Console\MakeSanitizer;
use CleanArchitecture\Console\MakeScaffold;
use CleanArchitecture\Console\MakeSpecification;
use CleanArchitecture\Console\MakeTest;
use CleanArchitecture\Console\MakeValueObject;
use CleanArchitecture\Kernel\ModuleLoader;
use CleanArchitecture\Support\ProvidesHttpStatus;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Request;
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
        $this->registerDomainExceptionRenderer();

        if ($this->app->runningInConsole()) {
            $this->registerAboutCommand();

            $this->commands([
                CacheContexts::class,
                ClearContexts::class,
                MakeBoundedContext::class,
                MakeCommand::class,
                MakeQuery::class,
                MakeEntity::class,
                MakeRepository::class,
                MakeValueObject::class,
                MakeSpecification::class,
                MakeReadModel::class,
                MakeArchTest::class,
                MakeDomainEvent::class,
                MakeException::class,
                MakeMapper::class,
                MakeModel::class,
                MakeSanitizer::class,
                MakeScaffold::class,
                MakeTest::class,
                MakeController::class,
                MakeRequest::class,
                MakeResource::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/clean-architecture.php' => config_path('clean-architecture.php'),
            ], 'clean-architecture-config');

            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/clean-architecture'),
            ], 'clean-architecture-stubs');
        }
    }

    /**
     * Surface discovery state in `php artisan about` — the cheapest diagnostic
     * for "which contexts did the package actually find?".
     */
    protected function registerAboutCommand(): void
    {
        if (! class_exists(AboutCommand::class)) {
            return;
        }

        AboutCommand::add('Clean Architecture', fn (): array => [
            'Contexts' => implode(', ', ModuleLoader::contextNames()) ?: 'none discovered',
            'Providers Discovered' => (string) count(ModuleLoader::load()),
            'Id Type' => (string) config('clean-architecture.id_type', 'uuid'),
            'Contexts Cached' => is_file(ModuleLoader::manifestPath()) ? 'yes' : 'no',
        ]);
    }

    /**
     * Render uncaught domain exceptions as JSON error responses so business
     * rule violations do not surface as generic 500 errors.
     */
    protected function registerDomainExceptionRenderer(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! method_exists($handler, 'renderable')) {
                return;
            }

            $handler->renderable(function (\DomainException $e, Request $request) {
                if (! config('clean-architecture.render_domain_exceptions', true) || ! $request->expectsJson()) {
                    return null;
                }

                $status = $e instanceof ProvidesHttpStatus ? $e->httpStatus() : 422;

                return response()->json(['message' => $e->getMessage()], $status);
            });
        });
    }

    protected function registerContextProviders(): void
    {
        $providers = ModuleLoader::load();

        foreach ($providers as $provider) {
            if (! class_exists($provider)) {
                continue;
            }

            try {
                $this->app->register($provider);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
