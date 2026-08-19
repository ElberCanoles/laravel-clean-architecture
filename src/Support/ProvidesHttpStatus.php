<?php

declare(strict_types=1);

namespace CleanArchitecture\Support;

/**
 * Lets a domain exception declare the HTTP status it should render as,
 * without coupling the Domain layer to the framework.
 */
interface ProvidesHttpStatus
{
    public function httpStatus(): int;
}
