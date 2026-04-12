<?php

use CleanArchitecture\Support\DispatchesDomainEvents;
use CleanArchitecture\Support\HasDomainEvents;
use Illuminate\Support\Facades\Event;

test('dispatches events from entity via event helper', function () {
    Event::fake();

    $event = new class
    {
    };

    $entity = new class($event) implements HasDomainEvents
    {
        private array $events;

        public function __construct(object $event)
        {
            $this->events = [$event];
        }

        public function releaseEvents(): array
        {
            $events = $this->events;
            $this->events = [];

            return $events;
        }
    };

    $repo = new class
    {
        use DispatchesDomainEvents;

        public function dispatch(object $entity): void
        {
            $this->dispatchDomainEvents($entity);
        }
    };

    $repo->dispatch($entity);

    Event::assertDispatched(get_class($event));
});

test('does nothing when entity does not implement HasDomainEvents', function () {
    Event::fake();

    $entity = new class
    {
    };

    $repo = new class
    {
        use DispatchesDomainEvents;

        public function dispatch(object $entity): void
        {
            $this->dispatchDomainEvents($entity);
        }
    };

    $repo->dispatch($entity);

    Event::assertNothingDispatched();
});

test('clears events after dispatching to prevent double dispatch', function () {
    Event::fake();

    $event = new class
    {
    };

    $entity = new class($event) implements HasDomainEvents
    {
        private array $events;

        public function __construct(object $event)
        {
            $this->events = [$event];
        }

        public function releaseEvents(): array
        {
            $events = $this->events;
            $this->events = [];

            return $events;
        }
    };

    $repo = new class
    {
        use DispatchesDomainEvents;

        public function dispatch(object $entity): void
        {
            $this->dispatchDomainEvents($entity);
        }
    };

    $repo->dispatch($entity);
    $repo->dispatch($entity);

    Event::assertDispatchedTimes(get_class($event), 1);
});
