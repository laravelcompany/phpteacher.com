---
title: "Domain-Driven Design in PHP - From Transaction Script to Clean Architecture"
description: "Learn how to refactor a legacy PHP codebase using Domain-Driven Design. Step-by-step guide with real code showing Application Services, Repositories, and the Dragon Wrangling Pattern."
pubDate: "2022-01-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "Domain-Driven Design", "PHP", "Architecture", "Refactoring", "SOLID", "Application Services", "Repository Pattern", "Clean Code"]
selected: false
---

You know the moment. You stare at a 1,500-line controller class, and your cursor blinks somewhere around line 847. You need to add one more condition to the registration flow. Nothing crazy — just a new field. But your stomach tightens because you know this file has more conditional branches than a choose-your-own-adventure novel. Every change feels like diffusing a bomb.

This is what developers call "hitting the wall." The code works. Tests pass (mostly). But the cognitive load of making any change is paralyzing. You're afraid to touch it, and onboarding a new developer means watching them drown for three weeks before they can ship a single feature.

There's a way out. It doesn't require rewriting everything overnight. It starts with a pattern shift — from Transaction Script to Domain-Driven Design — applied one use case at a time.

## What You'll Learn

- When to abandon Transaction Script for a Domain Model
- How to break monolithic controllers into Application Services
- The Dragon Wrangling Pattern for managing complex workflows
- How to wire services with Factories and handle errors centrally
- Practical PHP 8.1+ code examples you can use today

## When Transaction Script Hits the Wall

