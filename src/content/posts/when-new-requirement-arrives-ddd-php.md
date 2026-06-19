---
title: "When the New Requirement Arrives - Handling Change With DDD in PHP"
description: "Learn how to handle changing requirements using Domain-Driven Design principles in PHP. Practical strategies for extending your domain model without breaking existing code."
pubDate: "2022-04-24 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "Domain-Driven Design", "PHP", "SOLID", "Refactoring", "Architecture", "Change Management"]
selected: false
---

The email lands at 4:17 PM on a Tuesday. Subject line: "Quick question about the registration flow." You open it. Three paragraphs in, you realize it's not a question. It's a new requirement. The business wants to support team captains registering on behalf of their athletes. Parent accounts. Different pricing tiers based on registration date. Oh, and they need it before the season opens in three weeks.

Your stomach tightens. Not because the requirement is unreasonable — it makes perfect business sense. But because you know what happens next. You'll open the controller, find the 900-line `process()` method, and start adding conditional branches. The code will work. The tests will pass. And six months from now, someone will be cursing the "temporary" solution you're about to write.

This is the moment that separates sustainable architecture from technical debt. How you handle the new requirement determines whether your codebase gets stronger or weaker over time.

Domain-Driven Design gives you a framework for answering the hard questions: Do I modify this existing class or create a new one? Does this belong in the same aggregate? Should I extend or wrap? How do I communicate the cost back to the business?

Let's walk through what happens when the new requirement arrives.

## The Reality of Changing Requirements

Requirements change because the world changes. Your business discovers a new revenue stream. A competitor forces a feature pivot. A regulation drops that reshapes your compliance model. The worst codebases I've seen weren't written by bad developers. They were written by good developers who believed the requirements wouldn't change.

The first step in handling change is accepting it will happen. Every line of code you write today is a candidate for modification tomorrow. The question is not whether the requirements will shift. The question is whether your codebase can shift with them without breaking.

```php
// The pattern that kills maintainability:
// Adding flags and conditionals instead of expressing intent
class RegistrationService
{
    public function register(
        array $athleteData,
        bool $isTeamCaptain = false,
        bool $isParentRegistration = false,
        ?string $teamCaptainId = null,
        ?string $promoCode = null,
    ): Registration {
        if ($isTeamCaptain && $isParentRegistration) {
            throw new \InvalidArgumentException(
                'Cannot be both team captain and parent'
            );
        }

        // Seven more conditionals...
        // Each one added when a "simple" requirement arrived
    }
}
```

This class started clean. Then came team registration. Then parent accounts. Then promo codes. Then early-bird pricing. Each addition was reasonable. Each one added a parameter. Now the method signature has seven optional parameters and the internal logic is a maze of boolean checks.

The solution isn't to predict every future requirement. The solution is to structure your code so that new requirements slot into new classes, not new branches in existing ones.

## Ubiquitous Language as a Change Detection Tool

Eric Evans introduced Ubiquitous Language as the shared vocabulary between developers and domain experts. But it's also a powerful tool for detecting when a new requirement actually changes your domain model versus just extending it.

When the requirement for team-captain registration arrived, here's what happened in the requirements meeting:

- Business: "The team captain registers for the whole team."
- Developer: "So a team captain is creating multiple registrations?"
- Business: "No, they're creating one registration for the team."
- Developer: "And who pays?"
- Business: "The team captain pays for the team."

The word "registration" meant different things to different people. The business saw a team registration as one unit. The developers saw it as multiple individual registrations. This language gap is the first sign that you're dealing with a new domain concept, not just a variation of an existing one.

The fix was naming the new concept:

```php
// The new concept, not a variation
final readonly class TeamRegistration
{
    public function __construct(
        public TeamRegistrationId $id,
        public TeamId $teamId,
        public CaptainId $captainId,
        public SeasonId $seasonId,
        public RegistrationDate $registeredAt,
    ) {}

    public function addAthlete(AthleteId $athleteId): void
    {
        // Rules specific to team registrations
    }
}
```

Once the concept had a name, the code followed. No boolean flags. No conditional branches in the existing registration service. A new class for a new concept.

When the next requirement arrives, start with language. Ask your stakeholders to describe the feature without using technical terms. Listen for nouns and verbs that don't exist in your codebase yet. Those are your new domain objects.

