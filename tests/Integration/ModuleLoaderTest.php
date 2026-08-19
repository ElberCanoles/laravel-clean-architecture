<?php

declare(strict_types=1);

use CleanArchitecture\Kernel\ModuleLoader;
use Illuminate\Support\Facades\File;

function makeContext(string $tempDir, string $name, bool $withProvider = true): void
{
    File::ensureDirectoryExists("$tempDir/$name/Infrastructure");

    if ($withProvider) {
        File::put(
            "$tempDir/$name/Infrastructure/{$name}ServiceProvider.php",
            "<?php\n\nnamespace Src\\$name\\Infrastructure;\n\nclass {$name}ServiceProvider extends \\Illuminate\\Support\\ServiceProvider\n{\n}\n"
        );
    }
}

test('load discovers providers by convention', function () {
    makeContext($this->tempDir, 'Billing');
    makeContext($this->tempDir, 'Shipping');
    ModuleLoader::flush();

    expect(ModuleLoader::load())->toBe([
        'Src\Billing\Infrastructure\BillingServiceProvider',
        'Src\Shipping\Infrastructure\ShippingServiceProvider',
    ]);
});

test('load skips contexts without a provider file', function () {
    makeContext($this->tempDir, 'Billing');
    makeContext($this->tempDir, 'Empty', withProvider: false);
    ModuleLoader::flush();

    expect(ModuleLoader::load())->toBe(['Src\Billing\Infrastructure\BillingServiceProvider']);
});

test('registerAutoload resolves context classes through the composer loader', function () {
    // A unique class name per run so repeated processes never collide.
    $class = 'Parcel' . substr(md5($this->tempDir), 0, 8);

    File::ensureDirectoryExists($this->tempDir . '/Logistics/Domain/Entities');
    File::put(
        $this->tempDir . "/Logistics/Domain/Entities/{$class}.php",
        "<?php\n\nnamespace Src\\Logistics\\Domain\\Entities;\n\nclass {$class}\n{\n}\n"
    );
    ModuleLoader::flush();

    ModuleLoader::registerAutoload();

    expect(class_exists("Src\\Logistics\\Domain\\Entities\\{$class}"))->toBeTrue();
});

test('clean:cache writes a manifest that load() and registerAutoload() prefer over scanning', function () {
    makeContext($this->tempDir, 'Billing');
    ModuleLoader::flush();

    $this->artisan('clean:cache')
        ->expectsOutputToContain('Bounded contexts cached')
        ->assertSuccessful();

    expect(file_exists(ModuleLoader::manifestPath()))->toBeTrue();

    // Remove the real directory: a cached manifest must win over the scan.
    File::deleteDirectory($this->tempDir . '/Billing');
    ModuleLoader::flush();

    expect(ModuleLoader::load())->toBe(['Src\Billing\Infrastructure\BillingServiceProvider']);
});

test('clean:clear removes the manifest and discovery falls back to scanning', function () {
    makeContext($this->tempDir, 'Billing');
    ModuleLoader::flush();

    $this->artisan('clean:cache')->assertSuccessful();

    $this->artisan('clean:clear')
        ->expectsOutputToContain('cache cleared')
        ->assertSuccessful();

    expect(file_exists(ModuleLoader::manifestPath()))->toBeFalse();

    ModuleLoader::flush();
    expect(ModuleLoader::load())->toBe(['Src\Billing\Infrastructure\BillingServiceProvider']);
});

test('clean:cache rebuilds from a fresh scan, never from a stale manifest', function () {
    makeContext($this->tempDir, 'Billing');
    ModuleLoader::flush();
    $this->artisan('clean:cache')->assertSuccessful();

    makeContext($this->tempDir, 'Shipping');
    $this->artisan('clean:cache')->assertSuccessful();

    ModuleLoader::flush();
    expect(ModuleLoader::load())->toContain('Src\Shipping\Infrastructure\ShippingServiceProvider');
});

test('discovered context providers are registered with the application', function () {
    makeContext($this->tempDir, 'Billing');
    ModuleLoader::flush();

    // Simulate what the package ServiceProvider does at register() time,
    // now that the context exists on disk.
    ModuleLoader::registerAutoload();

    foreach (ModuleLoader::load() as $provider) {
        expect(class_exists($provider))->toBeTrue();
        $this->app->register($provider);
    }

    expect($this->app->getProvider('Src\Billing\Infrastructure\BillingServiceProvider'))->not->toBeNull();
});

test('contextNames lists discovered contexts', function () {
    makeContext($this->tempDir, 'Billing');
    makeContext($this->tempDir, 'Shipping', withProvider: false);
    ModuleLoader::flush();

    expect(ModuleLoader::contextNames())->toBe(['Billing', 'Shipping']);
});
