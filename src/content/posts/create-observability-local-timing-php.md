---
title: "Create Observability Part 1: Local Timing in PHP"
description: "Build a lightweight PHP timing library for local observability. Measure code execution with microtime() and understand DDD application behavior through timing data."
pubDate: "2023-06-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Observability", "Timing", "PHP", "DDD", "DDD Alley", "Performance", "Microtime", "Profiling", "Edward Barnard"]
---

Observability starts with measurement. Before you can optimize code, you must understand how it behaves at runtime. Before you can fix performance issues, you must know they exist. Edward Barnard's approach to local timing provides a simple, effective way to create observability in your PHP applications.

The tool is intentionally simplistic: a 30-line timing library based on PHP's built-in `microtime()` function. It adds microseconds of overhead and gives you precise timing data for any code path.

## What You'll Learn

- Building a lightweight Timing class with microtime()
- Measuring code execution in domain services
- Understanding timing output for debugging and verification
- Fine-grained measurement inside loops and iterations
- Using timing data for performance analysis and optimization
- Distinguishing measurement from premature optimization
- Integrating timing observability into DDD applications
- Extending the Timing class for production use
- Real-world patterns for local observability

## The Timing Class

The core is a simple class that records timestamps with labels.

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

The class tracks two values for each measurement: the interval since the last `measure()` call and the total time since the Timing object was created.

## Basic Usage

```php
<?php

$start = microtime(true) * 1000.0;

$timing = new Timing($start);
$timing->measure('Began bootstrap');

$service = RenderBodyFactory::create($timing);
$service->execute();

echo $timing->show(PHP_EOL);
```

Sample output:

```
Began bootstrap: 76.478 ms, 76.478 ms total
Began service execution: 20.140 ms, 96.618 ms total
100 input rows processed, 100 emails sent: 16606.550 ms, 16703.168 ms total
Completed service execution: 0.019 ms, 16703.187 ms total
```

This output tells you several things. Bootstrap and PHP startup took 76 milliseconds. That is slow but typical for development environments with caching disabled. The service executed for 16.7 seconds, processing 100 database rows and sending 100 emails. The final "Completed" call took only 19 microseconds, confirming that the completion logic itself is not the bottleneck.

## Integrating Timing in Application Services

Application services orchestrate domain logic and infrastructure. They are natural places to add timing instrumentation.

```php
<?php

declare(strict_types=1);

final class PaymentProcessingService
{
    public function __construct(
        private Timing $timing,
        private PaymentGateway $gateway,
        private InvoiceRepository $invoices,
        private NotificationService $notifications,
    ) {}

    public function processPayment(string $invoiceId): PaymentResult
    {
        $this->timing->measure('Payment processing started');

        $invoice = $this->invoices->findById($invoiceId);
        $this->timing->measure('Invoice loaded from database');

        $charge = $this->gateway->charge(
            $invoice->amount,
            $invoice->paymentMethodToken,
        );
        $this->timing->measure('Payment gateway charge completed');

        $invoice->markAsPaid($charge->transactionId);
        $this->invoices->save($invoice);
        $this->timing->measure('Invoice marked as paid and saved');

        $this->notifications->sendPaymentConfirmation($invoice);
        $this->timing->measure('Payment confirmation notification sent');

        return PaymentResult::success($charge->transactionId);
    }
}
```

This instrumentation tells you exactly how long each step takes. If payment gateway calls are slow, that is a provider issue. If database writes are slow, that is infrastructure. If notifications are slow, that is your email provider. Without this data, you would be guessing about the root cause of performance problems.

## Using Timing in Repository Pattern

Repositories are data access boundaries that benefit from timing.

