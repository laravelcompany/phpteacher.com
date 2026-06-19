---
title: "Event-Driven Programming in PHP - Patterns and Practical Examples"
description: "Learn event-driven programming patterns in PHP. From event dispatchers to domain events, build loosely coupled, extensible applications with event-driven architecture."
pubDate: "2022-06-14 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Event-Driven", "Event Dispatcher", "Architecture", "Domain Events", "PSR-14", "Symfony"]
selected: false
---

You ship a new feature. A week later, the product team wants to run a campaign when a user hits a milestone. Two weeks after that, marketing needs to fire a webhook when an order is placed. Then compliance wants an audit log for every payment. Then customer success needs an email sequence triggered by login frequency.

Each request is reasonable. Each one is a Sunday afternoon of adding a `Mailchimp::subscribe()` call here and a `Slack::notify()` call there. After six months, your "place order" method is 400 lines long, and nobody knows what side effects it has.

There's a better way.

Event-driven programming decouples the thing that happens from the things that should happen next. Your core business logic fires an event and moves on. Listeners pick up that event and do their work independently. The result is a codebase that bends—not breaks—under changing requirements.

## What Is Event-Driven Programming?

Event-driven programming is a paradigm where the flow of a program is determined by events—significant occurrences that your application needs to respond to. Instead of calling code directly, your application emits events and lets interested parties react.

Three core concepts make this work:

**Events** are messages that represent something that happened. A user registered. An order was placed. A payment failed. Events are immutable records of past occurrences. Their names use the past tense because the thing already happened—you can't change it, only react to it.

**Listeners (or subscribers)** are units of code that respond to specific events. A listener receives the event object and performs an action: send an email, update a cache, log to a file, dispatch a webhook. Multiple listeners can respond to the same event, and no listener knows about the others.

**Dispatchers** are the central nervous system. They receive events, match them to registered listeners, and invoke each listener in order. The dispatcher is the only piece of the system that knows both sides exist.

Here's how they interact:

```php
// The dispatcher orchestrates everything
$dispatcher = new EventDispatcher();

// Register listeners
$dispatcher->addListener(UserRegistered::class, new SendWelcomeEmail());
$dispatcher->addListener(UserRegistered::class, new CreateSlackChannel());

// Fire an event
$dispatcher->dispatch(new UserRegistered(
    userId: 42,
    email: 'user@example.com',
    name: 'Jane Doe',
));

// Both listeners run. Neither knows about the other.
// The code that dispatched the event doesn't know what listeners exist.
```

The code that dispatched `UserRegistered` has no idea that welcome emails or Slack channels exist. It just says "a user registered." The listeners handle the rest. This is the essence of event-driven programming: the emitter and the reactor don't know each other exist.

## Events as First-Class Citizens

An event is a plain PHP object. It carries data relevant to what happened and nothing else. No behavior. No business logic. Just state.

```php
final class UserRegistered
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $name,
        public readonly \DateTimeImmutable $occurredAt,
    ) {
    }
}

final class OrderPlaced
{
    public function __construct(
        public readonly string $orderId,
        public readonly int $userId,
        public readonly array $lineItems,
        public readonly Money $total,
        public readonly \DateTimeImmutable $occurredAt,
    ) {
    }
}

final class PaymentReceived
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $orderId,
        public readonly Money $amount,
        public readonly \DateTimeImmutable $occurredAt,
    ) {
    }
}
```

Every event carries an `occurredAt` timestamp. This is not optional. Events represent something that happened at a specific point in time. Losing the temporal context means losing the meaning of the event itself.

Events use readonly properties and constructor promotion. They are immutable by design. Once created, an event cannot change. If something else happens, create a new event.

## PSR-14: The Event Dispatcher Standard

PHP-FIG recognized the need for interoperability in event-driven systems and released PSR-14 (`Psr\EventDispatcher`). It defines three interfaces:

- **`EventDispatcherInterface`** — dispatches an event to all registered listeners
- **`ListenerProviderInterface`** — provides listeners for a given event
- **`StoppableEventInterface`** — allows an event to stop propagation

The standard separates two concerns. The provider knows which listeners exist for which events. The dispatcher takes an event, asks the provider for relevant listeners, and calls each one. This separation lets you swap implementations without changing your application code.

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