## Bounded Context Mapping When Requirements Cross Boundaries

Not every requirement stays inside one bounded context. A new invoicing requirement might touch billing, subscriptions, and customer support. A compliance change might span authentication, data storage, and reporting.

When requirements cross boundaries, you need a context map. This is DDD's strategic design tool for understanding how different bounded contexts relate to each other.

Here's a practical example. The business wants to implement "soft deletes" for athlete accounts. The athlete entity lives in the Registration context, but the requirement touches Payments (can't delete an athlete with outstanding balance), Scoring (historical scores should be preserved but anonymized), and Notifications (confirm the deletion via email).

Without a context map, you'd scatter the logic across all three contexts. With a context map, you define the relationship:

```php
// The Registration context owns the athlete lifecycle
final class Athlete
{
    public function __construct(
        private AthleteId $id,
        private AthleteStatus $status,
        private PersonalInfo $info,
    ) {}

    public function deactivate(): void
    {
        if ($this->status !== AthleteStatus::Active) {
            throw new AthleteAlreadyInactive($this->id);
        }

        $this->status = AthleteStatus::PendingDeactivation;
    }

    public function confirmDeactivation(\DateTimeImmutable $when): void
    {
        $this->status = AthleteStatus::Deactivated;
        $this->info = $this->info->anonymize();
        // Domain event dispatched: AthleteDeactivated
    }
}

// The Billing context subscribes to AthleteDeactivated
#[AsEventListener]
final readonly class CloseOutstandingBalance
{
    public function __construct(
        private BillingService $billing,
    ) {}

    public function __invoke(AthleteDeactivated $event): void
    {
        $balance = $this->billing->getOutstandingBalance($event->athleteId);

        if ($balance->isPositive()) {
            $this->billing->issueCredit($event->athleteId, $balance);
        }

        $this->billing->closeAccount($event->athleteId);
    }
}

// The Scoring context subscribes to AthleteDeactivated
#[AsEventListener]
final readonly class AnonymizeHistoricalScores
{
    public function __construct(
        private ScoreRepository $scores,
    ) {}

    public function __invoke(AthleteDeactivated $event): void
    {
        $this->scores->anonymizeForAthlete($event->athleteId);
    }
}
```

Each context handles the requirement within its own boundary. The Registration context owns the deactivation workflow and emits a domain event. The Billing and Scoring contexts react to that event. No context reaches into another context's database. No context knows about the other's internal model.

When you map your contexts early, you know exactly where a new requirement lands. You can estimate the work across teams. And you avoid the coupling that makes cross-cutting changes so painful.

## Writing Tests First for New Requirements

The new requirement arrives. You've clarified the language. You've mapped the contexts. Now it's time to code.

The single best practice for handling new requirements is writing the test first. Not because TDD is an ideology, but because the test forces you to answer the hard questions before you commit to an implementation.

Start with a BDD-style scenario:

```gherkin
Feature: Team Captain Registration
  In order to simplify team enrollment
  As a team captain
  I want to register all team members in one transaction

  Scenario: Captain registers full team
    Given a season "Spring 2024"
    And a team "Riverside Sharpshooters"
    And a captain "Alice" authenticated
    When Alice registers the team for the season
    Then all 5 team members should be enrolled
    And the total fee of $250 should be charged to Alice
    And each member should receive a confirmation email

  Scenario: Captain registers with incomplete team
    Given a season "Spring 2024"
    And a team "Riverside Sharpshooters" with 3 members
    And a captain "Alice" authenticated
    When Alice registers the team for the season
    Then the registration should be rejected
    And Alice should see "Team must have at least 4 members"
```

Write these scenarios before you write a single line of domain code. Show them to your stakeholders. Ask them: "Is this right?" The scenarios become a contract. When they pass, the requirement is implemented.

The test shapes the design:

```php
final class TeamRegistrationTest extends TestCase
{
    /** @test */
    public function captain_registers_full_team(): void
    {
        $season = new Season(new SeasonId('S2024-1'), 'Spring 2024');
        $team = new Team(
            new TeamId('T-123'),
            'Riverside Sharpshooters',
            new AthleteCount(5),
        );
        $captain = new Captain(
            new CaptainId('C-001'),
            'Alice',
        );
        $registration = new TeamRegistration(
            new TeamRegistrationId('TR-1'),
            $team,
            $captain,
            $season,
        );

        $result = $registration->enroll();

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(5, $result->enrolledMembers()->count());
        $this->assertSame(25000, $result->totalFee()->toCents());
    }

    /** @test */
    public function rejects_registration_when_team_is_incomplete(): void
    {
        $season = new Season(new SeasonId('S2024-1'), 'Spring 2024');
        $incompleteTeam = new Team(
            new TeamId('T-456'),
            'Small Team',
            new AthleteCount(3),
        );
        $captain = new Captain(
            new CaptainId('C-002'),
            'Bob',
        );

        $this->expectException(TeamBelowMinimumSize::class);

        $registration = new TeamRegistration(
            new TeamRegistrationId('TR-2'),
            $incompleteTeam,
            $captain,
            $season,
        );
        $registration->enroll();
    }
}
```

Writing the test first changes how you think about the requirement. You're not asking "how do I fit this into the existing code?" You're asking "what is the cleanest interface for this new concept?" The existing code either accommodates it or doesn't — and if it doesn't, you extend, not modify.

## Extending vs Modifying Existing Domain Models

The Open/Closed Principle states that software entities should be open for extension but closed for modification. This is the guiding principle for handling new requirements.

When the new requirement arrives, ask: does this change the behavior of an existing concept, or is this a new concept?

Modify when:

- The business rule itself changes (e.g., "registration fee is $50" becomes "registration fee is $75").
- An invariant becomes stricter (e.g., minimum age from 12 to 14).
- A bug fix that aligns the code with existing rules.

Extend when:

- A new behavior is added alongside existing behavior (e.g., team registration alongside individual registration).
- A new variation of a rule is introduced (e.g., early-bird pricing alongside standard pricing).
- A new workflow is added that shares some structure but has different rules.

Here's the extension pattern using composition:

```php
// Existing: individual registration
final class IndividualRegistration
{
    public function __construct(
        private AthleteId $athleteId,
        private SeasonId $seasonId,
        private Money $fee,
        private RegistrationStatus $status,
    ) {}

    public function confirm(): void
    {
        $this->status = $this->status->transitionTo(RegistrationStatus::Confirmed);
    }

    public function fee(): Money
    {
        return $this->fee;
    }
}

// New: team registration extends behavior via composition
// No modification to IndividualRegistration
final class TeamRegistration
{
    /** @var IndividualRegistration[] */
    private array $memberRegistrations;

    public function __construct(
        private TeamId $teamId,
        private CaptainId $captainId,
        private SeasonId $seasonId,
        private Money $teamFee,
        private TeamRegistrationStatus $status,
    ) {}

    public function confirm(): void
    {
        $this->status = $this->status->transitionTo(TeamRegistrationStatus::Confirmed);
    }

    public function confirmMember(AthleteId $athleteId): void
    {
        // Team registration may confirm members individually
        // without confirming the whole team
    }

    public function fee(): Money
    {
        return $this->teamFee;
    }
}
```

The existing `IndividualRegistration` stays untouched. `TeamRegistration` is a new class that may internally use `IndividualRegistration` or may manage its own state. The key is that existing code doesn't change, so existing tests don't break.

## When to Create New Aggregates vs Modify Existing Ones

Aggregates are consistency boundaries. The rule is: one transaction modifies one aggregate. When a new requirement arrives, the aggregate question is critical.

Create a new aggregate when:

- The new concept has its own lifecycle independent of existing aggregates
- The consistency requirements differ from existing aggregates
- The new concept is used in different workflows than existing aggregates
- The data changes at a different frequency or under different rules

Modify an existing aggregate when:

- The new requirement changes an invariant the aggregate already enforces
- The new data is meaningless without the aggregate root
- The consistency boundary genuinely includes the new behavior

