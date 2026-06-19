---
title: "Exception Report - Error Handling Strategies in Domain-Driven Design PHP"
description: "Error handling strategies in Domain-Driven Design PHP. Learn exception patterns, domain error modeling, and resilient application design."
category: "DDD"
banner: "/logo.svg"
pubDate: "2022-12-24 21:00:00"
tags: ["DDD", "Exceptions", "PHP", "Error Handling", "Architecture"]
---

Planning for failure is difficult because it is usually not practical to predict every possible thing that could go wrong. PHP applications have many points where something could fail in theory but rarely does in practice. If we created a separate contingency plan for every single line of code that could someday fail for some unknown reason, we would never ship anything.

The key insight is to design a systematic mechanism for capturing rare and random failures without letting contingency planning paralyze development.

## The Transactional Boundary

When building Domain-Driven Design applications, the transactional boundary is the most critical concept for consistency. A transaction either commits all changes or rolls them all back. This guarantees the invariant: related data stays consistent.

Consider a simple flow: you begin a transaction, perform work, and then either COMMIT or ROLLBACK. The transactional boundary enforces consistency. Now comes the important question: does this boundary express a business rule that must remain invariant?

Yes. Both the happy path (commit) and the sad path (rollback) express the invariant. But have you fully expressed the invariant? No. An event can only be published upon successful commit. The event publication is part of the invariant but lives outside the transactional boundary.

## The Solution: Transaction Inside Try/Catch

Place the transaction inside a `try`/`catch` block. When an error happens within the transaction, the PHP code rolls back the transaction and then throws an exception. Processing continues inside the `catch` block.

This structure fulfills the invariant using the combination of transaction and `try`/`catch`:

```php
try {
    $connection->transactional(function ($conn) use ($entity, $appEvent) {
        $this->eventCountsTable->saveOrFail($entity);
        $appEvent->save($conn);
    });
    $appEvent->notify();
} catch (\Exception $e) {
    $message = $e->getMessage();
    $detail = [
        'trace' => $e->getTraceAsString(),
        'entity' => $entity->toArray(),
        'entity errors' => $entity->getErrors(),
    ];
    RecordExceptionReport::capture($message, $detail);
    RecordExceptionReport::flush();
}
```

Note that we cannot save an error record to the database within the transaction. When the transaction rolls back, we lose the error record too. Instead, capture the error in memory, and once outside the transaction, flush the errors to the database.

## Domain Events vs Application Events

There is an important distinction between Domain Events and Application Events in DDD.

Domain Events are meaningful business occurrences that other parts of the domain care about. "Order was placed." "Payment was received." These are persisted in the same transaction as the aggregate root.

Application Events report that the database changed with a brief description of why. They help developers avoid data corruption. They are useful but not the same as notifying interested parties that something important happened.

The pattern shown here uses Application Events. The advice from Scott Millett's DDD reference applies: "Decouple the publishing and handling of events so that side effects are isolated. This approach stores a collection of events on the aggregate root and publishes them once the aggregate root has completed its task."

## The Exception Report Table

The database table for capturing failures:

```sql
CREATE TABLE `exception_reports` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `description` VARCHAR(255) NOT NULL DEFAULT '',
    `detail`      JSON DEFAULT NULL,
    `created`     DATETIME NOT NULL,
    `modified`    DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;
```

Each row represents a rare and random failure that needs human examination. This table has proven useful for errors that are not so rare and not so random. During development, database errors that are less than obvious get captured here: wrong column names, validation failures, plain coding errors.

When following this pattern during development, the exception reports table tells you exactly what happened. This is also helpful when problems sneak into production. If a new feature gets out of sync with a database table change, the exception report informs you right away.

## The Record Exception Report Class

This class captures information independently of the database transaction:

```php
final class RecordExceptionReport
{
    private static array $captures = [];

    private function __construct()
    {
    }

    public static function capture(
        string $description,
        array $detail = []
    ): void {
        self::$captures[] = new ExceptionReportEntity(
            $description,
            $detail
        );
    }

    public static function flush(bool $isTest = false): void
    {
        if (count(self::$captures) === 0) {
            return;
        }

        if ($isTest) {
            self::reset();
            return;
        }

        $repository = new RExceptionReport();
        if ($repository->flush(self::$captures)) {
            self::reset();
        }
    }

    public static function errorCount(): int
    {
        return count(self::$captures);
    }

    public static function reset(): void
    {
        self::$captures = [];
    }
}
```

