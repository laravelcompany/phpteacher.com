---
title: "Application Event Walkthrough - Implementing Events in PHP DDD"
description: "Learn the difference between Application Events and Domain Events in PHP DDD. Covers event dispatching, async processing, Symfony EventDispatcher, RabbitMQ integration, and CQRS patterns."
pubDate: "2022-10-24 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "Application Events", "PHP", "Event-Driven", "CQRS"]
selected: false
---

Events are the nervous system of a Domain-Driven Design application. They connect bounded contexts, trigger side effects, and keep your domain model clean. But there's an important distinction that many implementations blur: Application Events versus Domain Events.

This month's code walkthrough builds on the Domain Event feature we implemented previously. We'll focus on the differences and show how Application Events handle concerns that Domain Events should not.

## Essential Questions

By the end of this article you should be able to answer:

- What is the difference between a Domain Event and an Application Event?
- Should the Application Event feature be used in production as-is?

## Application Event vs. Domain Event

A **Domain Event** captures something that happened in the domain that domain experts care about. "Order was placed." "Invoice was paid." Domain Events are named in the ubiquitous language and form part of the domain model's public record.

An **Application Event** captures something that happened at the application infrastructure level. "A database row was inserted." "A file was uploaded." Application Events typically trigger cross-cutting concerns like logging, auditing, or integration with external systems.

The key insight: Application Events, once published, become Domain Events. The Application Event is the raw infrastructure notification; the Domain Event is the meaningful business record.

## Test Harness

Our test command shows the basic flow:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use ...\AppEventFactory;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

final class AppEventCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $action = 'Command-line test';
        $description = 'app_event command';
        $appEvent = AppEventFactory::defaultAppEvent(
            $action,
            $description
        );
        $appEvent->save();
        $appEvent->notify();

        return 0;
    }
}
```

`save()` persists the event within a database transaction. `notify()` publishes the event only after the transaction commits successfully.

## Application Event Factory

The factory wires together the repository and service:

```php
<?php

declare(strict_types=1);

namespace ...\AppEvent\Factory;

use ...\ApplicationServices\DefaultAppEvent;
use ...\DomainModel\Constants\CAppEventOriginatingContexts;
use ...\DomainModel\Interfaces\IAppEvent;
use ...\Repository\RAppEventDefault;

class AppEventFactory implements CAppEventOriginatingContexts
{
    private function __construct()
    {
    }

    public static function defaultAppEvent(
        string $action,
        string $description,
        ?array $detail = null
    ): IAppEvent {
        $repository = new RAppEventDefault();
        return new DefaultAppEvent(
            $repository, $action, $description, $detail
        );
    }

    public static function dbStateChangeAppEvent(
        string $description,
        ?array $detail = null
    ): IAppEvent {
        return self::defaultAppEvent(
            self::ACTION_DB_STATE_CHANGE,
            $description,
            $detail
        );
    }
}
```

The `dbStateChangeAppEvent()` method is a convenience wrapper for recording every database update. Don't blindly put this into production — it generates a massive number of rows. But in development it gives you a comprehensive picture of database activity.

## Repository with Manual Transactions

The repository uses low-level SQL with prepared statements. This is intentional: we need to stay inside a database transaction that might span multiple operations:

```php
public function save(
    string $insert,
    string $read,
    array $parms
): array {
    $connection = $this->localAppEventsTable->getConnection();
    try {
        $connection->transactional(
            function ($conn) use ($insert, $read, $parms) {
                $statement = $conn->prepare($insert);
                $statement->execute($parms);
                $statement = $conn->prepare($read);
                $statement->execute([$statement->lastInsertId()]);
                $readback = $statement->fetchAll('assoc');
                if (!(is_array($readback) &&
                    array_key_exists(0, $readback))) {
                    throw new DatabaseException('Event readback failed');
                }
                $this->readback = $readback[0];
            }
        );
    } catch (Exception) {
        return [];
    }

    return $this->readback;
}
```

Raw SQL strings with `prepare()` and `execute()` prevent SQL injection while remaining framework-independent. This is important when a single application update spans multiple database servers and versions. PHP's PDO layer works the same way regardless of the ORM you use.

This does not mean you should build your entire application with raw SQL. It means that when you need transactional consistency across infrastructure boundaries, low-level PDO control gives you options that ORMs abstract away.

## Default Application Event

The child class provides the SQL strings:

```php
<?php

