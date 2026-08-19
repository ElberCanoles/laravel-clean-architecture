<?php

declare(strict_types=1);

namespace CleanArchitecture\Support;

use Illuminate\Support\Facades\DB;

trait DispatchesDomainEvents
{
    protected function dispatchDomainEvents(object $entity): void
    {
        if (! $entity instanceof HasDomainEvents) {
            return;
        }

        foreach ($entity->releaseEvents() as $event) {
            // Fires immediately outside a transaction; inside one it is
            // deferred until the outermost commit and discarded on rollback,
            // so listeners never react to writes that were rolled back.
            DB::afterCommit(static fn () => event($event));
        }
    }
}
