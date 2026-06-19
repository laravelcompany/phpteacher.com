---
title: "Structure by Use Case - Organizing DDD Code in PHP"
seoTitle: "Structure by Use Case - Organizing DDD Code in PHP"
description: "Organize your Domain-Driven Design PHP code by use case. Learn practical folder structures and naming conventions for maintainable DDD applications."
category: "DDD"
banner: "/logo.svg"
pubDate: "2022-07-28 21:00:00"
tags: ["DDD", "PHP", "Use Case", "Architecture", "Domain-Driven Design"]
---

Domain-Driven Design gives you powerful patterns for modeling complex business domains, but translating those patterns into actual code structure can be paralyzing. Where do the files go? How do you name things? How do you prevent framework coupling while still using the framework's strengths?

This article introduces a practical, repeatable folder structure based on Bounded Contexts and Application Services—the pattern I call "Structure by Use Case." You can implement it in Laravel, Symfony, CakePHP, or any modern PHP framework.

## The Problem with Framework-First Structure

Most PHP frameworks encourage organizing code by technical role:

```
app/
  Controllers/
    UserController.php
  Models/
    User.php
  Repositories/
    UserRepository.php
  Services/
    UserService.php
```

This structure works for simple CRUD applications, but it breaks down as complexity grows. Related logic for a single feature is scattered across five directories. When you need to understand how "forgot password" works, you're hunting through controllers, models, services, and repositories. There's no cohesive feature boundary.

## Structure by Use Case

Instead of organizing by technology, organize by use case. Each feature gets its own set of classes within a Bounded Context.

The folder structure looks like this:

```
src/BoundedContexts/
  SpikeCountEvents/
    ApplicationServices/
      CountEvents.php
    DomainModel/
    Factory/
      CountEventsFactory.php
    Repository/
      RCountEvents.php
  Infrastructure/
    ...
```

Every Bounded Context gets four folders, even if some start empty:

1. **ApplicationServices** — Use case implementations
2. **DomainModel** — Domain entities, value objects, aggregates
3. **Factory** — Object construction
4. **Repository** — Data access, framework-dependent code

## Why This Works

### The Application Service Is the Use Case

An Application Service represents a single use case. It orchestrates the workflow by delegating to the domain model and infrastructure layers. It contains no framework dependencies—no request objects, no response objects, no database code.

```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\SpikeCountEvents\ApplicationServices;

use App\BoundedContexts\SpikeCountEvents\Repository\RCountEvents;

final class CountEvents
{
    private RCountEvents $repository;

    public function __construct(RCountEvents $repository)
    {
        $this->repository = $repository;
    }

    public function insertCurrentCount(): void
    {
        $count = $this->repository->collectCount();
        $this->repository->storeCount($count);
    }
}
```

This class does exactly one thing: count events and store the result. It doesn't care if it's invoked from a CLI command, a web controller, or a unit test.

### The Factory Connects Layers

Factories decouple the use case from dependency construction:

```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\SpikeCountEvents\Factory;

use App\BoundedContexts\SpikeCountEvents\ApplicationServices\CountEvents;
use App\BoundedContexts\SpikeCountEvents\Repository\RCountEvents;

final class CountEventsFactory
{
    private function __construct()
    {
    }

    public static function countEvents(): CountEvents
    {
        $repository = new RCountEvents();
        return new CountEvents($repository);
    }
}
```

The factory is the only place that knows how to wire concrete dependencies. If you use a dependency injection container, you'd register the factory or let the container wire it directly.

### The Repository Isolates Framework Code

The repository pattern is often misunderstood. Its purpose isn't to abstract away the database—it's to make database access **explicit and intention-revealing**:

```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\SpikeCountEvents\Repository;

use App\BoundedContexts\Infrastructure\LoadTableModels\PrimaryDatabase\Events\AppEventsPrimaryTrait;
use App\BoundedContexts\Infrastructure\LoadTableModels\PrimaryDatabase\Events\EventCountsTrait;
use App\Model\Entity\EventCount;
use Cake\I18n\FrozenTime;

final class RCountEvents
{
    use AppEventsPrimaryTrait;
    use EventCountsTrait;

    public function __construct()
    {
        $this->loadModels();
    }

    private function loadModels(): void
    {
        $this->loadLocalAppEventsTable();
        $this->loadEventCountsTable();
    }

    public function collectCount(): int
    {
        return $this->localAppEventsTable->find()->count();
    }

    public function storeCount(int $count): void
    {
        $data = [
            EventCount::FIELD_WHEN_COUNTED => FrozenTime::now(),
            EventCount::FIELD_EVENT_COUNT => $count,
        ];
        $entity = $this->eventCountsTable->newEntity($data);
        $this->eventCountsTable->saveOrFail($entity);
    }
}
```

Notice what the repository does NOT do: it doesn't expose generic `find()`, `save()`, or `delete()` methods. It exposes **business-relevant** methods like `collectCount()` and `storeCount()`. This is the essence of the Repository pattern—expressing persistence intent in domain language, not SQL.

As Scott Millett writes in *Patterns, Principles, and Practices of Domain-Driven Design*:

> Instead of offering an open interface into the data model that supports any query or modification, the repository makes retrieval explicit by using named query methods and limiting access to the aggregate level. By making retrieval explicit, it becomes easy to tune queries, and more importantly express the intent of the query in terms a domain expert can understand rather than in SQL.

## Connecting to the Framework

The framework wrapper (CLI command, HTTP controller) is thin:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\BoundedContexts\SpikeCountEvents\Factory\CountEventsFactory;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

final class CountEventsCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $service = CountEventsFactory::countEvents();
        $service->insertCurrentCount();
        $io->out('Count complete');

        return 0;
    }
}
```

That's it. The command handler creates the service (via the factory) and calls the use case method. No business logic leaks into the framework layer. If you migrate from CakePHP to Laravel or Symfony, you rewrite only this thin wrapper.

## Avoiding Framework Coupling

The Application Service should avoid framework dependencies whenever possible. But sometimes you need access to the HTTP request object (e.g., for the current user's ID). The solution: use PSR-7 interfaces.

If your framework implements PSR-7 (Symfony 5+, Laravel via bridge), your Application Service can depend on `Psr\Http\Message\ServerRequestInterface` instead of the framework-specific request class:

```php
<?php

final class CompleteOnboarding
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function __invoke(ServerRequestInterface $request): void
    {
        $userId = $request->getAttribute('user_id');
        $user = $this->users->findById($userId);

        if ($user === null) {
            return;
        }

        $user->completeOnboarding();
        $this->users->save($user);
    }
}
```

This Application Service is portable across any framework that supports PSR-7.

## Naming Conventions

With many use cases in a single Bounded Context, naming matters. Each use case typically generates multiple files: the Application Service, the Factory, the Repository, interfaces, and constants. Consistent naming prevents confusion:

| Concept | Convention | Example |
|---------|-----------|---------|
| Use case class | FeatureName | `CountEvents` |
| Factory suffix | FeatureName + `Factory` | `CountEventsFactory` |
| Repository prefix | `R` + FeatureName | `RCountEvents` |
| Constants interface | `C` + FeatureName | `CCountEvents` |
| Interface prefix | `I` + FeatureName | `ICountEvents` |
| Framework command | FeatureName + `Command` | `CountEventsCommand` |

You may prefer different conventions, and that's fine. The important thing is consistency within the project. When you see `RCountEvents`, you immediately know it's the repository for the CountEvents use case, not something related to a different feature.

## Handling Legacy Frameworks

The same pattern works in legacy codebases. Here's the same CountEvents use case in a PHP 7.3 codebase using Doctrine DBAL:

```php
<?php

declare(strict_types=1);

namespace LegacyBoundedContexts\SpikeCountEvents\Repository;

use Doctrine\DBAL\DBALException;
use Models\Common\BaseModel;

