<?php

test('creates full bounded context folder structure', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])
        ->assertSuccessful();

    $base = $this->tempDir . '/Billing';

    expect(is_dir("$base/Domain/Entities"))->toBeTrue();
    expect(is_dir("$base/Domain/ValueObjects"))->toBeTrue();
    expect(is_dir("$base/Domain/Repositories"))->toBeTrue();
    expect(is_dir("$base/Domain/Specifications"))->toBeTrue();
    expect(is_dir("$base/Application/Commands"))->toBeTrue();
    expect(is_dir("$base/Application/Queries"))->toBeTrue();
    expect(is_dir("$base/Application/ReadModels"))->toBeTrue();
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
        ->toContain('namespace App\Billing\Infrastructure;')
        ->toContain('class BillingServiceProvider extends ServiceProvider')
        ->toContain('public function register(): void')
        ->toContain('public function boot(): void')
        ->toContain('$this->loadRoutes()')
        ->toContain("'/../Presentation/Routes/api.php'");
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
