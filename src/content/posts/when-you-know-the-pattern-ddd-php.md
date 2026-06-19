---
title: "When You Know the Pattern - Recognizing Domain-Driven Design Opportunities in PHP"
description: "Learn to recognize when Domain-Driven Design is right for your PHP project. Practical patterns for identifying bounded contexts, aggregates, and domain events in real-world applications."
pubDate: "2022-02-24 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "Domain-Driven Design", "PHP", "Architecture", "Design Patterns", "Bounded Context", "Aggregates", "Domain Events"]
selected: false
---

You're pairing with a teammate, looking at a pull request. They've added a new payment type to the system. You scan the controller — 60 new lines inside an already bloated method. Your stomach sinks. Not because the code is wrong, but because you've seen this pattern before. It's the same shape as the last five features that made this class harder to maintain. You don't know the exact bug that will surface, but you know one will.

This is the skill no tutorial teaches: pattern recognition. The ability to look at a codebase and feel the weight of a design decision before it breaks. In Domain-Driven Design, pattern recognition is everything. It's the difference between forcing DDD where it doesn't belong and applying it exactly when your project needs it.

## What You'll Learn

- How to recognize when Transaction Script has reached its limit
- The specific signals that tell you DDD will pay off
- How to build a ubiquitous language with non-technical stakeholders
- The Dragon Wrangling approach to incremental adoption
- PHP 8.1+ code examples of Value Objects, Enums, and Domain Events
- Practical patterns for e-commerce, billing, and multi-step workflows

## The Pattern Recognition Skill

Pattern recognition in software isn't about memorizing UML diagrams. It's about noticing repetition. You see the same bug fix applied three times in six months — that's a pattern. You watch a new developer make the same architectural mistake you made two years ago — that's a pattern. You feel dread opening a particular file — that's a pattern too.

The best architects I've worked with don't start with diagrams. They start with discomfort. A controller that's grown beyond 400 lines. A test that takes six seconds to run. A deployment that requires three people on Zoom. These are signals, not failures. The question is whether you recognize them early enough to act.

DDD is one of the most powerful pattern-recognition frameworks we have. It gives you vocabulary for what you're feeling. When you can name "bounded context" as the reason two teams keep stepping on each other's code, or "aggregate boundary" as the reason your transaction keeps timing out, you stop guessing and start designing.

### Reading the Room

Pattern recognition starts outside the code. Listen to your stakeholders. If they use different words to describe the same thing depending on context, you've found a bounded context boundary. If they say "customer" in billing discussions but "member" in support discussions, those are different models.

```php
// Billing context
final readonly class Customer
{
    public function __construct(
        public CustomerId $id,
        public BillingPlan $plan,
        public PaymentMethod $paymentMethod,
    ) {}
}

// Support context
final readonly class Member
{
    public function __construct(
        public MemberId $id,
        public string $name,
        public SupportTier $tier,
    ) {}
}
```

Same person. Different models. Different bounded contexts. The moment you have two teams using the same database table for different purposes, you've found a bounded context worth splitting.

## Transaction Script vs Domain Model

The Transaction Script pattern organizes code by procedure — one method per database operation. Martin Fowler described it in *Patterns of Enterprise Application Architecture* as the simplest approach: get a request, do some work, return a response. It's the default for most PHP applications, and it works perfectly for 80% of use cases.

The Domain Model pattern organizes code by business concept. Instead of one method that does everything, you have objects that encapsulate rules and relationships. It's more complex, but it scales better when business logic is intricate.

### When Transaction Script Wins

Use Transaction Script when:
- Each endpoint maps to a single database query
- Business rules fit in a few conditional branches
- You have one or two developers working on the code
- The domain is well-understood and unlikely to change