final class RCountEvents extends BaseModel
{
    public function collectCount(): int
    {
        $sql = 'select count(*) row_count from local_app_events limit 1';

        try {
            $statement = $this->sql->executeQuery($sql, []);
            $rows = $statement->fetchAll();
        } catch (DBALException $e) {
            return 0;
        }

        if (is_array($rows) && count($rows) === 1) {
            return $rows[0]['row_count'];
        }

        return 0;
    }

    public function storeCount(int $count): void
    {
        $sql = 'insert into event_counts (when_counted, event_count, created, modified)
                values (now(), ?, now(), now())';

        try {
            $this->sql->executeUpdate($sql, [$count]);
        } catch (DBALException $e) {
            echo 'Query failed: ' . $e->getMessage() . PHP_EOL . $sql . PHP_EOL;
        }
    }
}
```

The Application Service looks essentially the same as the CakePHP version. The framework-specific code stays in the repository. If you later migrate from legacy to a modern framework, you update only the repository class—the Application Service remains untouched.

## Testing Benefits

This structure makes unit testing straightforward. The Application Service depends on an interface or a concrete repository that you can mock:

```php
<?php

declare(strict_types=1);

namespace Test\LegacyBoundedContexts;

use LegacyBoundedContexts\SpikeCountEvents\ApplicationServices\CountEvents;
use LegacyBoundedContexts\SpikeCountEvents\Repository\RCountEvents;
use PHPUnit\Framework\TestCase;

class CountEventsTest extends TestCase
{
    public function test_insert_current_count_delegates_to_repository(): void
    {
        $repository = $this->createMock(RCountEvents::class);
        $repository->expects($this->once())
            ->method('collectCount')
            ->willReturn(42);
        $repository->expects($this->once())
            ->method('storeCount')
            ->with(42);

        $service = new CountEvents($repository);
        $service->insertCurrentCount();
    }
}
```

No database, no HTTP, no framework bootstrap. The test runs in milliseconds.

## When to Use This Structure

This approach shines when:

- Your application has multiple distinct features or subdomains
- You're building a long-lived application that will outlive its initial framework
- You need to test business logic without framework overhead
- You have a large team where clear boundaries prevent stepping on each other's code
- You anticipate migrating frameworks or upgrading major framework versions

It's overkill for simple CRUD applications with a few entities. For those, the standard framework structure works fine.

## Laravel Integration Example

In Laravel, the Bounded Context structure sits alongside the standard framework structure. Controllers stay in `app/Http/Controllers`, but instead of containing business logic, they delegate to Application Services:

```php
<?php

namespace App\Http\Controllers;

use App\BoundedContexts\Onboarding\Factory\CompleteOnboardingFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function complete(Request $request): JsonResponse
    {
        $service = CompleteOnboardingFactory::make();

        $service->completeOnboarding(
            userId: $request->user()->id,
            preferences: $request->input('preferences'),
        );

        return response()->json(['message' => 'Onboarding complete']);
    }
}
```

The factory handles dependency creation. The controller handles HTTP concerns. The Application Service handles business logic. Each layer has a single responsibility, and changing the HTTP layer (migrating from Laravel to Symfony, for instance) requires rewriting only the controllers.

## Symfony Integration Example

Symfony follows the same pattern with its own naming conventions:

```php
<?php

namespace App\Controller;

use App\BoundedContexts\Onboarding\Factory\CompleteOnboardingFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OnboardingController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        $service = CompleteOnboardingFactory::make();

        $service->completeOnboarding(
            userId: $request->getUser()->getId(),
            preferences: $request->toArray()['preferences'],
        );

        return $this->json(['message' => 'Onboarding complete']);
    }
}
```

In both cases, the Application Service remains unchanged. The framework-specific code is limited to the controller (HTTP) and the repository (persistence). This is the payoff for the structure.

## The Domain Model Layer

We've been focusing on Application Services and Repositories, but the Domain Model folder is where your business entities and value objects live:

```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\Onboarding\DomainModel;

use App\BoundedContexts\Onboarding\DomainModel\ValueObject\UserId;

final class OnboardingStatus
{
    public function __construct(
        private readonly UserId $userId,
        private bool $profileComplete = false,
        private bool $emailVerified = false,
        private bool $termsAccepted = false,
    ) {}

