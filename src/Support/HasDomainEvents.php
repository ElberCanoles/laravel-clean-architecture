<?php

declare(strict_types=1);

namespace CleanArchitecture\Support;

interface HasDomainEvents
{
    /** @return object[] */
    public function releaseEvents(): array;
}