```php
// Existing aggregate: Athlete
final class Athlete
{
    public function __construct(
        private AthleteId $id,
        private PersonName $name,
        private EmailAddress $email,
        private AthleteStatus $status,
    ) {}

    public function updateProfile(PersonName $name, EmailAddress $email): void
    {
        $this->name = $name;
        $this->email = $email;
    }
}

// New requirement: athletes can have multiple guardian contacts
// Decision: create a new aggregate, not modify Athlete
final class GuardianContacts
{
    public function __construct(
        private AthleteId $athleteId,
        private GuardianCollection $guardians,
    ) {}

    public function addGuardian(PersonName $name, PhoneNumber $phone, EmailAddress $email): void
    {
        if ($this->guardians->count() >= 5) {
            throw new TooManyGuardians($this->athleteId);
        }

        $this->guardians = $this->guardians->add(
            new Guardian($name, $phone, $email),
        );
    }

    public function removeGuardian(GuardianId $id): void
    {
        if ($this->guardians->count() <= 1) {
            throw new MinimumOneGuardianRequired($this->athleteId);
        }

        $this->guardians = $this->guardians->remove($id);
    }
}
```

Why a new aggregate? Because `GuardianContacts` has its own lifecycle. Guardians come and go independently of the athlete's core profile. The invariants are different — you must have at least one guardian, but no more than five. The athlete profile doesn't need to load guardian data every time you render a dashboard.

If you had added guardians to the `Athlete` aggregate, every athlete query would carry guardian data. Every transaction that touched the athlete would need to load guardians. The consistency boundary would grow until it became a performance problem.

The rule of thumb: if the new requirement changes data at a different rate or under different rules, it's probably a new aggregate.

## Communicating With Stakeholders About Complexity

The hardest part of handling new requirements isn't technical. It's communication. Stakeholders see a "small change." Developers see a ripple effect across six bounded contexts.

Here's how to close that gap.

**Show the current model.** Draw the aggregates, the bounded contexts, and the workflows. Point to the part the new requirement touches. When stakeholders see that their "small change" crosses three context boundaries, they understand the cost.

**Present options, not refusals.** Instead of saying "we can't do that," say "we can do that in two ways. Option A is faster now but will slow down future changes. Option B takes longer now but keeps things clean." Let the business decide based on cost, not on your frustration.

**Quote the cost of options in business terms.** Don't say "Option B requires 40 story points." Say "Option B takes two weeks but means the next three features in this area will be faster. Option A takes three days but adds a month of cleanup later."

**Use scenarios for validation.** Write the Gherkin scenarios before you estimate. Walk through them with stakeholders. This is where mismatches surface. The stakeholder says "the team captain pays." You read the scenario: "the total fee of $250 should be charged to Alice." They nod. But then they add: "unless the team has a sponsorship." The scenario catches this before you've written any code.

**Build a vocabulary for technical debt.** The term "technical debt" is vague. Instead, say "this approach means that adding parent accounts next year will require rewriting the registration controller." Specific future costs are easier for stakeholders to evaluate than abstract warnings.

```php
// The cost of the "fast" option is explicit
final class RegistrationController
{
    // TODO: Remove this when team registration gets its own service
    // Expected: before Spring 2025 season
    // Cost of deferring: every team-related feature will add more conditionals here
    public function handleTeamRegistration(array $data): Response
    {
        // Inline logic that should live in a dedicated service
    }
}
```

## Practical Strategies for Incremental Change

You don't need to refactor the entire codebase every time a requirement arrives. You need targeted strategies that keep the system working while you extend it.

**Strangler Fig Pattern.** Route new workflows through new code paths. Leave old workflows untouched. When the old path is no longer used, remove it.

```php
// Old endpoint for individual registration
$router->post('/register/individual', IndividualRegistrationController::class);

// New endpoint for team registration — completely separate path
$router->post('/register/team', TeamRegistrationController::class);

// Both coexist. Old code never changes.
```

**Branch by Abstraction.** Introduce an interface that both old and new code implement. The old implementation delegates to existing logic. The new implementation uses your domain model. Swap the binding when you're confident.

```php
interface RegistrationProcessor
{
    public function process(RegistrationData $data): RegistrationResult;
}

// Old implementation — delegates to existing controller logic
final readonly class LegacyRegistrationProcessor implements RegistrationProcessor
{
    public function process(RegistrationData $data): RegistrationResult
    {
        // Call the existing procedural code
        // No changes to legacy codebase
    }
}

// New implementation — uses domain model
final readonly class DomainRegistrationProcessor implements RegistrationProcessor
{
    public function __construct(
        private RegistrationService $service,
    ) {}

    public function process(RegistrationData $data): RegistrationResult
    {
        return $this->service->register($data->toCommand());
    }
}
```

