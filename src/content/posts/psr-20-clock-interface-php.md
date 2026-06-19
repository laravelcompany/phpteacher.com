---
title: "PSR-20 Clock Interface: Standardizing Time in PHP"
description: "Master PSR-20 Clock interface for testable time-dependent PHP code. Learn clock abstraction patterns, immutable DateTime handling, and dependency injection."
pubDate: "2023-05-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PSR-20", "Clock Interface", "PHP", "PHP-FIG", "DateTime", "Testing", "Immutability", "Time Abstraction"]
---

Time is one of the hardest dependencies to manage in software. Every call to `new DateTime()` or `time()` creates an implicit dependency on the system clock. In testing, this means you cannot reliably assert behavior without controlling time itself.

PSR-20 provides a standardized ClockInterface that abstracts time retrieval. Instead of calling global functions, your code asks a clock implementation for the current time. In production, you inject a system clock. In tests, you inject a frozen or controllable clock.

## What You'll Learn

- The PSR-20 ClockInterface specification
- Why clock abstraction matters for testability
- Implementing a system clock, frozen clock, and mutable clock
- Using PSR-20 in domain entities and application services
- Testing time-sensitive logic without sleep()
- Patterns for scheduling, expiry, and follow-up logic
- Migrating existing code to clock-aware design
- Dependency injection configuration for clocks
- Common time-related testing pitfalls and solutions

## The PSR-20 Interface

PSR-20 is elegantly simple. It defines a single interface with one method.

```php
<?php

namespace Psr\Clock;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
```

The return type is `DateTimeImmutable`, not `DateTime`. This is intentional. Immutability prevents accidental mutation of the clock's value after retrieval.

## Implementing PSR-20

### System Clock

The production implementation returns the actual current time.

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
```

### Frozen Clock

The test implementation returns a fixed time.

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class FrozenClock implements ClockInterface
{
    public function __construct(
        private \DateTimeImmutable $now,
    ) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function setTo(\DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}
```

### Mutable Clock

For integration tests, a clock that advances on each call.

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class MutableClock implements ClockInterface
{
    public function __construct(
        private \DateTimeImmutable $now,
        private \DateInterval $interval,
    ) {}

    public function now(): \DateTimeImmutable
    {
        $current = $this->now;
        $this->now = $this->now->add($this->interval);
        return $current;
    }
}
```

## Using PSR-20 in Domain Entities

In Domain-Driven Design, entities and aggregates often need to know the current time. Inject a `ClockInterface` instead of calling `new DateTimeImmutable()` directly.

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class Appointment
{
    private ?\DateTimeImmutable $finishedDateTime = null;

    public function __construct(
        private ClockInterface $clock,
        private \DateTimeImmutable $appointmentDateTime,
    ) {}

    public function finish(): void
    {
        $this->finishedDateTime = $this->clock->now();
    }

    public function shouldSendFollowUp(): bool
    {
        if ($this->finishedDateTime === null) {
            return false;
        }

        $now = $this->clock->now();
        $followUpDate = $this->finishedDateTime->modify('+24 hours');

        return $now >= $followUpDate;
    }
}
```

This entity is fully testable. You control the clock in tests.

```php
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AppointmentTest extends TestCase
{
    public function test_follow_up_not_sent_before_24_hours(): void
    {
        $now = new \DateTimeImmutable('2024-01-01 10:00:00');
        $clock = new FrozenClock($now);

        $appointment = new Appointment(
            clock: $clock,
            appointmentDateTime: $now->modify('-1 hour'),
        );

        $appointment->finish();

        $this->assertFalse($appointment->shouldSendFollowUp());
    }

    public function test_follow_up_sent_after_24_hours(): void
    {
        $start = new \DateTimeImmutable('2024-01-01 10:00:00');
        $clock = new FrozenClock($start);

        $appointment = new Appointment(
            clock: $clock,
            appointmentDateTime: $start->modify('-1 hour'),
        );

        $appointment->finish();

        // Advance time by 25 hours
        $clock->setTo($start->modify('+25 hours'));

        $this->assertTrue($appointment->shouldSendFollowUp());
    }
}
```

## PSR-20 in Application Services