// Your application code depends only on the interfaces
final class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function handle(RegisterUserCommand $command): User
    {
        $user = User::register(
            email: $command->email,
            name: $command->name,
        );

        $this->users->save($user);

        $this->dispatcher->dispatch(new UserRegistered(
            userId: $user->id,
            email: $user->email,
            name: $user->name,
        ));

        return $user;
    }
}
```

The use case depends on `EventDispatcherInterface`, not a concrete implementation. You can swap Symfony's dispatcher for a test double or a lightweight custom implementation without changing the use case. That's the value of a standard.

`StoppableEventInterface` lets an event halt propagation. If a validation listener determines that an order cannot be processed, it can mark the event as stopped, and no further listeners run:

```php
final class OrderPlaced implements StoppableEventInterface
{
    private bool $stopped = false;

    // ... constructor ...

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }
}

final class ValidateInventory
{
    public function __invoke(OrderPlaced $event): void
    {
        if (! $this->inventory->hasStock($event->lineItems)) {
            $event->stopPropagation();
            // Log insufficient inventory
        }
    }
}
```

## Building a Simple Event Dispatcher

Understanding the internals of an event dispatcher makes you a better architect. Here's a minimal implementation that covers the core concepts:

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

final class SimpleListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, array<callable>> */
    private array $listeners = [];

    public function addListener(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    /** @return iterable<callable> */
    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = $event::class;

        return $this->listeners[$eventClass] ?? [];
    }
}

final class SimpleEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly ListenerProviderInterface $provider,
    ) {
    }

    public function dispatch(object $event): object
    {
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }
}
```

Usage:

```php
$provider = new SimpleListenerProvider();
$dispatcher = new SimpleEventDispatcher($provider);

$provider->addListener(
    UserRegistered::class,
    function (UserRegistered $event): void {
        $this->mailer->sendWelcomeEmail($event->email);
    },
);

$provider->addListener(
    UserRegistered::class,
    function (UserRegistered $event): void {
        $this->slack->notifyChannel("#new-users", "{$event->name} registered!");
    },
);

$dispatcher->dispatch(new UserRegistered(
    userId: 1,
    email: 'alice@example.com',
    name: 'Alice',
));
```

This dispatcher does exactly what it needs to do. It gets listeners from the provider, iterates them, calls each one, and respects stoppable events. There's no magic, no configuration files, no dependency injection container required.

A production dispatcher would add error handling, priority-based ordering, and possibly asynchronous execution. But the core—foreach over listeners, call each one—is the same.

## The Symfony EventDispatcher Component

Symfony's EventDispatcher is the de facto standard in the PHP ecosystem. It predates PSR-14 and is fully compatible with it. Symfony extends the pattern with three key features: priority-based listeners, subscriber classes, and event naming using strings.

### Event Subscribers

Instead of registering listeners one at a time, a subscriber tells the dispatcher which events it handles:

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UserNotifier implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistered::class => ['onUserRegistered', 0],
            OrderPlaced::class => ['onOrderPlaced', 10],
            PaymentReceived::class => ['onPaymentReceived', -5],
        ];
    }

    public function onUserRegistered(UserRegistered $event): void
    {
        $this->mailer->sendWelcomeEmail($event->email);
    }

    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->mailer->sendConfirmation($event->orderId, $event->email);
    }

    public function onPaymentReceived(PaymentReceived $event): void
    {
        $this->mailer->sendReceipt($event->transactionId, $event->amount);
    }
}
```

The integer after the method name is the priority. Higher values run first. The default is zero. Listeners with priority 10 run before listeners with priority 0, which run before listeners with priority -5. This matters when one listener's output is another listener's input—for example, a normalization listener should run before a logging listener.

### Registering Subscribers

```php
use Symfony\Component\EventDispatcher\EventDispatcher;

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new UserNotifier());
$dispatcher->addSubscriber(new AuditLogger());

