<?php

declare(strict_types=1);

namespace CleanArchitecture\Support;

interface Specification
{
    public function isSatisfiedBy(mixed $candidate): bool;
}
