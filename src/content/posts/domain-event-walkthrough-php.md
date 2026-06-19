---
title: "Domain Event Walkthrough - Implementing Domain Events in PHP DDD"
description: "Learn how to implement Domain Events in PHP using Domain-Driven Design patterns. Covers event definition, dispatch, repositories, and factory patterns."
pubDate: "2022-09-24 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "Domain Events", "PHP", "Event-Driven", "Architecture"]
selected: false
---

Domain Events are a core tactical pattern in Domain-Driven Design. They represent something meaningful that happened in the domain — an order was placed, a payment was received, a user registered. Other parts of the system react to these events without tight coupling.

This article walks through implementing a global Domain Event store using the Bounded Context pattern introduced in earlier DDD Alley columns. We will build the Application Service, Repository, Factory, and Interface layers, then implement the same feature in both a greenfield project and a legacy codebase.

The full source code is available at `github.com/ewbarnard/strategic-ddd`.

## The Bounded Context Layers

Within a Bounded Context, we build four software layers:

| Layer | Responsibility | Framework-Specific? |
|---|---|---|
| Application Service | Use case handler, entry point | No |
| Repository | Data persistence | Yes |
| Domain Model | Business logic, entities | No |
| Factory | Object construction | Minimal |

The Application Service and Domain Model should be essentially identical across projects. The Repository will differ sharply based on the framework (CakePHP, Doctrine, raw SQL, etc.).

## Database Schema

The global domain event store records events from all Bounded Contexts in the system:

```sql
CREATE TABLE `domain_events` (
    `id`            bigint unsigned NOT NULL AUTO_INCREMENT,
    `id_of_source`  bigint unsigned NOT NULL,
    `source_table`  varchar(255)    NOT NULL,
    `action`        varchar(255)    NOT NULL DEFAULT '',
    `subsystem`     varchar(255)    NOT NULL DEFAULT '',
    `description`   varchar(255)    NOT NULL DEFAULT '',
    `detail`        json                     DEFAULT NULL,
    `event_uuid`    char(36)        NOT NULL,
    `when_occurred` timestamp(6)    NOT NULL,
    `created`       datetime        NOT NULL,
    `modified`      datetime        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `event_uuid` (`event_uuid`),
    UNIQUE KEY `source_id` (`id_of_source`, `source_table`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
```

Each event has a UUID, a reference to the source record, and a flexible `detail` JSON column for event-specific data.

## The Domain Event Interface

Start with the interface that defines the contract:

```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\Infrastructure\Events\DomainEvent\DomainModel\Interfaces;

interface IDomainEvent
{
    public function notifyDomainEvent(
        string $sourceTable,
        array $localEvent
    ): void;
}
```

This is the Separated Interface pattern — the interface lives in the Domain Model layer, while the implementation goes in Application Services.

## The Application Service

The use case handler receives events and delegates to the repository:

```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\Infrastructure\Events\DomainEvent\ApplicationServices;

use App\BoundedContexts\Infrastructure\Events\DomainEvent\DomainModel\Interfaces\IDomainEvent;
use App\BoundedContexts\Infrastructure\Events\DomainEvent\Repository\RDomainEvent;

final class DomainEvent implements IDomainEvent
{
    private RDomainEvent $repository;

    public function __construct(RDomainEvent $repository)
    {
        $this->repository = $repository;
    }

    public function notifyDomainEvent(
        string $sourceTable,
        array $localEvent
    ): void {
        $this->repository->saveDomainEvent($sourceTable, $localEvent);
    }
}
```

The service is thin — it validates nothing, transforms nothing. Its job is to be the entry point that the rest of the application calls. All persistence logic lives in the repository.

## The Repository (CakePHP)

The framework-specific repository handles the database interaction:

```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\Infrastructure\Events\DomainEvent\Repository;

use App\Model\Entity\DomainEvent;

final class RDomainEvent
{
    use GlobalEventsTrait;

    public function __construct()
    {
        $this->loadModels();
    }

    private function loadModels(): void
    {
        $this->loadDomainEventsTable();
    }

    /**
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     */
    public function saveDomainEvent(
        string $sourceTable,
        array $localEvent
    ): void {
        $localEvent[DomainEvent::FIELD_ID_OF_SOURCE] = $localEvent[DomainEvent::FIELD_ID];
        unset($localEvent[DomainEvent::FIELD_ID]);
        $localEvent[DomainEvent::FIELD_SOURCE_TABLE] = $sourceTable;

        $entity = $this->domainEventsTable->newEntity($localEvent);
        $entity->setDirty(DomainEvent::FIELD_WHEN_OCCURRED, true);
        $entity->setDirty(DomainEvent::FIELD_CREATED, true);
        $entity->setDirty(DomainEvent::FIELD_MODIFIED, true);

        $this->domainEventsTable->saveOrFail($entity);
    }
}
```

The repository owns all CakePHP-specific code — table objects, entity creation, and ORM operations. If the framework changes, this is the only file that changes.

## The Factory

The factory wires the application service to its repository:

```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\Infrastructure\Events\DomainEvent\Factory;

use App\BoundedContexts\Infrastructure\Events\DomainEvent\ApplicationServices\DomainEvent;
use App\BoundedContexts\Infrastructure\Events\DomainEvent\DomainModel\Interfaces\IDomainEvent;
use App\BoundedContexts\Infrastructure\Events\DomainEvent\Repository\RDomainEvent;

final class DomainEventFactory
{
    private function __construct()
    {
    }

    public static function domainEvent(): IDomainEvent
    {
        $repository = new RDomainEvent();
        return new DomainEvent($repository);
    }
}
```

The factory returns the interface, not the concrete service. This lets callers depend on the abstraction. Unit tests swap in a mock repository by creating a different factory.

## The Command-Line Harness

During development, a console command exercises the feature:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Ramsey\Uuid\Uuid;

final class DomainEventCommand extends Command
{
    use GlobalEventsTrait;

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->loadDomainEventsTable();
        $service = DomainEventFactory::domainEvent();

        $data = [
            LocalAppEvent::FIELD_ACTION => 'Test',
            LocalAppEvent::FIELD_SUBSYSTEM => 'Command Line',
            LocalAppEvent::FIELD_DESCRIPTION => sprintf('%.6f', microtime(true)),
            LocalAppEvent::FIELD_DETAIL => null,
            LocalAppEvent::FIELD_EVENT_UUID => Uuid::uuid4()->toString(),
            LocalAppEvent::FIELD_WHEN_OCCURRED => FrozenTime::now(),
            LocalAppEvent::FIELD_CREATED => FrozenTime::now(),
            LocalAppEvent::FIELD_MODIFIED => FrozenTime::now(),
        ];

        $event = $this->domainEventsTable->newEntity($data)->toArray();
        $event[LocalAppEvent::FIELD_ID] = random_int(1, 999999999);

        $service->notifyDomainEvent('command line', $event);
        $io->out('Domain event complete.');

        return 0;
    }
}
```

## Legacy Implementation

The same pattern applies to a legacy codebase. The Application Service and Factory are nearly identical. The Repository differs because it uses Doctrine's DBAL instead of CakePHP's ORM:

```php
<?php

declare(strict_types=1);

namespace LegacyBoundedContexts\Infrastructure\Events\DomainEvent\Repository;

use Doctrine\DBAL\DBALException;
use Models\Common\BaseModel;

final class RDomainEvent extends BaseModel
{
    /**
     * @throws DBALException
     */
    public function saveDomainEvent(
        string $sourceTable,
        array $localEvent
    ): void {
        $sql = 'INSERT INTO domain_events
                (id_of_source, source_table, action, subsystem, description,
                 detail, event_uuid, when_occurred, created, modified)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $params = [
            $localEvent['id'],
            $sourceTable,
            $localEvent['action'],
            $localEvent['subsystem'],
            $localEvent['description'],
            $localEvent['detail'],
            $localEvent['event_uuid'],
            $localEvent['when_occurred'],
            $localEvent['created'],
            $localEvent['modified'],
        ];

        try {
            $this->sql->executeUpdate($sql, $params);
        } catch (DBALException $e) {
            // Log and fail silently for now
        }
    }
}
```

The test harness for the legacy version:

```php
<?php

use LegacyBoundedContexts\Infrastructure\Events\DomainEvent\Factory\DomainEventFactory;
use Ramsey\Uuid\Uuid;

require_once __DIR__ . '/bootstrap.php';

$service = DomainEventFactory::domainEvent();

try {
    $event = [
        'id' => random_int(1, 99999999),
        'action' => 'Legacy Test',
        'subsystem' => 'Command Line',
        'description' => sprintf('%.6f', microtime(true)),
        'detail' => null,
        'event_uuid' => Uuid::uuid4()->toString(),
        'when_occurred' => date('Y-m-d H:i:s'),
        'created' => date('Y-m-d H:i:s'),
        'modified' => date('Y-m-d H:i:s'),
    ];

    $service->notifyDomainEvent('legacy command line', $event);
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

echo 'Domain event complete' . PHP_EOL;
```

## Essential Questions Answered

**What are the software layers within a Bounded Context?**
Application Service, Repository, Domain Model, Factory.

**Which layers are identical across greenfield and legacy projects?**
Application Service and Domain Model. The business logic does not change. Only the infrastructure varies.

**Which layer differs between frameworks?**
The Repository. It contains all database-specific code. The Factory may differ slightly depending on auto-wiring and dependency injection capabilities.

## Summary

- The Domain Event command acts as a test harness during development
- The Factory connects the application service to its dependencies
- The Repository contains all database-specific and framework-specific code
- The Application Service is the use case handler — thin, focused, testable
- The Interface demonstrates the Separated Interface pattern

The Bounded Context pattern ensures you can implement the same domain feature in a greenfield application and a legacy codebase with nearly identical business logic. Only the Repository layer changes. This is the payoff of careful layer separation — framework independence where it matters, framework specificity where it is unavoidable.