$dispatcher->dispatch(new UserRegistered(
    userId: 42,
    email: 'user@example.com',
    name: 'Jane Doe',
));
```

Subscribers make registration explicit and self-documenting. Open `UserNotifier` and you see exactly which events it handles and in what priority order.

## Domain Events in DDD

Domain-Driven Design formalizes the concept of domain events—events that domain experts care about. A domain event captures something meaningful that happened in the domain. It's not "button clicked" or "HTTP request received." It's "InvoicePaid" and "SubscriptionCancelled" and "InventoryThresholdReached."

Domain events serve two purposes within DDD:

**Recording what happened.** Every domain event is a permanent record. An `OrderShipped` event, stored in your database, lets you answer questions about what shipped, when, and to whom, without relying on current state.

**Cross-aggregate communication.** An aggregate (a cluster of domain objects treated as a unit) should not directly modify another aggregate. Instead, it emits a domain event. A separate process picks up that event and tells the other aggregate to update itself.

```php
final class Order
{
    private function __construct(
        private OrderId $id,
        private OrderStatus $status,
        private array $lineItems,
        private CustomerId $customerId,
        /** @var DomainEvent[] */
        private array $recordedEvents = [],
    ) {
    }

    public static function place(
        OrderId $id,
        CustomerId $customerId,
        array $lineItems,
    ): self {
        $order = new self($id, OrderStatus::PENDING, $lineItems, $customerId);

        $order->recordThat(new OrderPlaced(
            orderId: $id->toString(),
            customerId: $customerId->toString(),
            lineItems: array_map(
                fn(LineItem $item) => ['sku' => $item->sku, 'quantity' => $item->quantity],
                $lineItems,
            ),
        ));

        return $order;
    }

    public function markAsPaid(): void
    {
        if ($this->status !== OrderStatus::PENDING) {
            throw new \DomainException('Only pending orders can be paid.');
        }

        $this->status = OrderStatus::PAID;

        $this->recordThat(new OrderPaid(
            orderId: $this->id->toString(),
            customerId: $this->customerId->toString(),
        ));
    }

    /** @return DomainEvent[] */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
```

The aggregate records events internally and releases them after persistence. An infrastructure layer collects the released events and dispatches them through the event dispatcher:

```php
final class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        foreach ($order->releaseEvents() as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
```

Events are dispatched *after* persistence. If the database save fails, no events are dispatched. This prevents phantom events where your code acts on something that never actually persisted.

## Event Sourcing Concepts

Event sourcing takes domain events to their logical conclusion: instead of storing current state, store every event that led to the current state. The current state is derived by replaying all events.

```php
final class EventSourcedOrder
{
    private function __construct(
        private OrderId $id,
        private OrderStatus $status = OrderStatus::PENDING,
        private array $lineItems = [],
        private ?CustomerId $customerId = null,
    ) {
    }

    /** Rebuild from stored events */
    public static function fromEvents(OrderId $id, DomainEvent ...$events): self
    {
        $order = new self($id);

        foreach ($events as $event) {
            $order->apply($event);
        }

        return $order;
    }

    public static function place(OrderId $id, CustomerId $customerId, array $lineItems): self
    {
        $order = new self($id);
        $order->apply(new OrderPlaced(
            orderId: $id->toString(),
            customerId: $customerId->toString(),
            lineItems: $lineItems,
        ));

        return $order;
    }

    private function apply(DomainEvent $event): void
    {
        match ($event::class) {
            OrderPlaced::class => $this->applyOrderPlaced($event),
            OrderPaid::class => $this->applyOrderPaid($event),
            OrderShipped::class => $this->applyOrderShipped($event),
            OrderCancelled::class => $this->applyOrderCancelled($event),
            default => throw new \InvalidArgumentException('Unknown event: ' . $event::class),
        };
    }

    private function applyOrderPlaced(OrderPlaced $event): void
    {
        $this->status = OrderStatus::PENDING;
        $this->customerId = new CustomerId($event->customerId);
        $this->lineItems = $event->lineItems;
    }

    private function applyOrderPaid(OrderPaid $event): void
    {
        $this->status = OrderStatus::PAID;
    }

    private function applyOrderShipped(OrderShipped $event): void
    {
        $this->status = OrderStatus::SHIPPED;
    }

    private function applyOrderCancelled(OrderCancelled $event): void
    {
        $this->status = OrderStatus::CANCELLED;
    }
}
```

Event sourcing gives you a complete audit trail, temporal queries (what did the order look like on Tuesday?), and the ability to rebuild state from scratch. The cost is complexity: you need an event store, projection handlers, and careful schema management.

You don't need full event sourcing to benefit from domain events. Start with dispatching events after persistence. If your audit or rebuilding requirements grow, the events are already in place to support event sourcing later.

## Testing Event-Driven Code

Event-driven code is more testable than procedural side-effect code, but it demands a different testing strategy. Here's how to approach it.

### Unit Test Listeners in Isolation

A listener should be testable without the dispatcher:

```php
final class SendWelcomeEmailTest extends TestCase
{
    public function test_sends_welcome_email_on_registration(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                return $email->subject === 'Welcome!'
                    && $email->to === 'user@example.com';
            }));