```php
// Transaction Script — works fine for simple cases
readonly class UpdateProfileController
{
    public function __construct(
        private DatabaseConnection $db,
    ) {}

    public function handle(ServerRequest $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $name = $request->getParsedBody()['name'];

        $this->db->execute(
            'UPDATE users SET name = ? WHERE id = ?',
            [$name, $userId],
        );

        return new JsonResponse(['status' => 'ok']);
    }
}
```

This is fine. It's simple. It's testable. Do not add DDD here.

### When Transaction Script Fails

The pain points are consistent across projects. Watch for these signals:

1. **The same conditional appearing in multiple controllers.** If three different endpoints check "is this athlete eligible for this season," that logic is leaking. It belongs in a domain object.

2. **Partial updates across related tables.** A single user action modifies users, teams, subscriptions, and invoices. If any step fails, you need to roll everything back. Transaction Script makes this fragile.

3. **Business rules that interact.** "If the athlete is on a scholarship and the season is summer and the competition is out-of-state, the fee is waived." That's not a query. That's a domain rule.

4. **Testing requires a full database.** If every test needs fixtures with foreign keys cascading across six tables, your design is fighting you.

```php
// Transaction Script — becoming painful
class RegistrationController
{
    public function process(Request $request): Response
    {
        // This check is duplicated in three controllers
        if ($request->get('athlete_type') === 'scholarship'
            && $request->get('season') === 'summer'
            && $request->get('competition_type') === 'out_of_state') {
            // waive fee
        }

        // More business logic inline...
        // More conditionals...
        // Some SQL mixed in...
        // Error handling via try-catch soup...
    }
}
```

When you see this pattern, Transaction Script has hit its limit. The code works. But every new feature makes it worse.

### When Domain Model Wins

A Domain Model pays for itself when:
- Business rules involve multiple entities with non-trivial relationships
- The same rule manifests in multiple workflows
- You need to enforce invariants (things that must always be true)
- The domain experts argue about edge cases

```php
// Domain Model — appropriate for complex rules
final class Athlete
{
    public function __construct(
        private readonly AthleteId $id,
        private readonly AthleteType $type,
        private readonly ScholarshipStatus $scholarship,
    ) {}

    public function isExemptFromFee(Season $season, Competition $competition): bool
    {
        return $this->type === AthleteType::Student
            && $this->scholarship === ScholarshipStatus::Active
            && $season->isSummer()
            && $competition->isOutOfState();
    }
}
```

The rule lives in one place. Every controller, every service, every test calls the same method. Change it once, and the entire system updates.

## Signs You Need DDD

DDD carries real overhead. You need more classes, more interfaces, and more discipline around testing. The payoff is proportional to complexity. Here's how to know if you're ready.

### The 80/20 Rule of Controllers

Track your controller file sizes. If 20% of your controllers contain 80% of your total controller code, those files are candidates for domain modeling. The long files aren't complex because the authors were lazy. They're complex because the domain is complex.

### The Stakeholder Vocabulary Gap

Sit in a requirements meeting. Listen for when stakeholders contradict each other. If the product manager says "a registration is complete when payment is received" and the compliance officer says "a registration is complete when all waivers are signed," you have two different domain models. The word "registration" means different things in different contexts. DDD gives you a framework for modeling both meanings explicitly.

### The "I Don't Know What This Does" Moment

When you open a method and can't describe its purpose in one sentence, the code has lost touch with the domain. DDD forces you to name things after business concepts. An Application Service called `RegisterAthleteForSeason` tells you exactly what it does. A controller method called `process` tells you nothing.

### The Test Cascade

When changing one domain rule breaks tests across five different feature files, your tests are coupled to the wrong abstractions. DDD decouples tests by letting you test domain objects in isolation. You change the rule in one class, update its tests, and the rest of the test suite stays green.

## Incremental Adoption

You don't need a Big Bang rewrite to adopt DDD. In fact, a Big Bang rewrite is the fastest way to fail. DDD adoption works best when you apply it one workflow at a time.

### Step 1: Identify Your Target

