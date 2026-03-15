<?php

test('clean architecture package classes are in correct namespace', function () {
    expect('CleanArchitecture')
        ->toBeClasses();
});

test('console commands extend Illuminate Command', function () {
    expect('CleanArchitecture\Console')
        ->toExtend(\Illuminate\Console\Command::class);
});
