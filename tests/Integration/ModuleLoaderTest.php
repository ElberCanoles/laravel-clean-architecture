<?php

declare(strict_types=1);

use CleanArchitecture\Kernel\ModuleLoader;
use Composer\Autoload\ClassLoader;
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

test('psr4Map maps each context to its directory with a trailing slash', function () {
    makeContext($this->tempDir, 'Billing');
    ModuleLoader::flush();

    // Directory scans use the OS separator; the trailing slash is always '/'.
    expect(ModuleLoader::psr4Map())->toBe(['Src\Billing\\' => $this->tempDir . DIRECTORY_SEPARATOR . 'Billing/']);
});

test('clean:clear also forgets the memoized scan', function () {
    makeContext($this->tempDir, 'Billing');
    ModuleLoader::flush();
    expect(ModuleLoader::contextNames())->toBe(['Billing']);

    makeContext($this->tempDir, 'Shipping');
    $this->artisan('clean:clear')->assertSuccessful();

    expect(ModuleLoader::contextNames())->toBe(['Billing', 'Shipping']);
});

test('a manifest with the wrong shape is ignored in favour of scanning', function (string $manifest) {
    makeContext($this->tempDir, 'Billing');
    File::ensureDirectoryExists(dirname(ModuleLoader::manifestPath()));
    File::put(ModuleLoader::manifestPath(), $manifest);
    ModuleLoader::flush();

    expect(ModuleLoader::load())->toBe(['Src\Billing\Infrastructure\BillingServiceProvider']);
})->with([
    'not an array' => "<?php return 'nope';",
    'providers missing' => "<?php return ['psr4' => []];",
    'psr4 missing' => "<?php return ['providers' => []];",
    'providers not a list' => "<?php return ['providers' => 'x', 'psr4' => []];",
]);

test('manifest entries that are not strings are dropped', function () {
    File::ensureDirectoryExists(dirname(ModuleLoader::manifestPath()));
    File::put(ModuleLoader::manifestPath(), '<?php return ' . var_export([
        'providers' => ['Src\Cached\Infrastructure\CachedServiceProvider', 42],
        'psr4' => ['Src\Cached\\' => $this->tempDir . '/Cached/', 7 => '/ignored/', 'Src\Broken\\' => 9],
    ], true) . ';');
    ModuleLoader::flush();

    expect(ModuleLoader::load())->toBe(['Src\Cached\Infrastructure\CachedServiceProvider']);

    ModuleLoader::registerAutoload();
    $prefixes = array_values(ClassLoader::getRegisteredLoaders())[0]->getPrefixesPsr4();

    expect($prefixes['Src\Cached\\'])->toContain($this->tempDir . '/Cached/')
        ->and($prefixes)->not->toHaveKey('Src\Broken\\')
        ->and($prefixes)->not->toHaveKey(7);
});

test('clean:cache creates the cache directory and writes a manifest PHP can load', function () {
    makeContext($this->tempDir, 'Billing');
    File::deleteDirectory(dirname(ModuleLoader::manifestPath()));
    ModuleLoader::flush();

    $this->artisan('clean:cache')->assertSuccessful();

    $content = (string) file_get_contents(ModuleLoader::manifestPath());
    expect($content)->toStartWith('<?php return array (')
        ->and($content)->toEndWith(';' . PHP_EOL)
        ->and(require ModuleLoader::manifestPath())->toBe([
            'providers' => ['Src\Billing\Infrastructure\BillingServiceProvider'],
            'psr4' => ['Src\Billing\\' => $this->tempDir . DIRECTORY_SEPARATOR . 'Billing/'],
        ]);
});