    public function isComplete(): bool
    {
        return $this->profileComplete
            && $this->emailVerified
            && $this->termsAccepted;
    }

    public function completeProfile(): void
    {
        $this->profileComplete = true;
    }

    public function verifyEmail(): void
    {
        $this->emailVerified = true;
    }

    public function acceptTerms(): void
    {
        $this->termsAccepted = true;
    }
}
```

The Domain Model has no framework dependencies. It doesn't know about databases, HTTP, or caching. It's pure PHP representing business rules. You can unit test it in milliseconds because there's nothing to bootstrap.

## Command and Query Separation

An important refinement of the Application Service pattern is separating commands (writes) from queries (reads). Commands change state; queries return data. They have different optimization and validation requirements:

```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\Onboarding\ApplicationServices;

use App\BoundedContexts\Onboarding\DomainModel\OnboardingStatus;
use App\BoundedContexts\Onboarding\DomainModel\ValueObject\UserId;

// Command - changes state
final class CompleteOnboarding
{
    public function __construct(
        private readonly OnboardingRepository $repository,
    ) {}

    public function __invoke(int $userId, array $preferences): void
    {
        $status = $this->repository->findStatusByUserId(new UserId($userId));

        $status->completeProfile();
        $status->acceptTerms();

        if (!empty($preferences)) {
            // Process preferences
        }

        $this->repository->save($status);
    }
}

// Query - returns data
final class GetOnboardingStatus
{
    public function __construct(
        private readonly OnboardingRepository $repository,
    ) {}

    public function __invoke(int $userId): array
    {
        $status = $this->repository->findStatusByUserId(new UserId($userId));

        return [
            'is_complete' => $status->isComplete(),
            'profile_complete' => true, // simplified
            'email_verified' => true,
            'terms_accepted' => true,
        ];
    }
}
```

Commands and queries have separate Application Service classes, separate repository methods, and separate test suites. This makes it easy to add caching only to queries, or to audit only commands.

## The Dragon Wrangling Pattern

The "Dragon Wrangling" pattern is a mental model for structuring use cases. The dragon is the complex business process. Wrangling means controlling it step by step:

1. **Identify the dragon** (the use case). What exactly is the business trying to achieve?
2. **Create a corral** (the Bounded Context). Draw a boundary around the relevant domain concepts.
3. **Assign wranglers** (Application Services). Each wrangler handles one specific task.
4. **Set up the tools** (Repositories). Give wranglers the tools they need to do their job.
5. **Provide supplies** (Domain Model). The entities and value objects the wranglers manipulate.

This pattern prevents the common DDD trap of trying to model everything perfectly upfront. Instead, you start with a rough Bounded Context, implement one use case, refine the boundary, implement the next use case, and iterate. The structure evolves with your understanding of the domain.

## Essential Questions Answered

**What's the purpose of the Application Service?** It represents and orchestrates a single use case. It delegates to the domain model and repository.

**Why is the Bounded Context pattern important?** It divides large models into manageable pieces, making dependencies between them explicit. As Martin Fowler explains, DDD deals with large models and teams by dividing them into different Bounded Contexts and being explicit about their interrelationships.

**How do you avoid framework coupling?** Use PSR-7 interfaces for request/response objects, and keep framework-specific code in the Repository layer. Application Services should be framework-agnostic.

**What makes the Repository pattern powerful?** It's laser-focused on specific queries that the application needs. Named methods like `findActiveUsers()` or `countEventsSince()` express intent in domain language rather than SQL. This makes the code self-documenting and the queries optimizable.

## Summary

Structure by Use Case means organizing code around features rather than technical layers. Each Bounded Context gets four folders: ApplicationServices, Repository, Factory, and DomainModel. Each Application Service handles one use case. Repositories isolate framework dependencies. Factories wire everything together.

This structure is repeatable, testable, and framework-agnostic. You'll use it over and over as you implement feature after feature, knowing that the business logic you write today will survive framework upgrades, team changes, and evolving requirements.