        $listener = new SendWelcomeEmail($mailer);

        $listener(new UserRegistered(
            userId: 1,
            email: 'user@example.com',
            name: 'Jane',
        ));
    }
}
```

No dispatcher. No event provider. Just construct the listener with its dependencies, call it with an event, and assert the outcome.

### Test Event Emission

Test that your use cases emit the correct events:

```php
final class RegisterUserUseCaseTest extends TestCase
{
    public function test_dispatches_user_registered_event(): void
    {
        $events = [];
        $dispatcher = new class implements EventDispatcherInterface {
            public array $dispatched = [];

            public function dispatch(object $event): object
            {
                $this->dispatched[] = $event;
                return $event;
            }
        };

        $useCase = new RegisterUserUseCase(
            users: new InMemoryUserRepository(),
            dispatcher: $dispatcher,
        );

        $useCase->handle(new RegisterUserCommand(
            email: 'test@example.com',
            name: 'Test User',
        ));

        $this->assertCount(1, $dispatcher->dispatched);
        $this->assertInstanceOf(UserRegistered::class, $dispatcher->dispatched[0]);
        $this->assertSame('test@example.com', $dispatcher->dispatched[0]->email);
    }
}
```

An anonymous class implementing `EventDispatcherInterface` captures dispatched events without bootstrap overhead. Test what events were dispatched and that they carry the right data.

### Integration Test the Full Chain

Test the complete flow once: dispatch an event and verify that all listeners produce their intended side effects:

```php
final class UserRegistrationFlowTest extends TestCase
{
    public function test_full_registration_flow(): void
    {
        // Bootstrap real dispatcher with real listeners
        $dispatcher = $this->createDispatcher();

        $dispatcher->dispatch(new UserRegistered(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
        ));

        // Assert side effects
        $this->assertEmailSentTo('test@example.com');
        $this->assertSlackMessageSent('#new-users');
        $this->assertUserInCache(userId: 1);
    }
}
```

One integration test per major event confirms the wiring is correct. Everything else is faster, narrower unit tests.

## Common Pitfalls

Event-driven programming introduces new failure modes. Here are the ones that bite most developers.

### Memory Leaks from Accumulated Listeners

Registering listeners inside a request loop (in a long-running process like Swoole or ReactPHP) without clearing them causes listeners to accumulate. Each request adds more listeners to the provider, and they never get removed. After ten thousand requests, your dispatcher iterates ten thousand listeners for every event.

Fix: reset the provider between requests in long-running processes, or register global listeners once during boot and never dynamically add them.

### Circular Event Chains

Listener A dispatches Event X. Listener B handles Event X and dispatches Event Y. Listener C handles Event Y and dispatches Event X. Infinite loop.

```php
final class AuditListener
{
    public function __invoke(OrderPlaced $event): void
    {
        $this->dispatcher->dispatch(new EntryCreated(
            message: "Order {$event->orderId} placed",
        ));
    }
}

final class SearchIndexListener
{
    public function __invoke(EntryCreated $event): void
    {
        $this->dispatcher->dispatch(new OrderPlaced(
            orderId: $this->extractOrderId($event->message),
            // ...
        ));
    }
}
```

Prevent this with a recursion guard. Track the current dispatch depth or maintain a set of processed event IDs. If depth exceeds a threshold, throw an exception.

```php
final class RecursionGuardDispatcher implements EventDispatcherInterface
{
    private int $depth = 0;