Application services orchestrate operations that depend on time.

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class AppointmentService
{
    public function __construct(
        private AppointmentRepository $appointments,
        private ClockInterface $clock,
        private NotificationService $notifications,
    ) {}

    public function createAppointment(CreateAppointmentCommand $command): Appointment
    {
        $appointment = new Appointment(
            clock: $this->clock,
            appointmentDateTime: $command->scheduledAt,
        );

        $this->appointments->save($appointment);

        $this->notifications->send(
            recipient: $command->email,
            message: 'Appointment created for ' . $command->scheduledAt->format('Y-m-d H:i'),
        );

        return $appointment;
    }

    public function processFollowUps(): void
    {
        $appointments = $this->appointments->findFinished();

        foreach ($appointments as $appointment) {
            if ($appointment->shouldSendFollowUp()) {
                $this->notifications->sendFollowUp($appointment);
            }
        }
    }
}
```

## Migrating Existing Code

Converting code from global time functions to PSR-20 is straightforward.

```php
<?php

// Before: untestable
class ExpiringToken
{
    private \DateTimeImmutable $expiresAt;

    public function __construct()
    {
        $this->expiresAt = (new \DateTimeImmutable())->modify('+1 hour');
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() >= $this->expiresAt;
    }
}

// After: testable
class ExpiringToken
{
    private \DateTimeImmutable $expiresAt;

    public function __construct(
        private ClockInterface $clock,
    ) {
        $this->expiresAt = $this->clock->now()->modify('+1 hour');
    }

    public function isExpired(): bool
    {
        return $this->clock->now() >= $this->expiresAt;
    }
}
```

## PSR-20 with Dependency Injection

Register the clock implementation in your DI container.

```php
<?php

// Production config
$container->set(ClockInterface::class, function () {
    return new SystemClock();
});

// Test config
$container->set(ClockInterface::class, function () {
    return new FrozenClock(new \DateTimeImmutable('2024-01-01 12:00:00'));
});
```

Symfony example:

```yaml
# config/services.yaml
services:
  Psr\Clock\ClockInterface:
    class: App\Infrastructure\Clock\SystemClock
```

### Laravel Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Clock\ClockInterface;

class ClockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClockInterface::class, function () {
            return new SystemClock();
        });
    }

    public function boot(): void
    {
        //
    }
}
```

For testing in Laravel, swap the binding:

```php
<?php

use Psr\Clock\ClockInterface;

public function test_expired_token(): void
{
    $frozen = new FrozenClock(
        new \DateTimeImmutable('2024-01-01 10:00:00')
    );
    $this->app->instance(ClockInterface::class, $frozen);

    $token = new ExpiringToken($frozen);

    $frozen->setTo(new \DateTimeImmutable('2024-01-01 11:01:00'));

    $this->assertTrue($token->isExpired());
}
```

## Advanced Clock Patterns

### Time Zone Handling

Clocks should operate in UTC internally. Convert to local time zones only at the presentation layer.

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}

final class PresentationClock
{
    public function __construct(
        private ClockInterface $clock,
        private \DateTimeZone $displayTimeZone,
    ) {}

    public function nowFormatted(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->clock->now()
            ->setTimezone($this->displayTimeZone)
            ->format($format);
    }
}
```

### Cached Clock

Cache the current time within a request to ensure consistency across multiple calls:

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class CachedClock implements ClockInterface
{
    private ?\DateTimeImmutable $cached = null;

    public function __construct(
        private ClockInterface $inner,
    ) {}

    public function now(): \DateTimeImmutable
    {
        if ($this->cached === null) {
            $this->cached = $this->inner->now();
        }

        return $this->cached;
    }
}
```

This clock returns the same time for every call within a single request, which is useful when you need consistent timestamps across multiple domain operations.

### Clock with Timeout

For long-running processes, create a clock that throws after a deadline:

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class TimeoutClock implements ClockInterface
{
    private \DateTimeImmutable $deadline;

    public function __construct(
        private ClockInterface $inner,
        \DateInterval $timeout,
    ) {
        $this->deadline = $this->inner->now()->add($timeout);
    }

    public function now(): \DateTimeImmutable
    {
        $now = $this->inner->now();

        if ($now >= $this->deadline) {
            throw new \RuntimeException(
                'Operation timed out at ' . $now->format('Y-m-d H:i:s')
            );
        }

        return $now;
    }
}
```

### Clock with Time Advancement

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class AdvancingClock implements ClockInterface
{
    public function __construct(
        private \DateTimeImmutable $now,
        private \DateInterval $advanceBy,
    ) {}

    public function now(): \DateTimeImmutable
    {
        $current = $this->now;
        $this->now = $this->now->add($this->advanceBy);
        return $current;
    }
}

// Usage in tests: advances 5 minutes per call
$clock = new AdvancingClock(
    new \DateTimeImmutable('2024-01-01 10:00:00'),
    new \DateInterval('PT5M'),
);
```

### Business Hours Clock

For scheduling applications, a clock that only returns business hours:

```php
<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;

final class BusinessHoursClock implements ClockInterface
{
    public function __construct(
        private ClockInterface $inner,
        private int $startHour = 9,
        private int $endHour = 17,
    ) {}

    public function now(): \DateTimeImmutable
    {
        $now = $this->inner->now();
        $hour = (int) $now->format('G');

        if ($hour < $this->startHour) {
            return $now->setTime($this->startHour, 0, 0);
        }

        if ($hour >= $this->endHour) {
            return $now
                ->modify('+1 day')
                ->setTime($this->startHour, 0, 0);
        }

        // Weekend check
        $dayOfWeek = (int) $now->format('N');
        if ($dayOfWeek >= 6) {
            $daysToAdd = 8 - $dayOfWeek;
            return $now
                ->modify("+{$daysToAdd} days")
                ->setTime($this->startHour, 0, 0);
        }

        return $now;
    }
}
```

## Real-World Use Cases

### Email Scheduling

Send follow-up emails exactly N hours after an event. The clock abstraction makes testing the scheduling logic trivial.

### Token Expiration

JWT tokens, password reset links, and session tokens all expire. Clock-aware code tests expiration without slow `sleep()` calls.

### Rate Limiting

Track request counts within sliding windows. The clock provides the current window timestamp without coupling to `time()`.

### Subscription Management

Handle trial periods, billing cycles, and feature access based on current time. Tests can verify behavior at any point in the subscription lifecycle.

### Audit Logging

Record timestamps for domain events. Clock injection ensures audit times are consistent and testable.

## Best Practices

- **Inject ClockInterface, not DateTime** - Pass the clock to constructors. Let entities call `$this->clock->now()` when they need the time.
- **Use DateTimeImmutable everywhere** - PSR-20 returns DateTimeImmutable. Match this convention throughout your domain model.
- **Never mock ClockInterface** - Use FrozenClock instead of mocks. It is simpler and more reliable.
- **Test time-sensitive logic with FrozenClock** - Set the clock to the exact time needed for each test scenario.
- **Keep clock injection at the boundary** - Domain entities get the clock through dependency injection. Application services pass it to entities.

## Common Mistakes to Avoid

- **Calling new DateTime() in domain code** - This creates an untestable dependency on the system clock.
- **Using mutable DateTime** - Mutable DateTime objects change state when passed to `modify()`. Use DateTimeImmutable for all clock values.
- **Mocking ClockInterface** - Mocks are verbose and fragile. A simple FrozenClock implementation is more maintainable.
- **Forgetting clock injection for new entities** - Every new entity that needs time must receive a ClockInterface instance.
- **Assuming clock precision** - System clocks drift and synchronize. Do not assume sub-millisecond precision across distributed systems.

## Frequently Asked Questions

### What is PSR-20?

PSR-20 is a PHP-FIG specification that defines a ClockInterface with a single `now(): DateTimeImmutable` method. It standardizes how PHP applications retrieve the current time.

### Why use DateTimeImmutable instead of DateTime?

DateTimeImmutable prevents accidental mutation. When you call `modify()` on a DateTimeImmutable, you get a new instance. The original is unchanged.

### Can I use PSR-20 with Laravel?

Yes. Bind `ClockInterface` to a system clock in your `AppServiceProvider`. Use `Clock::fake()` in tests or bind a FrozenClock.

### Does PSR-20 replace Carbon?

Carbon implements DateTimeImmutable and can be used with PSR-20. Return `CarbonImmutable` from `now()` if you want Carbon's convenience methods while staying immutable.

### How do I test code that runs at midnight?

Create a FrozenClock set to the desired midnight timestamp. Test boundary conditions by setting the clock to 23:59:59 and 00:00:00.

### Is PSR-20 adopted by major frameworks?

PSR-20 is a draft standard as of 2023. It is implemented by several libraries and is gaining adoption in the PHP ecosystem.

### What about time zones?

DateTimeImmutable includes timezone information. Your clock implementation should use UTC internally and convert to the application's timezone at the presentation layer.

## Conclusion

PSR-20 transforms time from an untestable global dependency into a manageable, injectable service. By abstracting the system clock behind a simple interface, you gain the ability to test time-sensitive logic thoroughly, simulate any date or time, and write deterministic tests without sleep() calls.

The investment is small. A single interface with one method. The payoff is large. No more untestable time-dependent bugs, no more flaky tests that pass or fail based on the time of day, and no more coupling your domain logic to PHP's global time functions.

Start today. Add `psr/clock` to your composer.json, create a SystemClock for production, and a FrozenClock for tests. Your future self will thank you.
