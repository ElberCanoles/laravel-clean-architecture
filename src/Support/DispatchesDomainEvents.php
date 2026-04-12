<?php

namespace CleanArchitecture\Support;

trait DispatchesDomainEvents
{
    protected function dispatchDomainEvents(object $entity): void
    {
        if (! $entity instanceof HasDomainEvents) {
            return;
        }

        foreach ($entity->releaseEvents() as $event) {
            event($event);
        }
    }
}
