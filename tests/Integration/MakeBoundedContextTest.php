<?php

test('creates full bounded context folder structure', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])
        ->assertSuccessful();

    $base = $this->tempDir . '/Billing';

    expect(is_dir("$base/Domain/Entities"))->toBeTrue();
    expect(is_dir("$base/Domain/ValueObjects"))->toBeTrue();
    expect(is_dir("$base/Domain/Repositories"))->toBeTrue();
    expect(is_dir("$base/Domain/Specifications"))->toBeTrue();
    expect(is_dir("$base/Domain/Events"))->toBeTrue();
    expect(is_dir("$base/Domain/Exceptions"))->toBeTrue();
    expect(is_dir("$base/Application/Commands"))->toBeTrue();
    expect(is_dir("$base/Application/Queries"))->toBeTrue();
    expect(is_dir("$base/Application/ReadModels"))->toBeTrue();
    expect(is_dir("$base/Application/Contracts"))->toBeTrue();
    expect(is_dir("$base/Application/Sanitizers"))->toBeTrue();
    expect(is_dir("$base/Infrastructure"))->toBeTrue();
    expect(is_dir("$base/Presentation/Controllers"))->toBeTrue();
    expect(is_dir("$base/Presentation/Requests"))->toBeTrue();
    expect(is_dir("$base/Presentation/Resources"))->toBeTrue();
    expect(is_dir("$base/Presentation/Routes"))->toBeTrue();
});

test('generates service provider for context with route loading', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Infrastructure/BillingServiceProvider.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Infrastructure;')
        ->toContain('class BillingServiceProvider extends ServiceProvider')
        ->toContain('public function register(): void')
        ->toContain('auto-discovered by the CleanArchitecture package')
        ->toContain('TODO: Bind repository interfaces to implementations')
        ->toContain('YourWriteRepository::class')
        ->toContain('YourReadRepository::class')
        ->toContain('public function boot(): void')
        ->toContain('$this->loadRoutes()')
        ->toContain("foreach (['api', 'web'] as \$type)")
        ->toContain('// {bindings}')
        ->toContain('// {/bindings}');
});

test('generates routes file for context', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Routes/api.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain("Route::prefix('billing')")
        ->toContain('->group(');
});

test('generates kebab-case route prefix for multi-word contexts', function () {
    $this->artisan('clean:context', ['name' => 'OrderManagement'])
        ->assertSuccessful();

    $file = $this->tempDir . '/OrderManagement/Presentation/Routes/api.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)->toContain("Route::prefix('order-management')");
});

test('generates architecture test for context', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])
        ->assertSuccessful();

    $file = $this->tempDir . '/tests/Feature/Architecture/BillingArchTest.php';
    expect(file_exists($file))->toBeTrue();
});

test('warns when service provider exists without --force', function () {
    $this->artisan('clean:context', ['name' => 'Billing']);

    $this->artisan('clean:context', ['name' => 'Billing'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites files with --force', function () {
    $this->artisan('clean:context', ['name' => 'Billing']);
    $this->artisan('clean:context', ['name' => 'Billing', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('ServiceProvider created');
});

test('rejects invalid context name', function () {
    $this->artisan('clean:context', ['name' => 'my-context']);
})->throws(\InvalidArgumentException::class);

test('generates web routes with --routes=web', function () {
    $this->artisan('clean:context', ['name' => 'Billing', '--routes' => 'web'])
        ->assertSuccessful();

    $apiFile = $this->tempDir . '/Billing/Presentation/Routes/api.php';
    $webFile = $this->tempDir . '/Billing/Presentation/Routes/web.php';

    expect(file_exists($apiFile))->toBeFalse();
    expect(file_exists($webFile))->toBeTrue();

    $content = file_get_contents($webFile);
    expect($content)->toContain("Route::prefix('billing')");
});

test('generates both route files with --routes=both', function () {
    $this->artisan('clean:context', ['name' => 'Billing', '--routes' => 'both'])
        ->assertSuccessful();

    $apiFile = $this->tempDir . '/Billing/Presentation/Routes/api.php';
    $webFile = $this->tempDir . '/Billing/Presentation/Routes/web.php';

    expect(file_exists($apiFile))->toBeTrue();
    expect(file_exists($webFile))->toBeTrue();
});

test('rejects invalid --routes value', function () {
    $this->artisan('clean:context', ['name' => 'Billing', '--routes' => 'invalid']);
})->throws(\InvalidArgumentException::class);

test('generates routes file with route markers', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Routes/api.php';
    $content = file_get_contents($file);

    expect($content)
        ->toContain('// {routes}')
        ->toContain('// {/routes}');
});
