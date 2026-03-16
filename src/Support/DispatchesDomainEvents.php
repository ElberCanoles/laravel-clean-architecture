<?php

namespace CleanArchitecture\Support;

trait DispatchesDomainEvents
{
    protected function dispatchDomainEvents(object $entity): void
    {
        if (! method_exists($entity, 'releaseEvents')) {
            return;
        }

        foreach ($entity->releaseEvents() as $event) {
            event($event);
        }
    }
}
