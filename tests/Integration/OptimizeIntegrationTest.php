<?php

declare(strict_types=1);

use CleanArchitecture\Kernel\ModuleLoader;
use Illuminate\Support\ServiceProvider;

test('clean:cache and clean:clear are registered with the optimizer', function () {
    expect(ServiceProvider::$optimizeCommands)->toHaveKey('clean-architecture', 'clean:cache')
        ->and(ServiceProvider::$optimizeClearCommands)->toHaveKey('clean-architecture', 'clean:clear');
});

test('php artisan optimize caches the discovered contexts', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();

    // The framework's own caches would outlive this test in the testbench
    // skeleton, so only the package task runs.
    $this->artisan('optimize', ['--except' => 'config,events,routes,views'])->assertSuccessful();

    expect(is_file(ModuleLoader::manifestPath()))->toBeTrue();

    $manifest = require ModuleLoader::manifestPath();
    expect($manifest['providers'])->toBe(['Src\Billing\Infrastructure\BillingServiceProvider']);
});

test('php artisan optimize:clear forgets the context cache', function () {
    $this->artisan('clean:cache')->assertSuccessful();
    expect(is_file(ModuleLoader::manifestPath()))->toBeTrue();

    $this->artisan('optimize:clear')->assertSuccessful();

    expect(is_file(ModuleLoader::manifestPath()))->toBeFalse();
});