Pick one workflow that hurts. Not the most important workflow. Not the CEO's pet feature. The one that generates the most support tickets, the most regressions, the most "can we just add one more thing" conversations.

### Step 2: Write the Safety Net

Before touching any code, write tests that pin the existing behavior. These don't need to be pretty. They need to pass before you start and pass after you finish.

```php
final class RegistrationWorkflowTest extends TestCase
{
    public function test_current_registration_behavior(): void
    {
        // Pin the exact behavior before refactoring
        $response = $this->post('/register', [
            'athlete_id' => 'abc-123',
            'season_id' => 'season-2024',
            'disciplines' => ['trap', 'skeet'],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('registrations', [
            'athlete_id' => 'abc-123',
            'status' => 'pending_payment',
        ]);
    }
}
```

### Step 3: Extract One Concept

Find a domain concept buried in the controller. A status check. A fee calculation. An eligibility rule. Extract it into a Value Object or an Enum.

```php
// Before
if ($registration['status'] === 'confirmed' || $registration['status'] === 'active') {
    // allow something
}

// After
enum RegistrationStatus: string
{
    case Pending = 'pending';
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function isActive(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::Active,
        ], true);
    }
}

if ($registration->status->isActive()) {
    // allow something
}
```

This is a small change. It doesn't require buy-in from the team. It doesn't change the architecture. But it moves the code one step closer to the domain language.

### Step 4: Extract the Use Case

Once you've extracted a few domain concepts, wrap them in an Application Service. The controller becomes thin — it parses the request, calls the service, and returns a response. All business logic lives in the service or the domain objects it coordinates.

### Step 5: Repeat

Next sprint, pick another workflow. Over time, the codebase shifts. The balance tips from procedural controllers toward domain objects. You don't announce "we're adopting DDD." You just keep making one file better at a time.

## Ubiquitous Language in Practice

Ubiquitous Language is the term Eric Evans used for the shared vocabulary between developers and domain experts. It's not about using "business-friendly" names in code. It's about having one set of terms that everyone uses.

### Finding the Language

Listen for nouns that come up repeatedly in conversations with stakeholders. These are your domain concepts. Write them down. Notice when different stakeholders use different words for the same thing, or the same word for different things. Those are bounded context boundaries waiting to be discovered.

### Translating to Code

The language goes into classes, methods, variables, and directories. If stakeholders say "an athlete registers for a season," your code should have `athlete->registerForSeason($season)`. If they say "the system sends a confirmation," your domain event should be `AthleteRegisteredForSeason`.

```php
// Code that speaks the domain language
final readonly class Athlete
{
    /**
     * This method name comes from the business language.
     * Stakeholders say "register for a season" — not "create registration record."
     */
    public function registerForSeason(
        Season $season,
        DisciplineCollection $disciplines,
    ): Registration {
        if (! $this->isEligibleFor($season)) {
            throw new AthleteNotEligible($this->id, $season->id);
        }

        return Registration::provisional(
            athleteId: $this->id,
            seasonId: $season->id,
            disciplines: $disciplines,
        );
    }
}
```

### When Language and Code Diverge

The biggest red flag is when developers and stakeholders use different terms for the same concept. If developers say "the pending queue" and stakeholders say "the onboarding funnel," you have a language disconnect. Over time, this gap widens. Stakeholders can't read the code. Developers misunderstand requirements. The fix is to rename the code to match the stakeholders — not the other way around.

## The Dragon Wrangling Approach

The Dragon Wrangling Pattern isn't about fighting complexity. It's about containing it. Each dragon — each business rule or workflow step — gets its own enclosure. You don't solve every problem in one massive method. You isolate each concern and define clear protocols for interaction.

### Dragon #1: Payment Processing

Payment processing is a classic dragon. It involves multiple gateways, retry logic, reconciliation, refunds, chargebacks, and audit trails. In Transaction Script, all of this lives in the controller. In the Dragon Wrangling approach, payment processing is an Application Service with its own repository, its own error handling, and its own domain objects.