```php
<?php

declare(strict_types=1);

final class DbalInvoiceRepository implements InvoiceRepository
{
    public function __construct(
        private Connection $connection,
        private Timing $timing,
    ) {}

    public function findPendingInvoices(): array
    {
        $this->timing->measure('Querying pending invoices');

        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM invoices WHERE status = ?',
            ['pending'],
        );

        $this->timing->measure(
            sprintf('Fetched %d pending invoice rows', count($rows))
        );

        $invoices = array_map(
            fn(array $row) => InvoiceMapper::fromRow($row),
            $rows,
        );

        $this->timing->measure(
            sprintf('Mapped %d invoices to domain objects', count($invoices))
        );

        return $invoices;
    }
}
```

Pass the Timing instance through your service constructors.

```php
<?php

declare(strict_types=1);

final class EmailNotificationService
{
    public function __construct(
        private Timing $timing,
        private EmailRepository $emails,
        private MailerInterface $mailer,
    ) {}

    public function execute(): void
    {
        $this->timing->measure('Began service execution');

        $this->walkInputQueue();

        $this->timing->measure('Completed service execution');
    }

    private function walkInputQueue(): void
    {
        $inputRows = 0;
        $sendCount = 0;

        $queue = $this->emails->findPending();

        foreach ($queue as $email) {
            $this->mailer->send($email);
            $inputRows++;
            $sendCount++;
        }

        $this->timing->measure(
            "{$inputRows} input rows processed, {$sendCount} emails sent"
        );
    }
}
```

### Timing with Middleware Stack

For HTTP applications, wrap middleware in a timing decorator to measure request processing.

```php
<?php

declare(strict_types=1);

final class TimingMiddleware
{
    public function __construct(
        private Timing $timing,
        private array $middlewareStack,
    ) {}

    public function handle(Request $request): Response
    {
        $this->timing->measure('Middleware chain started');

        $result = $this->processStack($request, 0);

        $this->timing->measure('Middleware chain completed');

        return $result;
    }

    private function processStack(Request $request, int $index): Response
    {
        if ($index >= count($this->middlewareStack)) {
            return new Response('Not found', 404);
        }

        $this->timing->measure(
            'Executing middleware #' . $index
        );

        return $this->middlewareStack[$index]->process(
            $request,
            fn(Request $req) => $this->processStack($req, $index + 1),
        );
    }
}
```

## Fine-Grained Measurement Inside Loops

When aggregate measurements are not enough, instrument inside the loop.

```php
<?php

declare(strict_types=1);

final class BatchProcessor
{
    public function __construct(
        private Timing $timing,
        private array $items,
    ) {}

    public function process(): void
    {
        foreach ($this->items as $index => $item) {
            $start = microtime(true) * 1000.0;

            $this->processItem($item);

            $duration = (microtime(true) * 1000.0) - $start;

            if ($duration > 1000) {
                $this->timing->measure(
                    "Item {$index}: {$duration} ms (slow)"
                );
            }
        }
    }

    private function processItem(mixed $item): void
    {
        // Simulated processing
        usleep(random_int(100, 5000) * 100);
    }
}
```

This reveals whether performance is uniform or driven by outliers. You might discover that the first item takes 12 seconds while the rest complete in milliseconds. That changes your optimization strategy entirely.

## Interpreting Timing Data

What should you look for in timing output?

**Expected paths**: Did the code follow the expected branch? If you see "Cache miss" and you expected "Cache hit," something went wrong.

**Expected counts**: Did the loop process the expected number of items? An off-by-one error in iteration shows up immediately in the timing output.

**Performance profile**: Is the execution time dominated by a single step or spread evenly? Focus optimization on the step that accounts for the most time.

**Anomalies**: Is a normally fast operation suddenly slow? That is a signal for investigation.

```php
<?php

private function walkInputQueue(): void
{
    $inputRows = 0;

    foreach ($this->getItems() as $item) {
        $inputRows++;
    }

    // Did we process the right number?
    $this->timing->measure("Processed {$inputRows} items");
}
```

## When to Use Local Timing

This technique is ideal for:

- **Development**: Understanding how new code performs before deployment
- **Debugging**: Identifying which step in a process is slow
- **Validation**: Confirming that production fixes work as expected
- **Refactoring**: Measuring before and after performance

