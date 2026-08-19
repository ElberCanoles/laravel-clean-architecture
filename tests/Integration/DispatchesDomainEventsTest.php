<?php

use CleanArchitecture\Support\DispatchesDomainEvents;
use CleanArchitecture\Support\HasDomainEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

test('dispatches events from entity via event helper', function () {
    Event::fake();

    $event = new class {};

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

    $entity = new class {};

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

    $event = new class {};

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

test('events are deferred until the transaction commits', function () {
    config(['database.default' => 'testing']);
    Event::fake();

    $event = new class {};

    $entity = new class($event) implements HasDomainEvents
    {
        public function __construct(private object $event) {}

        public function releaseEvents(): array
        {
            return [$this->event];
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

    DB::transaction(function () use ($repo, $entity, $event) {
        $repo->dispatch($entity);

        // Inside the transaction nothing has fired yet.
        Event::assertNotDispatched(get_class($event));
    });

    // After the commit, the event is out.
    Event::assertDispatched(get_class($event));
});

test('events are discarded when the transaction rolls back', function () {
    config(['database.default' => 'testing']);
    Event::fake();

    $event = new class {};

    $entity = new class($event) implements HasDomainEvents
    {
        public function __construct(private object $event) {}

        public function releaseEvents(): array
        {
            return [$this->event];
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

    try {
        DB::transaction(function () use ($repo, $entity) {
            $repo->dispatch($entity);

            throw new RuntimeException('force rollback');
        });
    } catch (RuntimeException) {
        // expected
    }

    Event::assertNotDispatched(get_class($event));
});
