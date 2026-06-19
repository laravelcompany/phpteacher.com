---
title: "Random and Rare Failures - Testing Edge Cases in DDD PHP"
description: "Learn to identify and test random and rare failures in your Domain-Driven Design PHP applications. Strategies for capturing edge cases, flaky tests, and non-deterministic behavior."
pubDate: "2022-06-24 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "PHP", "Testing", "Edge Cases", "Debugging", "Flaky Tests", "Quality"]
selected: false
---

A test passes on your machine. It passes on CI. Then suddenly, in the middle of the night, it fails. You rerun the build. It passes. No one touches the code. No one knows why.

These are random and rare failures. They're the most dangerous bugs in software because they erode trust in your test suite and your codebase. When a failure can't be reproduced on demand, developers stop believing the tests. They start hitting "Rebuild" instead of investigating. They add `@sleep(1)` and hope for the best.

In Domain-Driven Design applications, these failures are especially insidious. DDD projects tend to have complex domain logic, event-driven architectures, and multiple bounded contexts communicating asynchronously. That complexity creates fertile ground for non-deterministic behavior.

## What Are Random and Rare Failures?

A random failure has no deterministic trigger. It depends on timing, ordering, or environmental conditions that change between runs. A rare failure occurs infrequently — once every hundred or thousand executions. When a bug is both random and rare, you have a debugging nightmare.

Common characteristics:
- The test fails without any code change
- Rerunning the same test passes
- The failure happens in CI but not locally
- It only fails on certain days or at certain times
- The error message points to different locations each time

These failures aren't truly random. They have causes. But those causes involve multiple variables interacting in ways that are hard to predict.

## Why DDD PHP Applications Are Vulnerable

Domain-Driven Design introduces patterns that, while valuable for modeling complex business logic, create opportunities for non-determinism.

### Eventual Consistency

DDD applications often use domain events processed asynchronously. An aggregate publishes an event; a handler processes it later.

```php
final class Order
{
    private array $events = [];

    public function complete(): void
    {
        $this->status = Status::COMPLETED;
        $this->events[] = new OrderCompleted($this->id);
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }
}
```

If your test checks state immediately after triggering the event, the handler may not have run yet. This is a classic race condition.

### Repository Implementations

In-memory repositories are common in DDD tests. They're fast and isolated. But if your application code relies on side effects that an in-memory repository doesn't simulate — like unique constraint violations or deadlocks — your tests pass while production fails.

### Service Layer Orchestration

Services that coordinate multiple aggregates introduce timing dependencies:

```php
final class TransferService
{
    public function __construct(
        private AccountRepository $accounts,
        private EventBus $eventBus,
    ) {}

    public function transfer(AccountId $from, AccountId $to, Money $amount): void
    {
        $source = $this->accounts->byId($from);
        $destination = $this->accounts->byId($to);

        $source->debit($amount);
        $destination->credit($amount);

        $this->accounts->save($source);
        $this->accounts->save($destination);

        $this->eventBus->publish([
            new AccountDebited($from, $amount),
            new AccountCredited($to, $amount),
        ]);
    }
}
```

If `save()` on one account succeeds but the other fails, your domain is inconsistent. The test that always uses one in-memory repository won't catch this.

## Common Causes of Random Failures

### Concurrency and Race Conditions

PHP is typically single-threaded per request, but modern PHP applications have plenty of concurrency. Queue workers process jobs in parallel. Cron jobs overlap. Multiple requests hit the same database.

```php
// Race condition in inventory management
final class InventoryService
{
    public function reserve(ProductId $id, int $quantity): void
    {
        $product = $this->products->byId($id);

        if ($product->stock() < $quantity) {
            throw new InsufficientStock($id);
        }

        $product->reserve($quantity);

        // Between reading stock and saving, another request
        // may have reserved the same stock
        $this->products->save($product);
    }
}
```

### External Services