It is not a production monitoring solution. The `Timing` class adds overhead to every call. For high-traffic production systems, use APM tools like Datadog, New Relic, or OpenTelemetry.

## Extending the Timing Class

Add features as needed without changing the interface.

```php
<?php

declare(strict_types=1);

final class AdvancedTiming extends Timing
{
    private array $thresholds = [];

    public function alertWhenSlowerThan(float $ms): void
    {
        $this->thresholds[] = $ms;
    }

    protected function record(string $description, float $interval, float $total): void
    {
        parent::record($description, $interval, $total);

        foreach ($this->thresholds as $threshold) {
            if ($interval > $threshold) {
                error_log(
                    "SLOW: {$description} took {$interval} ms"
                );
            }
        }
    }
}
```

## Real-World Use Cases

### Batch Job Profiling

Cron jobs and queue workers that process thousands of items benefit from per-batch timing. Know exactly how long each phase takes.

### API Endpoint Monitoring

Add timing to critical API endpoints during development. When the endpoint goes live, you already know its performance baseline.

### Database Query Analysis

Time queries and their processing separately. If the query is fast but processing is slow, optimizing the query will not help.

### Email and Notification Delivery

Timing reveals whether delays are in the database query, the template rendering, or the SMTP delivery.

### File Processing

Image resizing, CSV parsing, and file uploads benefit from per-step timing to identify bottlenecks.

## Best Practices

- **Measure, then optimize** - Never optimize without measurement. You might optimize code that is already fast enough.
- **Use descriptive labels** - "Began bootstrap" is more useful than "Point A." Include context like iteration counts.
- **Keep overhead minimal** - The Timing class adds microseconds. Avoid adding it inside the hottest loops.
- **Sample, do not trace everything** - In production, sample every Nth request. Full tracing creates too much data.
- **Disable in production** - Simple timing is for development. Use conditional compilation or environment checks.
- **Correlate with business metrics** - Timing data is most valuable when tied to specific business operations.

## Common Mistakes to Avoid

- **Optimizing without data** - Guessing about performance is almost always wrong. Always measure first.
- **Ignoring outliers** - Average timing hides slow individual operations. Track the maximum and 95th percentile.
- **Instrumenting too early** - Add timing when you have a performance question, not before.
- **Confusing precision with accuracy** - microtime() is precise but not perfectly accurate. Treat values as approximations.
- **Forgetting cold start** - First requests are slower due to cache warming. Measure after the system stabilizes.

## Frequently Asked Questions

### Why not use Xdebug for profiling?

Xdebug provides detailed profiling but significantly slows execution. The microtime approach adds minimal overhead and works in production-like environments.

### Is microtime() accurate enough?

For timing operations taking milliseconds or more, yes. For sub-millisecond precision, use `hrtime()` (PHP 7.3+).

### Should I leave timing code in production?

Simple timing in low-traffic paths is fine. For high-traffic paths, sample or disable timing code conditionally.

### How does this differ from APM tools?

APM tools provide distributed tracing, automated instrumentation, and production dashboards. Local timing is a lightweight alternative for development and debugging.

### Can I send timing data to a logging system?

Yes. Replace the internal array with a PSR-3 logger. Send each measurement to your logging infrastructure.

### What about memory measurement?

Add memory_get_peak_usage(true) calls alongside microtime() to correlate time and memory consumption.

### How do I measure async operations?

For async or deferred operations, pass the Timing instance to the async handler or record timestamps for later correlation.

## Conclusion

Local timing with PHP's microtime() is the simplest form of observability. It costs almost nothing, takes minutes to implement, and provides immediate insight into your application's runtime behavior.

Edward Barnard's approach proves that observability does not require complex infrastructure. A single class, injected into your services, gives you the data you need to understand performance, validate behavior, and debug issues.

Start with the 30-line Timing class. Add it to one domain service. Observe the output. You will learn something about your application that you did not know before.
