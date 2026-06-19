---
title: "Observability in DDD Applications: Logs, Metrics & Traces"
description: "Learn how to implement observability in domain-driven design applications using PHP 8.x. Monitor logs, metrics, and traces to understand system behavior."
pubDate: "2023-02-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Observability", "DDD", "Domain-Driven Design", "PHP", "Monitoring", "Logging", "Tracing", "Metrics", "Application Performance"]
---

Observability is the practice of understanding your system's internal state through the data it produces. In Domain-Driven Design (DDD) applications, where business logic is carefully modeled around domain concepts, observability becomes even more critical. Without proper visibility into how your domain objects behave at runtime, you're flying blind when production issues arise.

Traditional monitoring tells you something is wrong. Observability tells you *why* it's wrong. For DDD applications, this distinction matters because domain logic is where business value lives. When a pricing calculation fails, a workflow state transition breaks, or a domain event doesn't propagate, you need to know exactly what happened inside your domain model.

## What You'll Learn

- The three pillars of observability: logs, metrics, and traces
- How to instrument DDD aggregates, entities, and value objects
- Building a lightweight timing library for local observability
- Integrating structured logging with domain events
- Tracing command and query flows through your application
- Real-world patterns for observable PHP applications

## The Three Pillars of Observability

Observability rests on three foundational data types that work together to give you full visibility into your system.

### Logs

Logs are discrete, timestamped records of events. In a DDD context, logs capture domain events, command executions, and significant state changes. Structured logging (JSON format) is essential for machine readability and searchability.

### Metrics

Metrics are numerical measurements collected over time. Count domain events processed, measure aggregate method execution time, and track command handler throughput. These give you trend data for capacity planning and anomaly detection.

### Traces

Traces follow a single request or transaction through every service, class, and method it touches. In DDD, traces show you the path from an HTTP request through application services, domain logic, and infrastructure boundaries.

## Building Local Observability with PHP

Before adopting heavy infrastructure, you can add lightweight observability directly in your PHP code. Edward Barnard's approach uses a minimal Timing class based on `microtime()` that costs only microseconds of overhead.

```php
<?php

declare(strict_types=1);

final class Timing
{
    private float $start;
    private float $intervalStart;
    private array $timings = [];

    public function __construct(?float $start = null)
    {
        $start ??= microtime(true) * 1000.0;
        $this->start = $start;
        $this->intervalStart = $start;
    }

    public function measure(string $description): void
    {
        $current = microtime(true) * 1000.0;
        $total = $current - $this->start;
        $interval = $current - $this->intervalStart;
        $this->timings[] = sprintf(
            '%s: %.3f ms, %.3f ms total',
            $description,
            $interval,
            $total
        );
        $this->intervalStart = $current;
    }

    public function show(string $separator = PHP_EOL): string
    {
        return implode($separator, $this->timings);
    }
}
```

This class lets you instrument any part of your DDD application cheaply. You pass it through service constructors and call `measure()` at key points.

```php
<?php

$start = microtime(true) * 1000.0;

$timing = new Timing($start);
$timing->measure('Began bootstrap');

$service = RenderBodyFactory::create($timing);
$service->execute();

echo $timing->show(PHP_EOL);
```

Output might look like:

```
Began bootstrap: 76.478 ms, 76.478 ms total
Began service execution: 20.140 ms, 96.618 ms total
100 rows processed: 16606.550 ms, 16703.168 ms total
Completed service execution: 0.019 ms, 16703.187 ms total
```

The key insight is that you use this data for two purposes. First, verifying that your code executed the expected path and processed the expected number of iterations. Second, obtaining actual numeric measurements before deciding what to optimize.

## Observability in Domain Services

Domain services orchestrate complex business operations. They are natural places to add observability instrumentation.

