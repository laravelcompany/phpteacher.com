---
title: "PSR-14 Event Dispatcher in PHP - A Complete Guide"
description: "Learn PSR-14 Event Dispatcher for PHP. Covers events, listeners, dispatchers, provider patterns, and practical examples for decoupled application design."
pubDate: "2023-01-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "PSR-14", "Event Dispatcher", "Design Patterns", "Architecture", "Decoupling"]
selected: false
---

Every non-trivial PHP application has moments when one piece of code needs to notify other pieces of code that something happened. A user registers — send a welcome email. An order is placed — update inventory, notify shipping, charge the card. A blog post is published — invalidate the cache, ping the RSS feed, notify subscribers.

The naive approach is to hardcode these notifications directly into the business logic. The `UserController` calls `$mailer->sendWelcomeEmail()`. The `OrderController` calls `$inventoryService->deductStock()`. This works until it does not. Every new notification requires modifying the controller. Every feature becomes harder to test. Every deployment risks breaking something unrelated.

PSR-14, the PHP Event Dispatcher standard, solves this problem by formalizing a publish-subscribe pattern within your application. Events become first-class objects. Listeners respond to events without the emitter knowing about them. Your application becomes extensible, testable, and decoupled.

This guide explains PSR-14 from the ground up. It covers the core concepts, the interfaces, the implementation details, and practical patterns for using events in PHP applications.

## What You'll Learn

- The core concepts of PSR-14: events, listeners, dispatchers, and listener providers
- How the Event Dispatcher standard decouples application components
- How to implement PSR-14 in Laravel, Symfony, and vanilla PHP
- The difference between stoppable and non-stoppable events
- How to structure listener providers for complex applications
- Real-world event-driven patterns for PHP projects
- Common pitfalls and how to avoid them

## The Core Concepts of PSR-14

PSR-14 defines four primary concepts that work together to enable event-driven architecture.

### Events

An event is an immutable message that describes something that happened in your application. Events are plain PHP objects — they contain data but no behavior beyond what their constructor and accessor methods provide.

```php
<?php

use Psr\EventDispatcher\StoppableEventInterface;

class UserRegistered
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}
}
```

**Explanation:** This event captures that a user registered. It contains the user ID, email, and a timestamp. The properties are readonly, making the event immutable. Once created, the event cannot be modified — this is a PSR-14 requirement.

Events can implement `StoppableEventInterface` if they support propagation stopping. When a listener calls `stopPropagation()`, no further listeners receive the event.

```php
<?php

use Psr\EventDispatcher\StoppableEventInterface;

class OrderPlaced implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        public readonly string $orderId,
        public readonly float $total,
        public readonly string $customerEmail
    ) {}

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
```

**Explanation:** This event supports propagation stopping. A validation listener might stop propagation if the order is fraudulent. Subsequent listeners never execute, preventing unnecessary processing.

### Listeners

Listeners are callables that handle events. A listener receives an event, processes it, and returns nothing. Listeners should be stateless — they should not depend on data from previous invocations.

```php
<?php

class SendWelcomeEmailListener
{
    public function __construct(
        private Mailer $mailer,
        private string $fromAddress
    ) {}

    public function __invoke(UserRegistered $event): void
    {
        $this->mailer->send(
            to: $event->email,
            from: $this->fromAddress,
            subject: 'Welcome to our platform!',
            body: "Thanks for registering, {$event->userId}!"
        );
    }
}
```

**Explanation:** This listener sends a welcome email when a user registers. It receives the `UserRegistered` event, extracts the email address, and sends the message. The listener is a callable class — the `__invoke` method makes it directly usable as a listener.

### The Emitter

The emitter is any code that creates and dispatches an event. In practice, the emitter calls the dispatcher with an event object. The emitter does not know which listeners will handle the event — that separation is the entire point of the pattern.

```php
<?php

class UserRegistrationService
{
    public function __construct(
        private UserRepository $users,
        private EventDispatcher $dispatcher
    ) {}

    public function register(string $email, string $password): User
    {
        $user = $this->users->create($email, $password);

        $event = new UserRegistered(
            userId: $user->id,
            email: $user->email
        );

        $this->dispatcher->dispatch($event);

        return $user;
    }
}
```

**Explanation:** The registration service creates the user, then dispatches a `UserRegistered` event. It does not know about welcome emails, analytics tracking, or any other side effects. New features are added by creating new listeners, not by modifying this service.