```php
#[AsCommand('payment.process-athlete-pay')]
final readonly class ProcessAthletePayment
{
    public function __construct(
        private RRegistration $registrationRepository,
        private PaymentGateway $gateway,
        private InvoiceGenerator $invoices,
        private ReportError $errorHandler,
    ) {}

    public function handle(PayRegistrationCommand $command): RegistrationResult
    {
        try {
            $registration = $this->registrationRepository
                ->findByAthleteId(new AthleteId($command->athleteId));

            if ($registration->isPaid()) {
                return RegistrationResult::alreadyPaid($registration);
            }

            $payment = $this->gateway->charge(
                amount: $registration->totalFee(),
                token: $command->paymentToken,
            );

            $registration->markAsPaid(
                transactionId: $payment->transactionId,
                paidAt: new \DateTimeImmutable(),
            );

            $this->registrationRepository->save($registration);
            $this->invoices->generateFor($registration);

            return RegistrationResult::success($registration);
        } catch (PaymentDeclined $e) {
            return $this->errorHandler->handlePaymentDeclined($e);
        } catch (GatewayTimeout $e) {
            return $this->errorHandler->handleRetryableFailure($e);
        }
    }
}
```

### Dragon #2: Eligibility Checks

Eligibility rules multiply fast. Age limits, residency requirements, skill divisions, team affiliations. Each rule is a simple check. Together, they form a combinatorial explosion. The Dragon Wrangling approach isolates each rule into its own specification:

```php
interface EligibilitySpecification
{
    public function isSatisfiedBy(Athlete $athlete, Season $season): bool;
}

final readonly class AgeEligibility implements EligibilitySpecification
{
    public function __construct(
        private int $minimumAge,
        private int $maximumAge,
    ) {}

    public function isSatisfiedBy(Athlete $athlete, Season $season): bool
    {
        $age = $athlete->dateOfBirth->ageAt($season->startDate);
        return $age >= $this->minimumAge && $age <= $this->maximumAge;
    }
}

final readonly class AndSpecification implements EligibilitySpecification
{
    /** @param EligibilitySpecification[] $specifications */
    public function __construct(
        private array $specifications,
    ) {}

    public function isSatisfiedBy(Athlete $athlete, Season $season): bool
    {
        foreach ($this->specifications as $spec) {
            if (! $spec->isSatisfiedBy($athlete, $season)) {
                return false;
            }
        }
        return true;
    }
}

// Usage
$eligibility = new AndSpecification([
    new AgeEligibility(12, 18),
    new ResidencyRequirement('Minnesota'),
    new DivisionEligibility(Division::Varsity),
]);

if ($eligibility->isSatisfiedBy($athlete, $season)) {
    $athlete->registerForSeason($season, $disciplines);
}
```

Each specification is testable in isolation. Add a new rule by writing a new class. No existing code changes.

## Real-World Use Cases

### E-Commerce: Order Lifecycle

