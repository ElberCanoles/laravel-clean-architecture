<?php

use Illuminate\Console\Command;

test('clean architecture package classes are in correct namespace', function () {
    expect('CleanArchitecture')
        ->toBeClasses()
        ->ignoring('CleanArchitecture\Support');
});

test('console commands extend Illuminate Command', function () {
    expect('CleanArchitecture\Console')
        ->toExtend(Command::class);
});

test('package code declares strict types', function () {
    expect('CleanArchitecture')
        ->toUseStrictTypes();
});

test('console commands return int from handle', function () {
    expect('CleanArchitecture\Console')
        ->toHaveMethod('handle')
        ->ignoring('CleanArchitecture\Console\BaseGenerator');
});
