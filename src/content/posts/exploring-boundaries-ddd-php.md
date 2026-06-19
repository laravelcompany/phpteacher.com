---
title: "Exploring Boundaries - Bounded Contexts in Domain-Driven Design PHP"
description: "Learn how to identify and implement bounded contexts in Domain-Driven Design PHP applications. Covers context mapping, integration patterns, shared kernel, and anti-corruption layers."
pubDate: "2022-08-22 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "PHP", "Bounded Context", "Architecture", "Domain-Driven Design"]
selected: false
---

Bounded Contexts are the foundation of Strategic Domain-Driven Design. They define explicit boundaries within your system where a particular domain model applies. Everything inside the boundary speaks the same language and follows the same rules. Everything outside the boundary is foreign territory.

Last month introduced the Bounded Context pattern. This month goes deeper: how to place boundaries, how to cross them, and how layers within a context evolve independently.

The source code is available at `github.com/ewbarnard/strategic-ddd`.

## Why Boundaries Matter

A boundary creates a contract. Code inside the boundary can change freely as long as it honors the contract with the outside. Code outside the boundary can change freely without worrying about breaking internal implementation details.

This protective isolation is the key benefit. Consider changing a database column name:

1. The DBA creates the new column with a default null value
2. You regenerate the framework's table models to pick up the new column
3. You update any Repository classes that reference the old column name
4. The Repository's public API stays the same—other layers never notice

Each step stays within its boundary. The new column arrives in production before the old column is dropped. No domino effect. No cascading changes.

## Layers Within a Bounded Context

A single Bounded Context contains multiple layers, each with its own boundary:

- **Application Service** — Orchestrates use cases, coordinates domain objects
- **Domain Model** — Business rules and behavior
- **Repository** — Persistence and retrieval
- **Factory** — Object creation and assembly

These layers are separate even though they live in the same context. Each layer can evolve independently so long as the boundary crossing mechanism stays intact.

### Boundary Crossing Rules

Crossing a boundary requires an explicit mechanism:

- Call a public method on an interface
- Use a Factory to obtain an object
- Invoke a Repository to load domain objects
- Dispatch a Domain Event

The direction of dependencies matters. Requests flow in one direction only:

```
Application Service → Repository
Application Service → Domain Model
Repository → Framework Models
Repository → Domain Model
```

Higher-level abstractions (domain concepts) should never depend on lower-level details (framework models, database connections). The Dependency Inversion Principle ensures this by having both sides depend on abstractions, not concretions.

## Folder Structure as Boundaries

The folder structure itself declares boundaries. Consider this layout for Infrastructure support across multiple contexts:

```
src/
└── BoundedContexts/
    └── Infrastructure/
        ├── LoadTableModels/
        └── Events/
            ├── AppEvent/
            │   ├── DomainModel/
            │   ├── Factory/
            │   └── Repository/
            └── DomainEvent/
                ├── DomainModel/
                ├── Factory/
                └── Repository/
```

Each subfolder is a boundary. Code inside `AppEvent/DomainModel` can only access what's within its own folder or what's explicitly provided through interfaces. This structure creates a separation of concerns that's visible in the file system.

### The Bounded Contexts Directory

Start by creating a `BoundedContexts` directory. Within it, create folders for each context. Don't worry about getting the boundaries perfect on the first try—it's easier to move several small folders than to extract and move parts of a large folder later.

Take a fine-grained approach. Create small bounded contexts. If you later discover that two contexts should be combined, merging is straightforward. The opposite—extracting a context from a monolith—is much harder.

## Implementing a Domain Event Infrastructure

Domain Events are a fundamental DDD pattern. Eric Evans defines them: "Something happened that domain experts care about."

The implementation challenge is storing and publishing events. Vaughn Vernon describes option 3 in *Implementing Domain-Driven Design*: create a special storage area in the same persistence store as your domain model. This is the Event Store, owned by your Bounded Context, not by your messaging infrastructure.

An out-of-band component reads unpublished events and publishes them through the messaging mechanism. The advantage: your model and events are consistent within a single, local transaction.

From a database standpoint, there are two copies of the event:

- **Application Event** — Stored locally within the Bounded Context
- **Domain Event** — Published to a global store for other contexts to consume

Building the global Domain Event infrastructure first gives you a foundation. When you later find agreement on what the Domain Events actually are, dropping them in is straightforward.

### Where to Place the Code

The global event store sits outside any particular Bounded Context—it's its own context. The local event store also sits outside any particular context unless it proves to be context-specific. Making them separate now and folding them in later is easier than extracting them later.

## The Separated Interface Pattern

Martin Fowler describes the Separated Interface pattern in *Patterns of Enterprise Application Architecture*:

> Define an interface in one package but implement it in another. This way a client that needs the dependency to the interface can be completely unaware of the implementation.

This pattern breaks dependencies that flow in the wrong direction. If your Domain Model needs to call a Repository method, define the interface in the Domain Model layer and implement it in the Repository layer. The Domain Model knows about the interface, not the implementation.

```php
// Domain Model boundary — knows nothing about persistence
interface IAppEvent
{
    public function eventId(): string;
    public function occurredAt(): \DateTimeImmutable;
    public function eventData(): array;
}

// Repository boundary — implements the interface
class AppEventRepository implements IAppEvent
{
    public function __construct(
        private \PDO $db
    ) {}

    public function eventId(): string
    {
        return $this->db->lastInsertId();
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }

    // ...
}
```

## The Observer Pattern Within Boundaries

Sometimes a lower layer genuinely needs to notify a higher layer. Framework table models, for example, can publish events using the Observer pattern. A Repository registers as an observer and receives notifications when models change.

Both the Repository and the framework model know about the event publishing mechanism. This is the Dependency Inversion Principle at work: both depend on the abstraction (the event interface), not on each other.

```php
interface TableModelObserver
{
    public function onModelCreated(array $data): void;
    public function onModelUpdated(array $data): void;
}

class FrameworkModel
{
    /** @var TableModelObserver[] */
    private array $observers = [];

    public function attach(TableModelObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function save(): void
    {
        // ... persistence logic ...

        foreach ($this->observers as $observer) {
            $observer->onModelCreated($this->toArray());
        }
    }
}

class Repository implements TableModelObserver
{
    public function onModelCreated(array $data): void
    {
        $this->eventStore->append(
            new AppEvent(
                eventId: $data['id'],
                occurredAt: new \DateTimeImmutable(),
                eventData: $data
            )
        );
    }
}
```

## Independent Evolution in Practice

The boundary between Application Service and Repository demonstrates independent evolution. The Application Service calls `$repository->findByCriteria($criteria)` and expects domain objects back. As long as the interface stays the same, the Application Service doesn't care whether the Repository queries MySQL, Redis, an API, or a file on disk.

This means you can:

1. Change the underlying database without touching use case code
2. Add caching within the Repository without the Application Service knowing
3. Swap storage backends by swapping the Repository implementation

The same principle applies to refactoring. When you need to restructure code within a layer, do it within that layer's boundary. No other layer needs to change.

## The Test Boundary

Unit tests often become tightly coupled to the code they test. When `TestClass` directly tests `ClassA::methodA()`, any refactoring of `ClassA` risks breaking the test. The production code becomes answerable to the test suite, which is the wrong direction of dependency.

When this becomes a problem, introduce an interface that only the tests use:

```php
// Interface that locks down the boundary for testing
interface IGameRepository
{
    /** @return Game[] */
    public function findAll(): array;
    public function findById(int $id): ?Game;
    public function save(Game $game): void;
}

// Production implementation
class GameRepository implements IGameRepository
{
    // ...
}

// Tests interact through the interface
class GameRepositoryTest extends TestCase
{
    public function test_it_persists_a_game(): void
    {
        $repo = new GameRepository($this->db);
        $game = new Game(name: 'Test Game', slug: 'test-game');

        $repo->save($game);
        $loaded = $repo->findById($game->id);

        $this->assertSame('Test Game', $loaded->name);
    }
}
```

The interface decouples the test from the implementation. You can refactor `GameRepository`'s internals freely as long as the interface contract holds.

## Essential Questions Answered

**Why is it important to place boundaries around software layers?**
Software within a boundary can evolve without risk of breaking anything outside. Software outside a boundary can evolve without breaking anything inside.

**Why treat Application Service, Repository, Domain Model, and Factory as separate layers within the same Bounded Context?**
So each can evolve independently. A Repository can change its persistence strategy without the Application Service knowing. The Domain Model can add new business rules without the Repository caring.

**Why shouldn't the Repository call the Application Service?**
Dependencies should move toward higher-level abstractions. The Application Service orchestrates; the Repository persists. When a Repository needs to communicate upward, use the Observer or Separated Interface pattern.

**What is the Test Boundary?**
When unit tests are tightly coupled to production code, they constrain refactoring. Testing through an interface breaks that dependency, allowing production code to evolve independently.

## Summary

A Bounded Context's size depends on the context. Don't get paralyzed trying to find the perfect boundaries. Create folders where they make sense at the time. Refine as you gain deeper understanding.

Each layer and each Bounded Context has protective boundaries. Communication passes in a single direction. The Dependency Inversion Principle and Separated Interface pattern maintain clean dependency flow. Start fine-grained and merge later if needed. The boundaries you set today give you the freedom to evolve tomorrow.
