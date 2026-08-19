<?php

declare(strict_types=1);

namespace CleanArchitecture\Support;

/**
 * Base class for aggregate roots: records domain events for meaningful state
 * changes and releases them once, when the repository persists the aggregate.
 */
abstract class AggregateRoot implements HasDomainEvents
{
    /** @var list<object> */
    private array $domainEvents = [];

    protected function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