declare(strict_types=1);

namespace ...\ApplicationServices;

final class DefaultAppEvent extends BaseAppEvent
{
    protected static string $insert = <<<QUERY
        insert into `local_app_events`
        (action, subsystem, description, detail,
          event_uuid, when_occurred, created, modified)
        values (?, ?, ?, ?, ?, now(6), now(), now())
    QUERY;

    protected static string $read =
        'select * from `local_app_events` where id = ? limit 1';
}
```

Each child class provides its own `$insert` and `$read` strings, allowing variations for different event types, subsystems, or source tables.

## Base Application Service

Most of the logic lives here. The constructor captures everything needed for storage and performs a sanity check:

```php
public function __construct(
    IRAppEvent $repository,
    string $action,
    string $description,
    ?array $detail = null
) {
    $this->repository = $repository;
    $this->action = $action;
    $this->description = $description;
    $this->detail = is_array($detail)
        ? json_encode($detail, JSON_THROW_ON_ERROR)
        : null;
    $this->uuid = Uuid::uuid4()->toString();

    $this->validateSubclass();
}
```

The `addDetail()` method accumulates data across a long transaction — for example, persisting an invoice with line items:

```php
public function addDetail(array $detail): void
{
    $prior = (null === $this->detail)
        ? []
        : json_decode((string)$this->detail, true);
    $new = array_merge($prior, $detail);
    $this->detail = json_encode($new, JSON_THROW_ON_ERROR);
}
```

## The Notify Method

This is the key architectural decision. `save()` and `notify()` are completely separate:

```php
public function save(): void
{
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
        $parms
    );
}

public function notify(): void
{
    if (empty($this->readback)) {
        return;
    }
    $domainEvent = DomainEventFactory::domainEvent();
    $domainEvent->notifyDomainEvent(...);
}
```

`save()` runs inside the database transaction. `notify()` runs after the transaction commits. If the transaction rolls back, `$this->readback` is empty and `notify()` returns early. This guarantees you never publish an event for a failed operation.

The notify method publishes through the Domain Event feature — connecting two use case handlers. In production, you'd insert a message queue between them:

1. `notify()` packages the event data and sends it to RabbitMQ
2. A consumer receives the message and invokes the Domain Event service
3. The Domain Event service stores it in the global event store

## Interfacing for Flexibility

The repository interface keeps the factory in control of wiring:

```php
interface IRAppEvent
{
    public function save(
        string $insert,
        string $read,
        array $parms
    ): array;
}
```

The service interface:

```php
interface IAppEvent
{
    public function __construct(
        IRAppEvent $repository,
        string $action,
        string $description,
        ?array $detail = null
    );

    public function addDetail(array $detail): void;
    public function save(): void;
    public function notify(): void;
}
```

Type-hinting interfaces rather than concrete classes lets the test suite swap in fixtures easily and lets the factory decide how wiring works.

## Porting to Legacy Code

When porting this pattern to a legacy codebase, implement in reverse order:

1. Repository interface
2. Service interface
3. Originating context constants
4. Base service class
5. Default service class
6. Repository implementation
7. Factory
8. Test harness

Each layer depends only on the layer above it. The factory is the only class that knows how everything connects. If you're working with PHP 7.3 legacy code, the structure is identical — the only changes are property annotations and type syntax.

## Essential Questions Answered

- **What is the difference between Domain Event and Application Event?** The Application Event captures raw infrastructure state changes. Once published, it becomes a Domain Event in the global event store.
- **Should this be used in production as-is?** No. The current implementation calls the Domain Event service synchronously. Production code should insert a message queue between `notify()` and the Domain Event consumer to handle failures and backpressure.

## Summary

Application Events give you an audit trail of infrastructure-level changes while keeping your domain model clean. The two-step save-and-notify pattern ensures you only publish events for successfully committed transactions. In production, add a message queue between Application Events and Domain Events to handle random failures gracefully and maintain system resilience.
