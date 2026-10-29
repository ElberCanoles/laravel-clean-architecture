<?php

declare(strict_types=1);

use CleanArchitecture\CleanArchitectureServiceProvider;
use CleanArchitecture\Kernel\ModuleLoader;
use CleanArchitecture\Kernel\PackageConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

test('merges the package defaults into the configuration', function () {
    // Keys the test case never overrides come straight from config/clean-architecture.php.
    expect(config('clean-architecture.id_type'))->toBe('uuid')
        ->and(config('clean-architecture.auto_discover'))->toBeTrue()
        ->and(config('clean-architecture.auto_load'))->toBeTrue()
        ->and(config('clean-architecture.render_domain_exceptions'))->toBeTrue();
});

test('registers autoloading and the provider of every discovered context when it boots', function () {
    // A unique class per run: repeated processes share the autoloader.
    $class = 'Booted' . substr(md5($this->tempDir), 0, 8);

    File::ensureDirectoryExists($this->tempDir . '/Shop/Domain');
    File::ensureDirectoryExists($this->tempDir . '/Shop/Infrastructure');
    File::put(
        $this->tempDir . "/Shop/Domain/{$class}.php",
        "<?php\n\nnamespace Src\\Shop\\Domain;\n\nclass {$class}\n{\n}\n"
    );
    File::put(
        $this->tempDir . '/Shop/Infrastructure/ShopServiceProvider.php',
        "<?php\n\nnamespace Src\\Shop\\Infrastructure;\n\nclass ShopServiceProvider extends \\Illuminate\\Support\\ServiceProvider\n{\n}\n"
    );
    ModuleLoader::flush();

    // Boot the package provider again, now that the context exists on disk.
    $this->app->register(CleanArchitectureServiceProvider::class, force: true);

    expect(class_exists("Src\\Shop\\Domain\\{$class}"))->toBeTrue()
        ->and($this->app->getProvider('Src\Shop\Infrastructure\ShopServiceProvider'))->not->toBeNull();
});

test('adds a section to php artisan about', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();

    $output = artisanOutput('about', ['--only' => 'clean_architecture']);

    expect($output)->toContain('Clean Architecture')
        ->toMatch('/Contexts[ .]+Billing/')
        ->toMatch('/Contexts Cached[ .]+no\s*$/m')
        ->toMatch('/Id Type[ .]+uuid\s*$/m')
        ->toMatch('/Providers Discovered[ .]+1\s*$/m');
});

test('php artisan about reports when the contexts are cached', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();
    $this->artisan('clean:cache')->assertSuccessful();

    expect(artisanOutput('about', ['--only' => 'clean_architecture']))
        ->toMatch('/Contexts Cached[ .]+yes\s*$/m');
});

test('publishes the config file and the stubs', function () {
    $config = ServiceProvider::pathsToPublish(CleanArchitectureServiceProvider::class, 'clean-architecture-config');

    expect($config)->toHaveCount(1)
        ->and(realpath((string) array_key_first($config)))->toBe(realpath(__DIR__ . '/../../config/clean-architecture.php'))
        ->and(array_values($config)[0])->toBe(config_path('clean-architecture.php'));

    $stubs = ServiceProvider::pathsToPublish(CleanArchitectureServiceProvider::class, 'clean-architecture-stubs');

    expect($stubs)->toHaveCount(1)
        ->and(realpath((string) array_key_first($stubs)))->toBe(realpath(__DIR__ . '/../../stubs'))
        ->and(array_values($stubs)[0])->toBe(base_path(PackageConfig::stubsPath()));

    // Publishing the stubs lands them in the configured stubs path, byte for byte.
    $this->artisan('vendor:publish', ['--tag' => 'clean-architecture-stubs'])->assertSuccessful();

    $target = base_path(PackageConfig::stubsPath());
    expect(file_exists("$target/entity.stub"))->toBeTrue()
        ->and(file_get_contents("$target/entity.stub"))->toBe(file_get_contents(__DIR__ . '/../../stubs/entity.stub'));
});
