<?php

declare(strict_types=1);

use CleanArchitecture\Kernel\PackageConfig;

test('falls back to the package defaults when a key is missing', function () {
    $all = config('clean-architecture');
    unset($all['auto_discover'], $all['auto_load'], $all['render_domain_exceptions'], $all['id_type']);
    config()->set('clean-architecture', $all);

    expect(PackageConfig::autoDiscover())->toBeTrue()
        ->and(PackageConfig::autoLoad())->toBeTrue()
        ->and(PackageConfig::rendersDomainExceptions())->toBeTrue()
        ->and(PackageConfig::idType())->toBe('uuid');
});

test('keeps the truthiness of values that are not booleans', function (mixed $value, bool $expected) {
    config()->set('clean-architecture.auto_discover', $value);

    expect(PackageConfig::autoDiscover())->toBe($expected);
})->with([
    'string false' => ['false', false],
    'string zero' => ['0', false],
    'string true' => ['true', true],
    'integer one' => [1, true],
    'integer zero' => [0, false],
]);

test('ignores a string option set to the wrong type', function () {
    config()->set('clean-architecture.id_type', ['ulid']);
    config()->set('clean-architecture.contexts_path', 42);

    expect(PackageConfig::idType())->toBe('uuid')
        ->and(PackageConfig::contextsPath())->toBe('src');
});
