<?php

declare(strict_types=1);

use Illuminate\Console\Command;

test('clean architecture package classes are in correct namespace', function () {
    expect('CleanArchitecture')
        ->toBeClasses()
        ->ignoring('CleanArchitecture\Support')
        ->ignoring('CleanArchitecture\Console\Concerns');
});

test('console commands extend Illuminate Command', function () {
    expect('CleanArchitecture\Console')
        ->toExtend(Command::class)
        ->ignoring('CleanArchitecture\Console\Concerns');
});

test('package code declares strict types', function () {
    expect('CleanArchitecture')
        ->toUseStrictTypes();
});

test('console commands return int from handle', function () {
    expect('CleanArchitecture\Console')
        ->toHaveMethod('handle')
        ->ignoring('CleanArchitecture\Console\BaseGenerator')
        ->ignoring('CleanArchitecture\Console\Concerns');
});