An e-commerce order isn't a single database row. It's a state machine. An order is created, then it can be paid, shipped, partially shipped, cancelled, refunded, or returned. Each transition has its own rules. You can't ship an unpaid order. You can't refund more than the customer paid. You can't cancel after shipment without initiating a return.

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case PartiallyShipped = 'partially_shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [
                self::Paid, self::Cancelled,
            ], true),
            self::Paid => in_array($target, [
                self::Shipped, self::PartiallyShipped, self::Cancelled,
            ], true),
            self::Shipped => in_array($target, [
                self::Delivered, self::Refunded,
            ], true),
            // ...
        };
    }
}
```

The state machine is explicit. You can read it, test it, and reason about it. No hidden conditions in a service layer.

### SaaS Billing: Subscription Management

Billing is where domain models shine. Subscriptions have plans, trial periods, discounts, proration, upgrades, downgrades, cancellations, and reactivations. The rules interact. A downgrade that takes effect next billing cycle. A coupon that doesn't stack with a promotional rate. A trial that converts to a paid plan automatically unless cancelled.

```php
final readonly class Subscription
{
    public function __construct(
        public SubscriptionId $id,
        public CustomerId $customerId,
        public Plan $plan,
        public SubscriptionStatus $status,
        public BillingPeriod $currentPeriod,
        public ?Coupon $coupon,
    ) {}

    public function downgradeTo(Plan $newPlan, BillingSchedule $schedule): self
    {
        if ($newPlan->monthlyPrice() >= $this->plan->monthlyPrice()) {
            throw new CannotDowngradeToMoreExpensivePlan($this->id);
        }

        $effectiveDate = match ($schedule) {
            BillingSchedule::Immediate => new \DateTimeImmutable('now'),
            BillingSchedule::NextBilling => $this->currentPeriod->endDate,
        };

        return new self(
            id: $this->id,
            customerId: $this->customerId,
            plan: $newPlan,
            status: $this->status,
            currentPeriod: $this->currentPeriod->withEndDate($effectiveDate),
            coupon: $this->coupon,
        );
    }
}
```

The `downgradeTo` method encapsulates the rules. It rejects upgrades disguised as downgrades. It calculates the effective date based on business policy. It returns a new instance, leaving the original unchanged — events can be dispatched based on the transition.

### Multi-Step Workflows

Insurance applications, mortgage approvals, government permits — these are multi-step workflows where partial data is valid, validation rules change by step, and the workflow itself depends on previous answers. DDD handles this naturally: each step is an Application Service, the aggregate tracks progress, and domain events trigger the next step.

```php
#[AsCommand('application.submit-step')]
final readonly class SubmitApplicationStep
{
    public function __construct(
        private RApplication $repository,
        private ApplicationWorkflow $workflow,
        private DomainEventDispatcher $events,
    ) {}

    public function handle(SubmitStepCommand $command): Result
    {
        $application = $this->repository->find(new ApplicationId($command->applicationId));

        $validation = $this->workflow->validateStep(
            step: $command->step,
            data: $command->data,
            currentState: $application->progress,
        );

        if ($validation->hasErrors()) {
            return Result::invalid($validation->errors);
        }

        $application = $application->withCompletedStep(
            step: $command->step,
            data: $command->data,
        );

        $this->repository->save($application);

        foreach ($application->releaseEvents() as $event) {
            $this->events->dispatch($event);
        }

        return Result::success($application->progress);
    }
}
```

## Best Practices

**Test-first before you refactor.** Write tests that pin current behavior before you change a single line. These tests are your refactoring safety net. Without them, you're not refactoring — you're rewriting. The discipline of writing the test first forces you to understand what the code actually does, not what you think it does.

**Use PHP 8.1 features for domain primitives.** `readonly` classes make Value Objects immutable by default. Enums replace stringly-typed constants. Intersection types express constraints at the type level. Named arguments make constructor calls self-documenting.

```php
// PHP 8.1 Value Object with readonly
final readonly class EmailAddress
{
    public function __construct(
        public string $value,
    ) {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailAddress($value);
        }
    }
}

// PHP 8.1 Enum for domain concepts
enum MembershipTier: string
{
    case Basic = 'basic';
    case Premium = 'premium';
    case Enterprise = 'enterprise';

    public function monthlyPrice(): int
    {
        return match ($this) {
            self::Basic => 999,       // $9.99
            self::Premium => 2999,    // $29.99
            self::Enterprise => 9999, // $99.99
        };
    }