Both `capture()` and `flush()` are static, so they can be called from anywhere in the codebase. `capture()` gathers information into an `ExceptionReportEntity` in memory. `flush()` writes those entities to the database, only called outside a transaction. If nothing has been captured, `flush()` returns immediately.

## The Exception Report Entity

```php
final class ExceptionReportEntity
{
    private string $description;
    private ?array $detail;

    public function __construct(
        string $description,
        ?array $detail = null
    ) {
        $this->description = $description;
        $this->detail = $detail;
    }

    public function data(): array
    {
        return [
            'description' => $this->description,
            'detail' => $this->detail,
        ];
    }
}
```

This class is intentionally simple. It captures provided information and formats it for database insertion during `flush()`.

## The Repository Pattern for Exceptions

```php
final class RExceptionReport
{
    use ExceptionReportTrait;

    /** @param ExceptionReportEntity[] $captures */
    public function flush(array $captures): bool
    {
        if (empty($captures)) {
            return true;
        }

        $this->loadExceptionReportsTable();
        $conn = $this->exceptionReportsTable->getConnection();

        try {
            $conn->transactional(function () use ($captures) {
                foreach ($captures as $capture) {
                    $data = $capture->data();
                    $entity = $this->exceptionReportsTable
                        ->newEntity($data);
                    $this->exceptionReportsTable
                        ->saveOrFail($entity);
                }
            });
        } catch (\Exception) {
            return false;
        }

        return true;
    }
}
```

We cannot record an exception while flushing exceptions. If the flush fails, we return `false` and the caller retains the captured data. The static `$captures` array is only cleared on successful flush.

## Disabling Application Events for Production

Once the full pattern is in place, consider whether Application Events should run in production. There is no need to risk degrading production database performance without careful planning.

```php
interface CDisableAppEvent
{
    public const DISABLE_APP_EVENT = true;
}

abstract class BaseAppEvent implements CDisableAppEvent
{
    public function save(\Cake\Database\Connection $connection): void
    {
        if (self::DISABLE_APP_EVENT) {
            return;
        }

        $parms = [
            $this->action,
            static::$subsystem,
            $this->description,
            $this->detail,
            $this->uuid,
        ];

        $this->readback = $this->repository->save(
            static::$insert,
            static::$read,
            $parms,
            $connection
        );
    }

    public function notify(): void
    {
        if (self::DISABLE_APP_EVENT) {
            return;
        }

        if (empty($this->readback)) {
            return;
        }

        $domainEvent = DomainEventFactory::domainEvent();
        $domainEvent->notifyDomainEvent(
            static::$sourceTable,
            $this->readback
        );
    }
}
```

This approach allows designing for both Application Events and recording rare exceptions. Exception reports still work regardless of whether Application Events are published.

## Changed Habits

The drawback of this approach is that every database change needs to be placed inside a `try`/`catch` block. If including the Application Event pattern, each database change requires a manual transaction within the `try` block.

Designing the `try`/`catch` block forces you to consider what to do in case of failure. That is a good thing. You now have a standard way of reporting failures. If they rarely happen, perhaps one in a million updates, so much the better.

This changed habit feels like a hassle at first. It is more work and more code to write for a given task. However, once in place, it pays for itself the first time a rare failure occurs and the exception report tells you exactly what happened.

## Answering the Essential Questions

**How do we implement transactional consistency in our Bounded Context pattern?** With a database transaction.

**How do we enforce an invariant in our Bounded Context pattern?** With a database transaction inside a `try`/`catch` block.

**How do we record random and rare failures?** By gathering information during the failure and persisting that information after the problem transaction has rolled back.

The code patterns shown here are adaptable to any PHP framework or ORM. The principles persist regardless of the implementation details. Every PHP project will face rare and random failures. Having a systematic approach to capturing them turns unknowns into actionable data.