    public function __construct(
        private readonly EventDispatcherInterface $inner,
        private readonly int $maxDepth = 10,
    ) {
    }

    public function dispatch(object $event): object
    {
        if ($this->depth >= $this->maxDepth) {
            throw new \RuntimeException(
                'Event dispatch depth exceeded. Possible circular event chain.',
            );
        }

        $this->depth++;

        try {
            return $this->inner->dispatch($event);
        } finally {
            $this->depth--;
        }
    }
}
```

### Too Many Events

Every `set` method emits an event. Every HTTP request produces fifty events. Your event log is useless because it's noise. Event-driven code becomes event-chaos code.

Not everything is an event. A domain event should represent something a business stakeholder cares about. "UserChangedPassword" is a domain event. "ButtonClicked" is not. "InvoiceOverdue" is a domain event. "CacheKeySet" is not.

If you find yourself dispatching dozens of events per request, step back. Ask: would a non-technical person describe this as a meaningful occurrence? If not, it probably doesn't need to be an event.

### Synchronous Listener Failures

By default, listeners run synchronously in the same process. If one listener throws an exception, subsequent listeners never run. The original dispatch also fails, potentially rolling back a database transaction.

```php
$dispatcher->dispatch(new OrderPlaced(/* ... */));
// Listener 1: SendEmail → throws because SMTP server is down
// Listener 2: UpdateInventory → never runs
// Listener 3: NotifyWarehouse → never runs
```

Solutions:

- Wrap each listener invocation in a try-catch so one failure doesn't silence the rest
- Use a queue for side effects that don't need immediate execution (email sending, webhook calls, PDF generation)
- Decide which listeners are critical (must succeed for the operation to be valid) and which are best-effort (fire and forget)

```php
// In a custom dispatcher
foreach ($listeners as $listener) {
    try {
        $listener($event);
    } catch (\Throwable $e) {
        $this->logger->error('Listener failed', [
            'listener' => $listener::class,
            'event' => $event::class,
            'exception' => $e,
        ]);
        // Continue to next listener
    }
}
```

Listeners that send emails, call external APIs, or generate reports should almost always be async. Put their work on a message queue and dispatch from a worker process. The event itself stays synchronous—it records what happened. The side effects are decoupled in time.

### Implicit Dependencies Between Listeners

Two listeners for the same event run in registration order. If Listener A normalizes data and Listener B sends it to a third party, they have an implicit dependency. Changing the registration order breaks the system silently.

Make dependencies explicit. If a listener needs processed data, process the data before dispatch, or create a specific event stage with separate priorities. Better yet, make each listener self-sufficient.

## Real-World Example: Order Processing Pipeline

Let's wire together a complete example. An order is placed. Multiple systems need to react.

```php
final class PlaceOrderUseCase
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function handle(PlaceOrderCommand $command): Order
    {
        $order = Order::place(
            id: OrderId::generate(),
            customerId: new CustomerId($command->customerId),
            lineItems: $command->lineItems,
        );

        $this->orders->save($order);

        // Dispatch after save guarantees event only fires on success
        $this->dispatcher->dispatch(new OrderPlaced(
            orderId: $order->id()->toString(),
            customerId: $command->customerId,
            total: $order->total(),
            lineItems: $command->lineItems,
            occurredAt: new \DateTimeImmutable(),
        ));

        return $order;
    }
}
```

Registration of listeners:

```php
// infrastructure/registration.php

$dispatcher->addListener(OrderPlaced::class, new SendOrderConfirmation($mailer));
$dispatcher->addListener(OrderPlaced::class, new UpdateInventory($inventoryService));
$dispatcher->addListener(OrderPlaced::class, new ChargeCustomer($paymentGateway));
$dispatcher->addListener(OrderPlaced::class, new NotifyWarehouse($slack));
$dispatcher->addListener(OrderPlaced::class, new RecordAnalytics($analytics));
```

Each listener handles one concern. Adding a new requirement—say, "create a support ticket for orders over $500"—means writing a new listener and registering it. No changes to `PlaceOrderUseCase`. No changes to existing listeners.

```php
final class CreateSupportTicketForHighValueOrders
{
    public function __construct(
        private readonly TicketSystem $tickets,
    ) {
    }