### The Dispatcher

The dispatcher is the central service that routes events to their listeners. It receives an event from the emitter, retrieves the appropriate listeners from the listener provider, and calls each listener with the event.

```php
<?php

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class SimpleEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private ListenerProvider $listenerProvider
    ) {}

    public function dispatch(object $event): object
    {
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }
}
```

**Explanation:** This dispatcher iterates through all listeners returned by the provider. If the event is stoppable and propagation has been stopped, it breaks the loop. It always returns the same event object — immutability guarantees that listeners receive and return the same instance.

### The Listener Provider

The listener provider is a repository that determines which listeners should handle a given event. This separation allows for sophisticated listener resolution strategies.

```php
<?php

use Psr\EventDispatcher\ListenerProviderInterface;

class ConfigurableListenerProvider implements ListenerProviderInterface
{
    private array $listeners = [];

    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventClass][$priority][] = $listener;
        krsort($this->listeners[$eventClass], SORT_NUMERIC);
    }

    public function getListenersForEvent(object $event): iterable
    {
        $class = get_class($event);
        $matched = [];

        if (isset($this->listeners[$class])) {
            foreach ($this->listeners[$class] as $priorityGroup) {
                array_push($matched, ...$priorityGroup);
            }
        }

        return $matched;
    }
}
```

**Explanation:** This provider stores listeners indexed by event class and priority. Higher priority listeners execute first. The `getListenersForEvent` method flattens all priority groups for the matching event class into a single iterable. This design allows fine-grained control over execution order.

## How PSR-14 Works End to End

Let's trace a complete flow through a PSR-14 system using a movie ticket purchasing example.

When a customer buys tickets, the application needs to:
1. Send a confirmation email
2. Generate a QR code for entry
3. Notify the theater of the sale
4. Apply a promotional gift for frequent buyers

Without PSR-14, the checkout service would contain code for all four of these responsibilities. With PSR-14, the checkout service dispatches a single `TicketsPurchased` event, and each responsibility becomes a separate listener.

```php
<?php

class TicketsPurchased
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerEmail,
        public readonly string $movieTitle,
        public readonly \DateTimeImmutable $showTime,
        public readonly int $ticketCount,
        public readonly float $totalAmount
    ) {}
}
```

Listeners register for this event:

```php
<?php

class SendConfirmationEmailListener
{
    public function __construct(private Mailer $mailer) {}

    public function __invoke(TicketsPurchased $event): void
    {
        $this->mailer->send(
            to: $event->customerEmail,
            subject: "Your tickets for {$event->movieTitle}",
            body: "You purchased {$event->ticketCount} tickets for {$event->movieTitle} at {$event->showTime->format('g:i A')}."
        );
    }
}

class GenerateQrCodeListener
{
    public function __construct(private QrCodeService $qrService) {}

    public function __invoke(TicketsPurchased $event): void
    {
        $this->qrService->generateForOrder($event->orderId);
    }
}

class NotifyTheaterListener
{
    public function __construct(private TheaterNotificationService $theaterService) {}

    public function __invoke(TicketsPurchased $event): void
    {
        $this->theaterService->notifySale(
            movie: $event->movieTitle,
            showTime: $event->showTime,
            tickets: $event->ticketCount
        );
    }
}
```

The checkout service becomes clean:

```php
<?php

class TicketCheckoutService
{
    public function __construct(
        private TicketRepository $tickets,
        private PaymentGateway $payments,
        private EventDispatcher $dispatcher
    ) {}

    public function purchaseTickets(array $seats, string $customerEmail): string
    {
        $orderId = $this->tickets->reserve($seats);
        $total = $this->tickets->calculateTotal($seats);

        $this->payments->charge($orderId, $total);

        $event = new TicketsPurchased(
            orderId: $orderId,
            customerEmail: $customerEmail,
            movieTitle: 'The PHP Movie',
            showTime: new \DateTimeImmutable('2024-03-15 19:00:00'),
            ticketCount: count($seats),
            totalAmount: $total
        );

        $this->dispatcher->dispatch($event);

        return $orderId;
    }
}
```

**Explanation:** The checkout service focuses on the core business logic — reserving tickets and processing payment. Side effects like emails, QR codes, and theater notifications are handled by listeners. Adding a new side effect (like sending SMS confirmations) requires creating a new listener, not modifying the checkout service.