    public function maxTeamMembers(): int
    {
        return match ($this) {
            self::Basic => 5,
            self::Premium => 25,
            self::Enterprise => 999,
        };
    }
}
```

**Let the domain language drive naming.** If stakeholders say "disqualify," don't name the method `setStatusToDisqualified`. Name it `disqualify`. The code reads like the business speaks. New developers can follow requirements meetings using the same vocabulary they see in the codebase.

**Small iterations over big redesigns.** Extract one Value Object this week. One Application Service next week. One bounded context next month. Each change is small enough to review, test, and deploy. Each change makes the codebase slightly more expressive.

## Common Mistakes

**DDD for CRUD apps.** If your application is 90% create-read-update-delete with simple validation, DDD adds accidental complexity. The Transaction Script pattern is better. You'll know DDD fits when your Transaction Script tests start needing five fixtures to test one endpoint.

**Over-engineering from day one.** Don't design aggregates, repositories, and domain events before you understand the domain. Let the patterns emerge from the complexity. Start with Transaction Script. Extract domain objects when the pain justifies the abstraction.

**Ignoring bounded contexts.** A `User` in the authentication context is not the same as a `User` in the billing context. They share a login but have different responsibilities, different invariants, and different lifecycles. Model them separately. The duplication is cheaper than the coupling.

**Making everything an Entity.** Not every domain concept needs an identity. A `Money` object with a `Currency` and an `Amount` is a Value Object — two `Money` objects with the same values are interchangeable. A `Payment` is an Entity — each payment has a unique identity and lifecycle. Confusing the two leads to anemic models and unnecessary complexity.

## Frequently Asked Questions

**Q: How do I convince my team to try DDD?**

Don't pitch DDD. Pitch making one specific file better. Pick the controller everyone dreads touching, extract one Value Object, and show the team how the tests become clearer. Let the pattern sell itself.

**Q: What's the smallest DDD change I can make today?**

Replace a string constant with a PHP 8.1 Enum. Every place that checks `$status === 'active'` becomes `$status === RegistrationStatus::Active`. It's type-safe, searchable, and introduces the concept of domain language to the codebase.

**Q: Do I need Doctrine or Eloquent for DDD?**

No. Repositories can use raw PDO, the DBAL query builder, or Eloquent internally. The interface is what matters — it should speak the domain language, not the persistence language. Doctrine's Entity Manager is useful for complex aggregates but is not required.

**Q: When should I use Domain Events?**

Use Domain Events when multiple parts of the system need to react to a domain concept. "A registration was completed" might trigger an email, an invoice, a Slack notification, and a CRM update. The event decouples the reaction from the action. Don't use events for internal method calls — use them for cross-boundary communication.

**Q: How do Aggregates relate to database transactions?**

An Aggregate is a consistency boundary. One transaction should modify one aggregate. If you need to modify multiple aggregates atomically, reconsider your aggregate design — or use eventual consistency with Domain Events. This is the hardest DDD concept to internalize, and the one that prevents the most bugs.

**Q: What if my team doesn't care about architecture?**

Code for your future self. You will be the one debugging the 1,500-line controller at 2 AM. You will be the one explaining to a new hire why "just add one more if statement" broke three unrelated features. The patterns are not for the team. They're for the code, and the code will outlast every team member.

**Q: Does DDD work in Laravel?**

Yes. Laravel's container, Eloquent, queues, and event system map naturally to DDD concepts. Use Eloquent models inside repository implementations. Use the container for dependency injection. Use queued listeners for Domain Event subscribers. The framework supports the pattern — you just need the discipline to draw the boundaries.

## The Takeaway

Pattern recognition is the skill that separates experienced developers from everyone else. It's not about knowing more patterns — it's about knowing when a pattern fits. DDD is powerful precisely because it gives you a vocabulary and a framework for recognizing domain complexity before it becomes painful.

You don't need to adopt DDD everywhere. You need to recognize the moment when a simple controller starts accumulating the same three if-conditions across five different methods. You need to feel when your tests require more setup than assertions. You need to hear when stakeholders use words that don't appear anywhere in your codebase.

When you know the pattern, you don't force it. You let it emerge. You start with one Value Object. One Enum. One Application Service. You let the code tell you where the boundaries are, and you follow.

The dragons are waiting. But now you know how to spot them.
