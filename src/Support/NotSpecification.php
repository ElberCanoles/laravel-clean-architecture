<?php

declare(strict_types=1);

namespace CleanArchitecture\Support;

final class NotSpecification extends CompositeSpecification
{
    public function __construct(
        private readonly Specification $inner,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return ! $this->inner->isSatisfiedBy($candidate);
    }
}