HTTP calls to external APIs are inherently non-deterministic. A service that's down, slow, or returns inconsistent data causes failures that aren't under your control.

```php
final class PaymentService
{
    public function __construct(
        private PaymentGateway $gateway,
    ) {}

    public function charge(Invoice $invoice): void
    {
        // If gateway times out, do we retry?
        // The first call may have succeeded on the gateway
        // but the response was lost
        $result = $this->gateway->charge(
            $invoice->amount(),
            $invoice->paymentToken()
        );

        $invoice->markPaid($result->transactionId());
    }
}
```

### Randomness and Uniqueness

Code that generates random values or UUIDs can produce collisions or unexpected sequences:

```php
// A test using a unique reference code
$ref = 'INV-' . bin2hex(random_bytes(4));

// On rare occasions: collision in test data
```

### Timing and Timezones

Date-related logic is a persistent source of rare failures. Tests that pass at 3 PM fail at 3 AM. Daylight saving time shifts cause off-by-one errors.

```php
final class SubscriptionService
{
    public function renew(Subscription $subscription): void
    {
        $now = new \DateTimeImmutable();

        // This test breaks at midnight UTC
        // when run just before and just after
        if ($subscription->expiresAt() <= $now) {
            $subscription->renew();
        }
    }
}
```

## Testing Strategies

### Property-Based Testing

Instead of writing individual test cases with specific inputs, property-based testing generates hundreds or thousands of random inputs and verifies that invariants hold.

```php
use PHPUnit\Framework\Attributes\DataProvider;

final class MoneyTest extends TestCase
{
    public static function provideRandomValues(): iterable
    {
        for ($i = 0; $i < 1000; $i++) {
            yield [random_int(0, 100000), random_int(0, 100000)];
        }
    }

    #[DataProvider('provideRandomValues')]
    public function testAdditionIsCommutative(int $a, int $b): void
    {
        $moneyA = Money::fromCents($a);
        $moneyB = Money::fromCents($b);

        $sum1 = $moneyA->add($moneyB);
        $sum2 = $moneyB->add($moneyA);

        $this->assertTrue($sum1->equals($sum2));
    }
}
```

For PHP, the `ergebnis/combinatorial-whitelist` or `phpstan/phpstan-phpunit` can help, but a custom loop with `random_int()` is often the most practical approach.

### Fuzzy Testing

Fuzzy testing feeds malformed, unexpected, or boundary data into your system:

```php
final class EmailAddressTest extends TestCase
{
    public function testRejectsMalformedInputs(): void
    {
        $malformed = [
            '',
            'not-an-email',
            '@missing-local.com',
            'spaces in@email.com',
            str_repeat('a', 1000) . '@b.com',
            "new\nline@email.com",
            null,
        ];

        foreach ($malformed as $input) {
            $this->assertFalse(
                EmailAddress::tryFrom($input)?->isValid() ?? false,
                "Should reject: {$input}"
            );
        }
    }
}
```

### Stress Testing with Concurrent Requests

For concurrency issues, multiple simultaneous requests are necessary:

```php
// Using parallel curl requests in a test script
$urls = array_fill(0, 50, 'http://localhost/reserve-product?id=1&qty=1');
$mh = curl_multi_init();

foreach ($urls as $i => $url) {
    $ch = curl_init($url);
    curl_multi_add_handle($mh, $ch);
}

do {
    curl_multi_exec($mh, $running);
} while ($running);

// Check that total reserved never exceeds stock
```

### PHPUnit's @repeat

PHPUnit doesn't have a built-in `@repeat` annotation, but you can achieve repeated execution:

```php
/**
 * @dataProvider repeatProvider
 */
public function testRepositoryConcurrency(int $iteration): void
{
    // Run the same test many times to trigger race conditions
}

public static function repeatProvider(): iterable
{
    for ($i = 0; $i < 100; $i++) {
        yield [$i];
    }
}
```

Some frameworks like Pest PHP offer direct repetition:

