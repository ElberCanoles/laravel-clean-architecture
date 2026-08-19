<?php

declare(strict_types=1);

use CleanArchitecture\Support\CompositeSpecification;
use CleanArchitecture\Support\Specification;

function spec(bool $result): CompositeSpecification
{
    return new class($result) extends CompositeSpecification
    {
        public function __construct(private readonly bool $result) {}

        public function isSatisfiedBy(mixed $candidate): bool
        {
            return $this->result;
        }
    };
}

test('specifications of different classes can be composed', function () {
    $paid = spec(true);
    $overdue = spec(false);

    expect($paid->and($overdue)->isSatisfiedBy(null))->toBeFalse();
    expect($paid->or($overdue)->isSatisfiedBy(null))->toBeTrue();
    expect($overdue->not()->isSatisfiedBy(null))->toBeTrue();
});

test('three-term chains do not crash', function () {
    $a = spec(true);
    $b = spec(true);
    $c = spec(false);

    expect($a->and($b)->and($c)->isSatisfiedBy(null))->toBeFalse();
    expect($a->and($b)->or($c)->isSatisfiedBy(null))->toBeTrue();
    expect($a->and($b->or($c))->not()->isSatisfiedBy(null))->toBeFalse();
});

test('composites implement the shared Specification contract', function () {
    expect(spec(true)->and(spec(true)))->toBeInstanceOf(Specification::class);
    expect(spec(true)->or(spec(true)))->toBeInstanceOf(Specification::class);
    expect(spec(true)->not())->toBeInstanceOf(Specification::class);
});

test('truth table for and, or, not', function (bool $left, bool $right) {
    expect(spec($left)->and(spec($right))->isSatisfiedBy(null))->toBe($left && $right);
    expect(spec($left)->or(spec($right))->isSatisfiedBy(null))->toBe($left || $right);
    expect(spec($left)->not()->isSatisfiedBy(null))->toBe(! $left);
})->with([
    [true, true],
    [true, false],
    [false, true],
    [false, false],
]);
