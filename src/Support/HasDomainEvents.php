<?php

namespace CleanArchitecture\Support;

interface HasDomainEvents
{
    /** @return object[] */
    public function releaseEvents(): array;
}
