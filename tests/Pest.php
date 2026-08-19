<?php

declare(strict_types=1);

use CleanArchitecture\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Assert;

uses(TestCase::class)->in('Integration');

/*
|--------------------------------------------------------------------------
| Custom Expectations
|--------------------------------------------------------------------------
*/

// Asserts that the file at the given path is syntactically valid PHP, so a
// broken stub can never ship while the suite stays green on substring checks.
expect()->extend('toBeValidPhp', function () {
    $path = $this->value;
    $output = [];
    $exitCode = 1;

    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1', $output, $exitCode);

    Assert::assertSame(
        0,
        $exitCode,
        "PHP syntax error in {$path}:\n" . implode("\n", $output)
    );

    return $this;
});

/*
|--------------------------------------------------------------------------
| Shared Helpers
|--------------------------------------------------------------------------
*/

function publishStub(string $name, string $contents): void
{
    $dir = base_path('stubs/clean-architecture');
    File::makeDirectory($dir, 0755, true, true);
    File::put("$dir/$name.stub", $contents);
}