**Use PHP 8.1 Enums to encapsulate variation.** When a requirement adds a new variation of an existing concept, an Enum absorbs the variation without changing the class structure.

```php
enum RegistrationType: string
{
    case Individual = 'individual';
    case Team = 'team';
    case Parent = 'parent';

    public function requiresApproval(): bool
    {
        return match ($this) {
            self::Individual => false,
            self::Team => true,
            self::Parent => true,
        };
    }

    public function feeStrategy(): FeeStrategy
    {
        return match ($this) {
            self::Individual => new StandardFee(),
            self::Team => new TeamFee(),
            self::Parent => new ParentFee(),
        };
    }
}
```

The Enum captures the variation points explicitly. When a new `RegistrationType` appears, you add a case. The compiler forces you to handle it in every `match`. No hidden conditionals, no forgotten branches.

## What Success Looks Like

A year from now, the business will have sent you twenty more "quick questions." Some will be genuinely simple — a configuration change, a new report, a field rename. Others will introduce new domain concepts.

If you handled the first requirement well, the codebase handles each subsequent one with less friction. New classes, not new conditionals. New aggregates, not bloated existing ones. New endpoints, not modified controllers.

The 900-line registration controller never grows. Because when the team-captain requirement arrived, you didn't add to it. You wrote a test, named the new concept, created a new aggregate, and deployed it through a new endpoint. The old code stayed closed. The new code stayed open.

That's the Open/Closed Principle in practice. Not as an abstract ideal, but as a concrete decision you make every time the email lands at 4:17 PM.

## Frequently Asked Questions

**Q: How do I convince stakeholders to let me refactor before adding the feature?**

Don't ask for refactoring time. Ask to implement the feature in a way that doesn't make the next one harder. Show them two estimates — one with a quick hack, one with a clean extension. Connect the cost to something they care about: speed of future features, risk of bugs, onboarding time for new developers.

**Q: What if the new requirement is genuinely urgent?**

Ship the simplest thing that works. But mark it. Add a TODO with a date. Schedule the cleanup in the next sprint. The danger isn't the urgent hack — it's the urgent hack that becomes permanent. If you know you'll clean it up, you're making a conscious trade-off. If you pretend it won't need cleanup, you're accruing debt.

**Q: When should I modify an existing aggregate vs. create a new one?**

Modify when the new data shares the same invariants and lifecycle as the existing aggregate. Create a new one when the new data has its own rules, its own rate of change, or its own consistency requirements. The wrong choice is almost always merging everything into one aggregate because "it's simpler."

**Q: How do I test that existing behavior isn't broken by a new requirement?**

Write characterization tests before you add the new code. Pin the behavior with assertions. Then add the new feature. Run both the old and new tests. If the old tests pass and the new ones pass, you haven't broken anything. This is non-negotiable — without the safety net, you're not extending, you're risking.

**Q: Should I use inheritance or composition for extending domain models?**

Composition. Always. Inheritance couples the new class to the parent's entire interface. Composition lets you pick exactly what you reuse. `TeamRegistration` containing `IndividualRegistration` instances is more flexible than `TeamRegistration extends IndividualRegistration`.

**Q: What role do PHP 8.1 features play in handling change?**

`readonly` classes make Value Objects immutable — you can't accidentally mutate shared state. Enums replace stringly-typed constants with compiler-checked cases. Named arguments make constructor calls self-documenting and reduce positional errors when adding new parameters. Intersection types let you express constraints like `(Captain & Parent) $user` at the type level. These features make your code more resilient to change because the compiler catches more mistakes.

**Q: How do I avoid analysis paralysis when a requirement is ambiguous?**

Write the test first. The test forces you to define the behavior precisely. If you can't write the test, you don't understand the requirement well enough to code it. Use the ambiguity as a signal to go back to the stakeholder for clarification. The test is your tool for translating vague requirements into executable specifications.

**Q: What if the team doesn't use DDD?**

You don't need a team-wide DDD adoption to apply these patterns. Extract one concept into a Value Object. Write one BDD scenario for the next feature. Create one new aggregate for a distinct workflow. The patterns work at any scale. You don't need everyone on board — you need one codebase that gets a little better with each requirement.
