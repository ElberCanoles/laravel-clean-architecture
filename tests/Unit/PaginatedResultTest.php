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
            'last_page' => 6,
        ]);
});

test('holds an empty page', function () {
    $result = new PaginatedResult(items: [], total: 0, page: 1, perPage: 15);

    expect($result->items)->toBe([])
        ->and($result->meta()['total'])->toBe(0);
});

test('computes last page and whether more pages exist', function () {
    $result = new PaginatedResult(items: [], total: 12, page: 2, perPage: 5);

    expect($result->lastPage())->toBe(3)
        ->and($result->hasMorePages())->toBeTrue();

    $last = new PaginatedResult(items: [], total: 12, page: 3, perPage: 5);
    expect($last->hasMorePages())->toBeFalse();

    $empty = new PaginatedResult(items: [], total: 0, page: 1, perPage: 15);
    expect($empty->lastPage())->toBe(1)
        ->and($empty->hasMorePages())->toBeFalse();
});

test('rejects invalid pagination arguments', function (int $total, int $page, int $perPage) {
    expect(fn () => new PaginatedResult(items: [], total: $total, page: $page, perPage: $perPage))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'negative total' => [-1, 1, 15],
    'zero page' => [0, 0, 15],
    'zero perPage' => [0, 1, 0],
]);