## PSR-14 in Popular PHP Frameworks

### Laravel

Laravel's event system predates PSR-14 but follows similar principles. Laravel 11 continues to use its own event implementation with `Event::listen()` and `Event::dispatch()`.

```php
<?php

// app/Providers/EventServiceProvider.php
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [
            SendWelcomeEmailListener::class,
            LogUserRegistrationListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
```

Laravel supports queued listeners for expensive operations:

```php
<?php

class SendWelcomeEmailListener implements ShouldQueue
{
    use Queueable;

    public function handle(UserRegistered $event): void
    {
        Mail::to($event->email)->send(new WelcomeMail($event->userId));
    }
}
```

**Explanation:** Laravel's `ShouldQueue` interface tells the framework to dispatch this listener's execution to the queue. The welcome email is sent asynchronously, keeping the HTTP response fast.

### Symfony

Symfony's EventDispatcher component was a major influence on PSR-14. The Symfony implementation is fully PSR-14 compatible.

```php
<?php

// config/services.yaml
services:
    App\EventListener\SendWelcomeEmailListener:
        tags:
            - { name: kernel.event_listener, event: App\Event\UserRegistered, priority: 10 }
```

In PHP 8 attribute style:

```php
<?php

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: UserRegistered::class, priority: 10)]
class SendWelcomeEmailListener
{
    public function __invoke(UserRegistered $event): void
    {
        // Send the email
    }
}
```

### Standalone PHP

For applications not using a framework, the `php-enqueue/psr14-event-dispatcher` package provides a standalone PSR-14 implementation:

```bash
composer require php-enqueue/psr14-event-dispatcher
```

```php
<?php

use PhpEnqueue\Psr14\SimpleEventDispatcher;
use PhpEnqueue\Psr14\SimpleListenerProvider;

$provider = new SimpleListenerProvider();
$provider->addListener(UserRegistered::class, new SendWelcomeEmailListener($mailer));
$provider->addListener(UserRegistered::class, new LogUserRegistrationListener($logger));

$dispatcher = new SimpleEventDispatcher($provider);

// Dispatch
$dispatcher->dispatch(new UserRegistered(userId: '123', email: 'user@example.com'));
```

**Explanation:** This standalone setup works in any PHP application. The listener provider and dispatcher are instantiated directly, wired together, and used without framework integration.

## Real-World Use Cases

### SaaS Subscription Management

A SaaS application uses events to handle the complete subscription lifecycle. When a subscription is created, updated, or canceled, events trigger provisioning, billing, notifications, and analytics. Each of these side effects is a separate listener, making the subscription service testable without simulating email delivery or API calls.

### E-commerce Order Processing

An e-commerce platform dispatches events at every stage of the order lifecycle. `OrderPlaced`, `PaymentConfirmed`, `ItemShipped`, and `OrderCompleted` events each have multiple listeners. Inventory updates, customer notifications, accounting exports, and shipping label generation are all listener responsibilities.

### Content Management Systems

Content publishing events trigger cache invalidation, RSS feed regeneration, search index updates, and notification dispatch. Each listener operates independently. If the search service is down, content can still be published — the search indexing fails silently while other listeners succeed.

### Multi-Tenant Applications

Multi-tenant applications use events to decouple tenant-specific logic from core operations. When a new tenant provisions, events trigger tenant database creation, default configuration loading, admin user creation, and onboarding email dispatch.

## Best Practices

### Keep Events Immutable

Events should be read-only after construction. Use readonly properties or private properties with getters. Immutability guarantees that listeners see consistent data and prevents side effects from propagating through the event object.

### Name Events in Past Tense

Events describe something that already happened. Use past tense names like `UserRegistered`, `OrderPlaced`, or `PaymentFailed`. This mental model helps distinguish events from commands or requests.

### Use Typed Events

Create specific event classes for each domain event. Avoid generic `Event` classes with a string name and an array payload. Typed events are self-documenting, support static analysis, and make listener registration type-safe.

### Keep Events Small

Include only the data listeners need. If a listener needs the user ID, do not include the entire user object. This prevents accidental coupling to the user model's internal structure and makes testing easier.

### Register Listeners Declaratively

Use configuration or attributes to register listeners, not programmatic calls scattered across the codebase. Declarative registration makes it easy to see all listeners for a given event in one place.

### Use Queued Listeners for Slow Operations