The Transaction Script pattern (from Fowler's Patterns of Enterprise Application Architecture) works beautifully — until it doesn't. For simple CRUD operations and straightforward web pages, it's perfect. You get a request, you do a thing, you send a response. Done.

The trouble starts when your domain logic grows teeth. Consider a real-world example: a sports competition management platform. Athletes register for a season, choose disciplines, handle payments, manage team affiliations, and navigate a dozen edge cases. One registration page has 43 happy-path scenarios. Forty-three. And that's before you consider the error states.

Here's the pattern that signals you've hit the wall:

```php
class RegistrationController
{
    public function process(Request $request): Response
    {
        // 1,500 lines of if-else, foreach, try-catch, and despair
    }
}
```

That file started at 200 lines. Then came team payments. Then donation processing. Then medical info requirements. Then four shooting disciplines with different pricing. Then multi-session registration. Each addition was tiny. Each one was rational. Together, they created something nobody wants to touch.

The project was the USA Clay Target League — a real SaaS platform managing thousands of athletes, teams, and competitions. The complexity wasn't accidental. Sports registration naturally involves overlapping entities: athletes belong to teams, teams have seasons, seasons have disciplines, and every combination has its own payment rules. The "chicken and egg" problem of creating an athlete before they have a team, or a team before it has athletes, makes simple CRUD impossible.

## Enter the Dragon Wrangling Pattern

Before you can refactor, you need a mental model for the complexity. I call it the Dragon Wrangling Pattern.

Imagine each business rule is a dragon. A single dragon is manageable — you know its fire range, its temperament, its feeding schedule. But a hoard of 43 dragons, each with different rules and interactions? That's a dragon-wrangling problem. You don't fight dragons one at a time inside a single arena (your 1,500-line controller). You give each dragon its own enclosure, its own handler, and a clear protocol for when it interacts with other dragons.

In software terms: each use case gets its own Application Service. Each domain concept gets its own Repository. Each cross-cutting concern (error handling, transactions, logging) gets its own layer. You stop solving every problem in one file and start assigning responsibility to focused, testable units.

## Breaking Down the Workflow

The first step is mapping your workflows. Take that monolith and identify the distinct use cases hiding inside it.

For the athlete registration system, the workflows look like this:

1. **Account Creation** — Register as a new user
2. **Team Invitation** — Join or create a team
3. **Season Registration** — Register for a season with disciplines
4. **Medical Info Submission** — Add required health information
5. **Payment Processing** — Handle Athlete Pay vs Team Pay, donations
6. **Registration Confirmation** — Finalize and confirm enrollment

Each of these becomes an independent Application Service. Each service handles one thing and handles it well. Here's how that transformation looks.

## The Application Service Layer

An Application Service is a use case handler. It sits between your HTTP layer and your domain logic, orchestrating operations without containing business rules itself.

### Before: The Transaction Script Monster

```php
// Old approach: everything in the controller
public function addMedicalInfo(Request $request): Response
{
    try {
        $athleteId = $request->get('athlete_id');
        $conditions = $request->get('medical_conditions', []);
        $emergencyContact = $request->get('emergency_contact');
        $emergencyPhone = $request->get('emergency_phone');
        $insuranceProvider = $request->get('insurance_provider');
        $policyNumber = $request->get('policy_number');

        // Validation
        if (empty($emergencyContact) || empty($emergencyPhone)) {
            $this->flash->error('Emergency contact is required');
            return $this->redirectBack();
        }

        // Business logic mixed with persistence
        $db = $this->getDatabase();
        $db->begin();

        // Check if record exists
        $existing = $db->query(
            'SELECT id FROM medical_info WHERE athlete_id = ?',
            [$athleteId]
        )->fetch();

        if ($existing) {
            $db->query(
                'UPDATE medical_info SET conditions = ?, emergency_contact = ?,
                 emergency_phone = ?, insurance_provider = ?, policy_number = ?
                 WHERE athlete_id = ?',
                [json_encode($conditions), $emergencyContact, $emergencyPhone,
                 $insuranceProvider, $policyNumber, $athleteId]
            );
        } else {
            $db->query(
                'INSERT INTO medical_info (athlete_id, conditions, emergency_contact,
                 emergency_phone, insurance_provider, policy_number)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$athleteId, json_encode($conditions), $emergencyContact,
                 $emergencyPhone, $insuranceProvider, $policyNumber]
            );
        }

        $db->commit();
        $this->flash->success('Medical information saved');
        return $this->redirect('dashboard');

    } catch (\Exception $e) {
        $db->rollback();
        $this->flash->error('Something went wrong');
        return $this->redirectBack();
    }
}
```

This is what fragility looks like. Validation, business rules, persistence, and HTTP concerns are all tangled together. A change to any one concern risks breaking the others.

### After: The Application Service

```php
#[AsCommand('athlete.add-medical-info')]
final class AddMedicalInfo
{
    public function __construct(
        private readonly RAddMedicalInfo $repository,
        private readonly ReportError $errorHandler,
    ) {
    }

    public function handle(AddMedicalInfoCommand $command): Result
    {
        try {
            $athleteId = new AthleteId($command->athleteId);
            $conditions = array_map(
                fn(string $c) => MedicalCondition::from($c),
                $command->medicalConditions,
            );

            $info = MedicalInfo::create(
                athleteId: $athleteId,
                conditions: $conditions,
                emergencyContact: new PersonName(
                    $command->emergencyContact,
                    $command->emergencyPhone,
                ),
                insurance: new Insurance(
                    provider: $command->insuranceProvider,
                    policyNumber: $command->policyNumber,
                ),
            );

            $this->repository->save($info);

            return Result::success($info);
        } catch (ValidationFailed $e) {
            return $this->errorHandler->handleValidation($e);
        } catch (DomainError $e) {
            return $this->errorHandler->handleDomain($e);
        }
    }
}
```

Every dependency is explicitly injected. The service takes a command object (a simple DTO), builds domain objects, delegates persistence to the repository, and lets a dedicated error handler deal with failures. Testing this in isolation is trivial — you mock the repository and assert the command produces the right domain calls.

## The Repository Pattern (Specific, Not Generic)

A common mistake with repositories is making them generic. `RepositoryInterface<T>` with `find($id)`, `save($entity)`, `delete($id)` — this adds abstraction without adding value. Generic repositories just shift your SQL into a different file without capturing domain intent.

Instead, name your repository methods after your use cases:

```php
interface RAddMedicalInfo
{
    public function save(MedicalInfo $info): void;
    public function findByAthleteId(AthleteId $id): ?MedicalInfo;
    public function deleteForAthlete(AthleteId $id): void;
}
```

This interface tells you exactly what it does. There's no guessing whether `find(123)` returns a medical record, an athlete, or a team. The implementation handles persistence details, but the interface speaks the language of the domain.

```php
final class PgRAddMedicalInfo implements RAddMedicalInfo
{
    public function __construct(
        private readonly Connection $db,
    ) {
    }

    public function save(MedicalInfo $info): void
    {
        $serialized = $this->serialize($info);

        // Reload within transaction to avoid stale data
        // MySQL gap locks and repeatable read isolation mean
        // we need to verify our assumptions before writing
        $existing = $this->findByAthleteId($info->athleteId);

        if ($existing !== null) {
            $this->db->update('medical_info', $serialized, [
                'athlete_id' => $info->athleteId->toString(),
            ]);
        } else {
            $this->db->insert('medical_info', $serialized);
        }
    }

    private function serialize(MedicalInfo $info): array
    {
        return [
            'athlete_id' => $info->athleteId->toString(),
            'conditions' => json_encode(
                array_map(fn(MedicalCondition $c) => $c->value, $info->conditions)
            ),
            'emergency_contact' => $info->emergencyContact->fullName(),
            'emergency_phone' => $info->emergencyContact->phone(),
            'insurance_provider' => $info->insurance?->provider,
            'policy_number' => $info->insurance?->policyNumber,
        ];
    }

    private function hydrate(array $row): MedicalInfo
    {
        return MedicalInfo::restore(
            athleteId: new AthleteId($row['athlete_id']),
            conditions: array_map(
                fn(string $c) => MedicalCondition::from($c),
                json_decode($row['conditions'], true),
            ),
            emergencyContact: new PersonName(
                $row['emergency_contact'],
                $row['emergency_phone'],
            ),
            insurance: new Insurance(
                provider: $row['insurance_provider'],
                policyNumber: $row['policy_number'],
            ),
        );
    }
}
```

Notice the `reload` pattern inside the transaction. This is critical for transaction safety. When you're inside a MySQL transaction with REPEATABLE READ isolation, the first SELECT establishes your snapshot. If another process modified the row between your read and your write, you'd overwrite their changes. By explicitly reloading — checking what's actually in the database right now — you avoid lost updates. It's a simple belt-and-suspenders approach that prevents subtle concurrency bugs.

## Wiring Services with Factories

With your services and repositories defined, you need to wire them together. A Factory pattern keeps instantiation centralized and makes dependencies explicit.

```php
final class CreateAccountFactory
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function create(): CreateAccount
    {
        return new CreateAccount(
            athleteRepository: $this->container->get(RCreateAccount::class),
            teamRepository: $this->container->get(RTeam::class),
            inviteRepository: $this->container->get(RTeamInvite::class),
            errorHandler: $this->container->get(ReportError::class),
            passwordHasher: $this->container->get(PasswordHasher::class),
        );
    }
}
```

You don't need aService Locator or auto-wiring magic. The Factory explicitly constructs each service with its dependencies. When you add a new dependency, the Factory's constructor signature changes — a compile-time check that you haven't forgotten anything. This gives you the wiring benefits of a DI container without the magic.

For larger applications, a PSR-11 container combined with auto-wiring for leaf dependencies works well. Use the Factory pattern for aggregate roots and complex services that need coordination.

## Centralized Error Handling

Scattered try-catch blocks are a maintenance nightmare. Some swallow exceptions. Some wrap them in generic "Something went wrong" messages. Some leak stack traces to the user. The `ReportError` class consolidates all error handling into one place.

```php
final class ReportError
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly FlashMessenger $flash,
        private readonly ErrorMapper $mapper,
    ) {
    }

    public function handleValidation(ValidationFailed $e): Result
    {
        $this->flash->error($e->getMessage());

        return Result::invalid(
            errors: $this->mapper->mapToUserMessages($e),
        );
    }

    public function handleDomain(DomainError $e): Result
    {
        $this->logger->warning('Domain constraint violated', [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->flash->error('This action could not be completed');

        return Result::failed(
            reason: $e->getMessage(),
        );
    }

    public function handleSystemFailure(\Throwable $e): Result
    {
        $this->logger->critical('Unexpected system failure', [
            'exception' => $e,
        ]);

        $this->flash->error(
            'An unexpected error occurred. Our team has been notified.'
        );

        return Result::failed(
            reason: 'Unexpected error',
            shouldRetry: true,
        );
    }
}
```

Every error path follows the same rules. Validation errors show specific messages. Domain errors get logged and return a user-friendly message. System failures trigger alerts and give the user a clear path forward. You can add monitoring, metrics, or Slack notifications in one place instead of hunting through every controller method.

## The Results: What Changes

After refactoring, the 1,500-line controller becomes a routing layer. Each endpoint delegates to an Application Service. The services are individually testable. The repositories are mockable. The error handling is consistent.

```
Before: 1 controller × 1,500 lines = 1,500 lines of untestable spaghetti
After:  6 services × ~80 lines    = 480 lines of testable orchestration
        5 repositories × ~100 lines = 500 lines of data access
        1 factory × ~60 lines      = 60 lines of wiring
        1 error handler × ~80 lines = 80 lines of error management
                                    = 1,120 lines total
```

The total line count actually decreases because you remove duplication. More importantly, the shape of the code changes. Business logic lives in domain objects. Data access lives in repositories. Error handling lives in one place. Each file has a single responsibility and a clear reason to change.

Testing becomes straightforward. Each Application Service test:

```php
final class AddMedicalInfoTest extends TestCase
{
    public function test_saves_valid_medical_info(): void
    {
        $repository = $this->createMock(RAddMedicalInfo::class);
        $errorHandler = $this->createMock(ReportError::class);

        $repository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(MedicalInfo::class));

        $service = new AddMedicalInfo($repository, $errorHandler);
        $command = new AddMedicalInfoCommand(
            athleteId: 'abc-123',
            medicalConditions: ['none'],
            emergencyContact: 'Jane Doe',
            emergencyPhone: '555-0123',
            insuranceProvider: 'Acme',
            policyNumber: 'POL-789',
        );

        $result = $service->handle($command);

        $this->assertTrue($result->isSuccess());
    }

    public function test_returns_validation_error_for_missing_contact(): void
    {
        // ...
    }

    public function test_handles_database_failure_gracefully(): void
    {
        // ...
    }

    public function test_preserves_existing_medical_info_on_update(): void
    {
        // ...
    }
}
```

No database, no HTTP, no session. Pure logic assertions running in milliseconds.

## Real-World Use Cases

This approach isn't limited to sports registration. Apply it anywhere your workflow complexity outgrows a single controller:

**SaaS Multi-Tenant Onboarding** — Account creation, workspace setup, billing plan selection, team invitations, SSO configuration. Each step is an Application Service. Each service validates within its bounded context before passing control to the next.

**Complex Registration Flows** — Conference registration with early-bird pricing, speaker discounts, group rates, add-on workshops, and hotel packages. The pricing rules change every year. Domain objects encapsulate the rules; Application Services coordinate the flow.

**Multi-Step Forms** — Insurance applications, mortgage approvals, medical intake. Each step persists partial data. Repositories handle the "save in progress" semantics. Application Services know whether to validate partially or fully.

**Payment Processing** — Subscriptions, prorations, refunds, chargebacks, invoice generation. Each financial operation is a use case. Domain objects enforce invariants (you can't refund more than charged). The repository provides an audit trail. Error handling distinguishes between payment-declined (user action needed) and gateway-timeout (retry later).

## Best Practices

**Test coverage before refactoring.** Before you extract a single Application Service, write Behat feature files or PHPUnit acceptance tests that document the current behavior. These tests become your safety net. When your refactored code matches the same behavior, you know you haven't broken anything. The team behind the USA Clay Target League used Behat feature descriptions as their specification guide — every scenario captured a real workflow that had to continue working.

**Bounded contexts matter.** Don't create one giant domain model. Split your system along natural boundaries. Athlete management, team management, competition scoring, billing — these are separate contexts with separate models. A `User` in the billing context has a payment method. A `User` in the scoring context has a handicap and a division. These are different objects that happen to represent the same real-world person.

**Naming conventions are architectural.** `RAddMedicalInfo` (prefix `R` for Repository) tells you this is a repository interface. `ReportError` follows verb-noun naming. `CreateAccount` is a command name. These conventions make the codebase navigable. When you see a class starting with `R`, you know it handles persistence. When you see `handle()`, you know it's an Application Service entry point. New developers can find their way around in hours instead of weeks.

**Transaction safety requires care.** When using MySQL with REPEATABLE READ, remember that your first SELECT establishes the snapshot. If you read a row, then another process modifies it, your write will silently overwrite. The reload-before-write pattern — re-fetching a row inside the transaction before updating — prevents this. For high-contention scenarios, consider SELECT ... FOR UPDATE or optimistic locking with version columns.

## Common Mistakes

**Over-engineering simple CRUD.** If your feature has one controller, one database table, and five lines of business logic, you don't need Application Services, Repositories, and Domain objects. You need a well-organized controller and maybe a model class. DDD is a tool for complexity, not a religion. Use Transaction Script until it hurts, then refactor.

**Skipping the tests.** Refactoring without tests is not refactoring — it's rewriting. You will introduce bugs. You won't catch them until production. The only safe way to restructure a complex codebase is to pin its behavior with tests first.

**Making repositories too generic.** Resist the urge to create `BaseRepository` or `CrudRepository`. Generic repositories hide domain intent behind save/find/delete. Specific repositories communicate what the system does. `RAddMedicalInfo::save()` is more expressive than `Repository::persist($entity)`.

**Putting business logic in services.** Application Services orchestrate. Domain objects execute. If your service has complex if-else chains, those rules belong in a domain object. The service calls `$athlete->canRegisterForSeason($season)`. It doesn't implement the eligibility logic inline.

**Ignoring bounded contexts.** The biggest mistake is creating one god-class `User` that does everything. Split your models. Duplicate data across contexts if necessary. It's cheaper than untangling a monolithic model later.

## Frequently Asked Questions

**Q: When should I adopt DDD in a PHP project?**

Adopt DDD when your Transaction Script becomes painful — around 500+ lines per controller, or when a single workflow has 10+ conditional branches. For simple CRUD, DDD adds overhead without benefit.

**Q: Do I need a DI container?**

No. Factories work perfectly for smaller applications. Use a PSR-11 container when you have 20+ services and the Factory boilerplate becomes its own maintenance problem. Start with Factories, graduate to a container.

**Q: How do I test Application Services?**

Mock the repositories and error handler. Pass a command object. Assert the correct repository methods were called with the correct domain objects. No database, no HTTP, no framework bootstrapping.

**Q: What about Doctrine ORM? Does it replace repositories?**

Doctrine's EntityManager is a generic repository. You still want domain-specific repository interfaces. Implement them using Doctrine internally. The rest of your application depends on the interface, not the ORM.

**Q: How do I handle cross-cutting concerns like logging?**

Inject a logger into your Application Services or use a decorator. The ReportError class handles logging for errors. For auditing, consider middleware or event dispatchers that wrap service execution.

**Q: Should services be static or instance-based?**

Always instance-based. Static methods are untestable — you can't mock them. Services with constructor injection make dependencies explicit and enable proper unit testing.

**Q: How do I manage transactions across multiple repositories?**

Open the transaction at the Application Service level. Pass a transaction-aware repository or use a Unit of Work pattern. The key insight: the Application Service controls the transaction boundary; repositories execute within it.

**Q: Can I use this approach in a legacy codebase incrementally?**

Yes. This is the whole point. Extract one use case at a time. Leave the rest of the codebase untouched. Every service you extract makes the system slightly less fragile. Over months, the balance shifts from legacy to domain-driven.

## The Takeaway

Domain-Driven Design is not about UML diagrams, entity-relationship modeling sessions, or strategic design workshops. It's about recognizing when your code has become too fragile to change and applying targeted architectural patterns to make it safe again.

Start with one workflow. Define its use case. Extract the Application Service. Build a repository that speaks the domain language. Wire it with a Factory. Route errors through a central handler. Test it in isolation.

You don't need to convince your team to "adopt DDD." You need to make one file better today. Tomorrow, another file. Eventually, you look around and realize the code you were afraid to touch has become code you're proud to ship.

The dragons are still there. But now each one has its own enclosure.