```php
<?php

declare(strict_types=1);

final class RegistrationWorkflow
{
    public function __construct(
        private Timing $timing,
        private RegistrationRepository $repository,
        private EmailService $emailService,
    ) {}

    public function registerApplicant(array $data): Applicant
    {
        $this->timing->measure('Starting registration');

        $applicant = Applicant::register(
            name: $data['name'],
            email: $data['email'],
            submittedAt: new DateTimeImmutable()
        );

        $this->timing->measure('Applicant created');

        $this->repository->save($applicant);

        $this->timing->measure('Applicant persisted');

        $this->emailService->sendWelcome($applicant);

        $this->timing->measure('Welcome email sent');

        return $applicant;
    }
}
```

This pattern gives you per-operation timing without complex APM tooling. You can later replace the simple `Timing` class with a more sophisticated implementation that sends traces to Zipkin, Jaeger, or Datadog without changing your domain code.

## Domain Events as Observability Signals

Domain events are a natural observability mechanism. Each event represents something meaningful that happened in your domain. By publishing them to a structured log or event stream, you get a complete audit trail.

```php
<?php

declare(strict_types=1);

final class ApplicantRegistered
{
    public function __construct(
        public readonly string $applicantId,
        public readonly string $email,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}

final class EventBus
{
    private array $listeners = [];

    public function subscribe(callable $listener, string $eventClass): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $class = $event::class;

        foreach ($this->listeners[$class] ?? [] as $listener) {
            $listener($event);
        }
    }
}
```

An observability listener can log every domain event:

```php
<?php

declare(strict_types=1);

final class EventLogger
{
    public function __invoke(object $event): void
    {
        $logEntry = json_encode([
            'type' => $event::class,
            'data' => $event,
            'timestamp' => (new DateTimeImmutable())
                ->format(DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR);

        error_log($logEntry);
    }
}
```

## Tracing Through Aggregate Boundaries

Aggregates enforce consistency boundaries. Tracing across these boundaries reveals performance hotspots and unexpected behavior.

```php
<?php

declare(strict_types=1);

final class OrderAggregate
{
    private array $events = [];

    public function __construct(
        private string $orderId,
        private OrderState $state,
        private array $lineItems,
    ) {}

    public static function place(
        string $orderId,
        array $lineItems,
        Timing $timing,
    ): self {
        $timing->measure('Order aggregate creation');

        $order = new self(
            orderId: $orderId,
            state: OrderState::Pending,
            lineItems: $lineItems,
        );

        $order->recordThat(new OrderPlaced(
            orderId: $orderId,
            occurredAt: new DateTimeImmutable(),
        ));

        return $order;
    }

    public function confirm(Timing $timing): void
    {
        $timing->measure('Order confirmation started');

        if (!$this->state->canTransitionTo(OrderState::Confirmed)) {
            throw new \RuntimeException(
                'Cannot confirm order in state: ' . $this->state->value
            );
        }

        $this->state = OrderState::Confirmed;

        $this->recordThat(new OrderConfirmed(
            orderId: $this->orderId,
            occurredAt: new DateTimeImmutable(),
        ));

        $timing->measure('Order confirmation completed');
    }

    private function recordThat(object $event): void
    {
        $this->events[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }
}
```

Each aggregate method accepts an optional `Timing` parameter, allowing fine-grained measurement without coupling to a specific monitoring library.

## Structured Logging in Application Services

Application services orchestrate infrastructure and domain logic. They are the ideal layer for adding structured logging.

```php
<?php

declare(strict_types=1);

final class OrderApplicationService
{
    public function __construct(
        private OrderRepository $orders,
        private EventBus $eventBus,
        private LoggerInterface $logger,
    ) {}

    public function placeOrder(PlaceOrderCommand $command): void
    {
        $this->logger->info('Placing order', [
            'orderId' => $command->orderId,
            'customerId' => $command->customerId,
            'itemCount' => count($command->items),
        ]);

        $order = OrderAggregate::place(
            orderId: $command->orderId,
            lineItems: $command->items,
        );

        $this->orders->save($order);

        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
            $this->logger->debug('Domain event dispatched', [
                'event' => $event::class,
                'data' => $event,
            ]);
        }

        $this->logger->info('Order placed successfully', [
            'orderId' => $command->orderId,
        ]);
    }
}
```

## Real-World Use Cases

### E-Commerce Order Processing

Trace every step of order placement, payment capture, and fulfillment. When a checkout fails, you have the full trace showing exactly which domain service threw the exception and what state the aggregate was in.