Email sending, API calls, and file generation should be queued. The dispatcher returns immediately, and these slow operations execute asynchronously. This keeps the main request fast.

### Avoid Event Cascading

A listener should not dispatch events that trigger the same listener, creating infinite loops. If a listener needs to dispatch events, ensure the dispatched event is different from the one being handled, or use a loop detection mechanism.

## Common Mistakes to Avoid

**Mutating events in listeners.** A listener modifies the event object, changing data that subsequent listeners see. This creates hidden coupling between listeners. Events should be immutable.

**Throwing exceptions in listeners.** An exception in one listener prevents subsequent listeners from executing. Wrap listener execution in try-catch blocks or use a dispatcher that handles exceptions gracefully.

**Using events for request-response flows.** Events are fire-and-forget. If you need a response from a listener, use a different pattern like middleware or pipelines.

**Registering too many listeners on one event.** Fifty listeners on a single `UserRegistered` event is a code smell. Consider splitting the event into more granular domain events.

**Putting business logic in listeners.** Listeners should orchestrate side effects, not contain business logic. If a listener contains complex decision-making, move that logic into a service and call it from the listener.

**Forgetting to return the event.** The dispatcher must return the same event object. Returning a different object breaks the PSR-14 contract and surprises consumers.

## Frequently Asked Questions

### What is the difference between PSR-14 and the observer pattern?

PSR-14 formalizes the observer pattern with standardized interfaces. The observer pattern is a general concept. PSR-14 specifies exactly how events, listeners, dispatchers, and providers should interact, enabling interoperability between different PHP libraries and frameworks.

### Can PSR-14 events be asynchronous?

PSR-14 specifies synchronous dispatch. Listeners are called in order during the dispatch call. Asynchronous processing is achieved by queuing listeners — the listener itself dispatches a job to a queue worker rather than executing the work synchronously.

### How does PSR-14 differ from PSR-14 Event Dispatcher?

There is only one PSR-14 standard. It is formally called "PSR-14: Event Dispatcher." It covers the interfaces for event dispatching. It is the same standard.

### Is PSR-14 compatible with Laravel events?

Laravel's event system was created before PSR-14 and is not directly compatible. However, Laravel follows the same conceptual pattern. Adapters exist to bridge Laravel events with PSR-14 dispatchers.

### What is a stoppable event?

A stoppable event implements `StoppableEventInterface` and can prevent further listener execution. When a listener calls `stopPropagation()`, the dispatcher stops iterating through remaining listeners. This is useful for validation events or early-exit scenarios.

### How do I test event-driven code?

Test the emitter in isolation by mocking the dispatcher and asserting that the correct event was dispatched. Test each listener by calling it directly with a fake event and asserting the expected side effects occurred.

### Should I use events for logging?

Logging is a cross-cutting concern better handled by middleware or decorators. Events are for domain-level notifications that other parts of the application need to react to.

### Can one listener handle multiple event types?

Yes. Listeners can be registered for multiple events. In Symfony, the `__invoke` method uses type hints to determine which event to handle. A single class can have multiple handler methods for different events.

### What happens if a listener throws an exception?

It depends on the dispatcher implementation. Some dispatchers catch exceptions and continue. Others let the exception propagate, preventing remaining listeners from executing. Choose or implement a dispatcher that matches your error handling strategy.

### Do I need PSR-14 for small applications?

No. PSR-14 becomes valuable when your application has multiple side effects per operation or when you need extensibility. A small application with one or two side effects can hardcode them directly without significant maintenance burden. Introduce events when you feel the pain of tight coupling.

## Conclusion

PSR-14 provides a standardized approach to event-driven programming in PHP. Events, listeners, dispatchers, and listener providers work together to decouple your application's core business logic from its side effects. Your code becomes easier to test, easier to extend, and easier to reason about.

The movie ticket example demonstrates the pattern's value. Adding SMS confirmation or loyalty point tracking requires creating a new listener and registering it — zero changes to the checkout service. This extensibility is the hallmark of well-architected event-driven code.

Start by identifying the operations in your application that trigger multiple side effects. Extract those side effects into listeners. Introduce a dispatcher. Watch your codebase become more modular with each event you introduce.

**Ready to decouple your PHP application?** Identify one operation in your codebase that triggers multiple side effects — a user registration, an order placement, or a content publish — and refactor it to use the PSR-14 pattern. Start small, and expand event coverage as you see the benefits.