```php
it('handles concurrent orders', function () {
    // test logic here
})->repeat(100);
```

## Reproducing Flaky Tests

Reproducing a flaky test is the hardest part. Without reproduction, you can't fix it.

### Seed-Based Randomness

If your test uses randomness, seed the random number generator to make failures reproducible:

```php
final class OrderNumberGenerator
{
    public function __construct(
        private \Random\Randomizer $randomizer,
    ) {}

    public function generate(): OrderNumber
    {
        $number = $this->randomizer->getInt(100000, 999999);
        return new OrderNumber((string) $number);
    }
}

// In test
$engine = new \Random\Engine\Mt19937(42);
$generator = new OrderNumberGenerator(new \Random\Randomizer($engine));

// Always produces the same order numbers
```

### Controlled Test Order

Test ordering matters. A test that pollutes global state affects subsequent tests:

```php
final class ShoppingCartTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset static state before each test
        ShoppingCartRegistry::reset();

        // Use fresh repositories
        $this->cartRepository = new InMemoryCartRepository();
    }
}
```

### Deterministic Time

Replace real time with a clock interface:

```php
interface Clock
{
    public function now(): \DateTimeImmutable;
}

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}

final class FrozenClock implements Clock
{
    public function __construct(
        private \DateTimeImmutable $now
    ) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}

// Production
$service = new SubscriptionService(new SystemClock());

// Test
$fixedTime = new \DateTimeImmutable('2022-06-15 12:00:00');
$service = new SubscriptionService(new FrozenClock($fixedTime));
```

## Design Patterns to Reduce Rare Failures

### Idempotency

Making operations idempotent eliminates duplicate-processing bugs:

```php
final class InvoiceService
{
    public function markAsPaid(InvoiceId $id, TransactionId $transactionId): void
    {
        $invoice = $this->invoices->byId($id);

        if ($invoice->isPaid()) {
            return; // Idempotent: already paid
        }

        $invoice->pay($transactionId);
        $this->invoices->save($invoice);
    }
}
```

### Optimistic Concurrency Control

Use version numbers to detect conflicting writes:

```php
final class Product
{
    public function __construct(
        public ProductId $id,
        public int $stock,
        public int $version = 0,
    ) {}

    public function reserve(int $quantity): void
    {
        if ($this->stock < $quantity) {
            throw new InsufficientStock($this->id);
        }

        $this->stock -= $quantity;
        $this->version++;
    }
}

// Repository checks version on save
$product = $this->products->byId($id);
$product->reserve(1);
$this->products->save($product, $product->version);
// Throws if version mismatch
```

### Retry with Backoff

For external service calls, implement retry logic:

```php
final class PaymentGatewayRetryDecorator implements PaymentGateway
{
    public function __construct(
        private PaymentGateway $inner,
        private int $maxRetries = 3,
    ) {}

    public function charge(Money $amount, PaymentToken $token): PaymentResult
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                return $this->inner->charge($amount, $token);
            } catch (GatewayTimeout $e) {
                $attempt++;
                usleep(100_000 * (2 ** $attempt)); // Exponential backoff
            }
        }

        throw new PaymentFailed('Gateway unavailable after retries');
    }
}
```

### Unit of Work

Database transactions prevent partial failures:

```php
$this->entityManager->beginTransaction();

try {
    $this->accounts->save($source);
    $this->accounts->save($destination);
    $this->entityManager->commit();
} catch (\Throwable $e) {
    $this->entityManager->rollback();
    throw $e;
}
```

## Capturing and Debugging Failures

When a rare failure happens, you need maximum context:

```php
final class FailureLogger
{
    public function capture(callable $operation): mixed
    {
        $context = [
            'memory_before' => memory_get_usage(),
            'time' => microtime(true),
            'request_id' => $this->requestId,
        ];

        try {
            return $operation();
        } catch (\Throwable $e) {
            $context['exception'] = $e::class;
            $context['message'] = $e->getMessage();
            $context['trace'] = $e->getTraceAsString();
            $context['memory_after'] = memory_get_usage();

            $this->logger->warning('Rare failure captured', $context);

            throw $e;
        }
    }
}
```