### SaaS Subscription Management

Monitor subscription state transitions, billing events, and feature access changes. Observability helps you debug why a customer's trial didn't convert or why billing failed silently.

### Financial Transaction Systems

Audit every financial operation with domain events captured as structured logs. When reconciliation fails, you can replay the exact sequence of events that led to the discrepancy.

### Healthcare Scheduling

Track appointment booking workflows, insurance verification calls, and notification delivery. If a patient doesn't receive a reminder, the trace shows whether the domain event fired, the notification service ran, and the email provider accepted the message.

## Best Practices

- **Instrument at domain boundaries** - Add observability at the edges of your domain model, not inside every method. Service methods, command handlers, and event subscribers are natural instrumentation points.
- **Use structured logging** - Always log in JSON or another structured format. Include correlation IDs, entity identifiers, and timestamps.
- **Keep domain logic clean** - Don't couple your domain model to monitoring infrastructure. Use dependency injection for cross-cutting concerns like timing and logging.
- **Sample high-volume traces** - For aggregates that process thousands of operations per second, sample your traces. Capture every Nth request or every Nth event.
- **Store observability data separately** - Domain events should be stored in your event store for business purposes. Observability data goes to a separate monitoring system.
- **Set alert thresholds** - Don't just collect data. Configure alerts for anomalous patterns, such as an aggregate method taking longer than expected or a domain event failing to dispatch.

## Common Mistakes to Avoid

- **Over-instrumenting domain logic** - Adding logging inside every entity method creates noise. Focus on aggregate boundaries, service methods, and infrastructure gateways.
- **Treating logs as an afterthought** - Unstructured logs with vague messages like "error occurred" are useless. Plan your logging strategy before you ship.
- **Ignoring performance overhead** - Logging and tracing add CPU and I/O overhead. Use sampling for high-throughput paths and keep measurement calls cheap.
- **Mixing domain events with observability events** - Domain events are business concepts. Observability events are technical signals. Store them separately.
- **Failing to correlate** - Without correlation IDs spanning services and requests, you cannot connect logs, metrics, and traces for a single operation.

## Frequently Asked Questions

### What is the difference between monitoring and observability?

Monitoring tells you *what* is broken. Observability tells you *why* it is broken. Monitoring requires predefined dashboards and alerts. Observability lets you explore unknown unknowns.

### How does observability apply to DDD specifically?

DDD applications have rich domain logic that can fail in business-specific ways. Observability helps you verify that business rules executed correctly, workflows completed as expected, and domain events propagated properly.

### Should I use an APM tool or build my own?

Start with simple instrumentation like the Timing class shown above. When your needs grow, adopt an APM tool like Datadog, New Relic, or OpenTelemetry. The key is having clean abstractions so you can swap implementations.

### How do I handle observability in event-sourced aggregates?

Event sourcing naturally provides observability because every state change is recorded as an event. Add metadata like IP address, user agent, and correlation ID to your event store for full traceability.

### What metrics matter most in a DDD application?

Track aggregate creation rates, command processing times, domain event dispatch counts, and repository query durations. These tell you if your domain logic is healthy.

### Can observability replace unit testing?

No. Observability tells you what happened in production. Unit testing tells you if your code is correct. Both are essential. You cannot observe your way into correct code.

### How do I add tracing without coupling?

Define an interface like `TracerInterface` with methods for starting and ending spans. Inject it into services. Your domain model never knows about the tracing implementation.

## Conclusion

Observability transforms DDD applications from opaque business logic into transparent, diagnosable systems. By adding lightweight instrumentation at domain boundaries, capturing domain events as structured logs, and tracing command flows through aggregates, you gain the ability to understand exactly what your application is doing at runtime.

Start small. Add a Timing class to one service. Log a few domain events. As you see the value, expand your observability coverage. Your production debugging sessions will become faster, your incident response more precise, and your understanding of your own domain model deeper.

Ready to make your DDD application observable? Begin by instrumenting one aggregate boundary today. Future you, debugging a production issue at 2 AM, will be grateful.