    public function __invoke(OrderPlaced $event): void
    {
        if ($event->total->amount > 50000) { // $500.00 in cents
            $this->tickets->create(
                title: "High-value order {$event->orderId}",
                priority: Priority::HIGH,
                metadata: [
                    'order_id' => $event->orderId,
                    'customer_id' => $event->customerId,
                    'total' => $event->total->amount,
                ],
            );
        }
    }
}

// Register it
$dispatcher->addListener(
    OrderPlaced::class,
    new CreateSupportTicketForHighValueOrders($ticketSystem),
);
```

Zero modification to existing code. That's the power of event-driven architecture.

## Event-Driven Architecture Benefits

**Loose coupling.** The use case that places an order doesn't import mailer classes, HTTP clients, or Slack SDKs. It depends on `EventDispatcherInterface` and `OrderRepository`. Everything else is a listener registered elsewhere.

**Extensibility.** Adding behavior means adding listeners, not modifying existing classes. You can ship a new integration in a separate deployment without touching core business logic. This is particularly valuable in modular monoliths and plugin-based systems.

**Single responsibility.** Each listener does exactly one thing. `SendOrderConfirmation` sends email. `UpdateInventory` updates stock. `NotifyWarehouse` posts to Slack. You can test, deploy, and reason about each one independently.

**Audit and observability.** Every meaningful occurrence in your system produces an event. Log those events and you have a complete record of what happened and when. You can replay events to debug issues, rebuild caches, or generate reports.

**Graceful degradation.** If the email service is down, the order still places. The failed listener logs an error and moves on. The customer gets their confirmation when the email service recovers. The core operation—placing an order—never depends on the success of its side effects.

## When Event-Driven Architecture Hurts

Event-driven code is not free. It adds indirection. Finding the chain of behavior for a single operation requires hunting through listener registrations, subscriber classes, and priority configurations.

Use events when:
- You have multiple independent reactions to the same occurrence
- New reactions are added regularly by different teams
- You need to maintain an audit trail
- Cross-cutting concerns (logging, metrics, notifications) are polluting your business logic

Skip events when:
- One thing happens, and one thing reacts, and that's the whole flow
- Performance requirements demand minimal dispatch overhead
- The team is small and the requirements are stable
- You're inside a tight loop processing thousands of items per second

Event-driven architecture is a tool, not a goal. Use it where it solves real coupling problems. Leave it out where direct method calls are clearer and faster.

## Best Practices Summary

**Use past tense for event names.** `UserRegistered`, `OrderPlaced`, `PaymentFailed`. The event describes something that already happened. You can't unregister a user or unplace an order. The language reinforces the semantics.

**Keep events immutable.** Readonly properties, no setters, no behavior. An event is data with a timestamp.

**Dispatch after persistence.** Events should fire only after the database confirms the write. This prevents phantom events that trigger side effects for operations that never actually completed.

**Name events after the domain, not the implementation.** `UserRegistered`, not `DatabaseRowInserted`. The event should mean something to a domain expert, not just to a developer.

**Handle listener failures gracefully.** One failed listener should not prevent other listeners from running. Log the failure, notify operations, and continue.

**Keep listeners stateless where possible.** A listener should not maintain internal state between invocations. If you need to track state, use the event data or an external store.

**Document event contracts.** Each event's properties and their meanings should be documented. New listeners depend on this contract. Breaking changes to an event break every listener.

## Final Thoughts

Event-driven programming changes how you think about code. Instead of "I need to do X, then Y, then Z," you start thinking "X happened. What needs to react?" The difference is subtle but transformative.

Your core business logic becomes stable. It describes the rules: users register, orders place, payments process. The reactions—emails, notifications, analytics, integrations—live outside the core, attached by events. New requirements plug in without disturbing existing behavior.

Start small. Pick one operation that has multiple side effects. Extract the event. Register listeners. Watch how the coupling dissolves. You'll find yourself reaching for events whenever you encounter a method that "also does this other thing."

The pattern scales from a single file with two listeners to a distributed system with thousands of event types. The fundamental mechanics are the same: something happened, and the code that cares about it gets to react, independently, without asking permission from the code that made it happen.