In production, log every occurrence of an unexpected failure. Aggregate by error fingerprint. If the same fingerprint appears rarely but consistently, you have a pattern to investigate.

### Mutation Testing with Infection

Infection testing mutates your code and checks if tests catch the change. This reveals gaps in your test suite that could hide rare failures:

```bash
vendor/bin/infection --min-covered-msi=80
```

Mutation testing doesn't find flaky tests directly, but it shows you where your tests are weak. Those weak spots are often where rare failures hide.

### Pest PHP for Property-Based Testing

Pest PHP makes property-based testing cleaner with its higher-order tests and expressive syntax:

```php
use function Pest\Faker\fake;

it('generates valid invoice numbers', function () {
    $generator = new InvoiceNumberGenerator(
        new Randomizer(new Mt19937(fake()->randomNumber()))
    );

    $number = $generator->generate();

    expect($number->toString())->toMatch('/^INV-\d{6}$/');
})->repeat(500);
```

The `->repeat()` modifier runs the same test body multiple times. Each invocation uses a new seed from Faker, exercising different code paths. Pair this with `fake()->unique()` to guarantee no duplicate values across repeats:

```php
it('never generates duplicate invoice numbers', function () {
    $generator = new InvoiceNumberGenerator(
        new Randomizer(new Mt19937(fake()->randomNumber()))
    );

    $numbers[] = $generator->generate()->toString();
})->repeat(1000)->after(function () use (&$numbers) {
    expect(count($numbers))->toBe(count(array_unique($numbers)));
});
```

### Database Locking Strategies

For persistent storage, pessimistic locking prevents concurrent modification:

```php
final class ProductRepository
{
    public function lockAndReserve(ProductId $id, int $quantity): Product
    {
        // SELECT ... FOR UPDATE locks the row
        $product = $this->entityManager
            ->createQuery('SELECT p FROM Product p WHERE p.id = :id')
            ->setParameter('id', $id)
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->getSingleResult();

        $product->reserve($quantity);
        $this->entityManager->flush();

        return $product;
    }
}
```

Pessimistic locking is slower but guarantees correctness under high contention. Use it for high-value operations like payment processing or inventory management where consistency matters more than throughput.

## Monitoring for Rare Failures in Production

Your test suite can't catch everything. Production monitoring is the safety net.

- **Error tracking**: Sentry, Rollbar, or similar tools that group errors by fingerprint. Watch for errors that occur infrequently but regularly.
- **Structured logging**: Include request IDs, aggregate IDs, and timestamps in every log line. This lets you reconstruct the sequence of events leading to a failure.
- **Metrics**: Track error rates per endpoint, per domain event, per queue worker. A small but persistent error rate is a red flag.
- **Distributed tracing**: For event-driven DDD systems, tracing helps you follow a command through multiple bounded contexts.

## Summary

| Strategy | Problem It Solves |
|---|---|
| Property-based testing | Edge cases in domain logic |
| Fuzzy testing | Malformed/unexpected input |
| Stress testing | Concurrency race conditions |
| Seed-based randomness | Non-deterministic test output |
| Frozen clocks | Time-dependent failures |
| Idempotency | Duplicate processing |
| Optimistic locking | Concurrent write conflicts |
| Retry with backoff | External service flakiness |
| Mutation testing | Weak test coverage |
| Production monitoring | Escaped rare failures |

Random and rare failures are a fact of life in complex DDD PHP applications. You can't eliminate every source of non-determinism. But you can build systems that surface them quickly, reproduce them reliably, and tolerate them gracefully. The goal isn't perfect determinism — it's making rare failures visible and fixable instead of invisible and dangerous.
