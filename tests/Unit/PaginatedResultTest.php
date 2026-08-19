<?php

declare(strict_types=1);

use CleanArchitecture\Support\PaginatedResult;

test('exposes items and pagination metadata', function () {
    $result = new PaginatedResult(items: ['a', 'b'], total: 12, page: 2, perPage: 2);

    expect($result->items)->toBe(['a', 'b'])
        ->and($result->meta())->toBe([
            'total' => 12,
            'page' => 2,
            'per_page' => 2,
        ]);
});

test('holds an empty page', function () {
    $result = new PaginatedResult(items: [], total: 0, page: 1, perPage: 15);

    expect($result->items)->toBe([])
        ->and($result->meta()['total'])->toBe(0);
});
