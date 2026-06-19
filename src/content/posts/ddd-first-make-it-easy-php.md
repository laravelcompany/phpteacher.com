---
title: "First Make It Easy: DDD Alley PHP Architecture"
description: "Learn Edward Barnard's approach to DDD in PHP: make the easy path the right path. Simplify domain logic, reduce complexity, and build maintainable applications."
pubDate: "2023-05-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "Domain-Driven Design", "PHP", "Architecture", "Edward Barnard", "DDD Alley", "Software Design", "Simplification"]
---

Domain-Driven Design often gets a reputation for complexity. Bounded contexts, aggregates, domain events, value objects, repositories, and application services can overwhelm teams new to the methodology. Edward Barnard's DDD Alley column offers a refreshing counterpoint: start by making the easy path the right path.

The principle is simple. When you design your domain model, the most natural way to write code should also be the most correct way. If developers can accidentally write incorrect code, the design is wrong. If the simplest implementation is also the most robust, the design is right.

## What You'll Learn

- The "First Make it Easy" principle for DDD design
- Reducing accidental complexity in domain models
- Building domain services that guide correct usage
- Simplifying aggregate boundaries and constructor signatures
- Grouping related dependencies into cohesive abstractions
- Making illegal states unrepresentable in code
- Practical patterns for cleaner PHP domain logic
- Real-world examples of easy-to-use domain APIs
- Testing strategies for simplified domain models

## The Principle: Make Correctness Easy

Traditional software design focuses on preventing incorrect usage through barriers and checks. DDD Alley's approach flips this: design your domain so that the easiest thing to do is the correct thing.

```php
<?php

// Hard design: easy to misuse
class Order
{
    public string $status; // Can set any string
    public ?DateTimeImmutable $shippedAt; // Can forget to set
}

// Easy design: hard to misuse
class Order
{
    private OrderStatus $status;

    public function ship(DateTimeImmutable $when): void
    {
        $this->status = OrderStatus::Shipped;
        $this->shippedAt = $when;
    }
}
```

The first version makes it easy to set an invalid status or forget the shipping date. The second guides the developer toward correct usage through method encapsulation.

## Capturing Workflow State

Complex workflows benefit from explicit state management. Instead of scattered conditionals, model the workflow as a first-class concept.

```php
<?php

declare(strict_types=1);

final class RegistrationWorkflow
{
    private function __construct(
        private string $flowId,
        private RegistrationState $state,
    ) {}

    public static function captureFlow(): string
    {
        $flow = new self(
            flowId: bin2hex(random_bytes(16)),
            state: RegistrationState::Initial,
        );
        // Store in session or persistence
        $_SESSION['registration_flow'] = $flow;
        return $flow->flowId;
    }

    public function proceedToPayment(array $postData): void
    {
        $this->state = $this->state->transitionTo(RegistrationState::Payment);
        $_SESSION['stripe_post'] = $postData;
    }

    public function complete(): void
    {
        $this->state = $this->state->transitionTo(RegistrationState::Completed);
        unset($_SESSION['stripe_post']);
    }

    public function isInState(RegistrationState $state): bool
    {
        return $this->state === $state;
    }
}
```

The workflow class makes the correct sequence obvious. You cannot complete registration without going through the payment step.

## Reducing Constructor Complexity

A common DDD pitfall is constructors with many dependencies. This makes instantiation hard and testing painful.

```php
<?php

// Before: hard to construct and easy to get wrong
class RegistrationService
{
    public function __construct(
        private CaptureFlow $captureFlow,
        private CaptureMode $captureMode,
        private CaptureApiKey $captureApiKey,
        private RestorePost $restorePost,
        private RestoreStripeSession $restoreStripeSession,
        private RetrieveSeasonStripeSession $retrieveSeasonStripeSession,
        private RetrieveLeagueStripeSession $retrieveLeagueStripeSession,
        private RetrieveMshslStripeSession $retrieveMshslStripeSession,
        private RetrieveNationalStripeSession $retrieveNationalStripeSession,
        private ConfirmSeason $confirmSeason,
        private ConfirmLeague $confirmLeague,
        private ConfirmMshsl $confirmMshsl,
        private ConfirmNational $confirmNational,
        private CheckoutSessionSeason $checkoutSessionSeason,
        private CheckoutSessionLeague $checkoutSessionLeague,
        private CheckoutSessionMshsl $checkoutSessionMshsl,
        private CheckoutSessionNational $checkoutSessionNational,
    ) {}
}
```

This is the opposite of "making it easy." The fix is to group related dependencies into meaningful abstractions.

```php
<?php

declare(strict_types=1);

final class SessionManager
{
    public function __construct(
        private CaptureFlow $captureFlow,
        private CaptureMode $captureMode,
        private CaptureApiKey $captureApiKey,
        private RestorePost $restorePost,
        private RestoreStripeSession $restoreStripeSession,
    ) {}
}

final class StripeSessionFactory
{
    public function __construct(
        private RetrieveSeasonStripeSession $retrieveSeasonStripeSession,
        private RetrieveLeagueStripeSession $retrieveLeagueStripeSession,
        private RetrieveMshslStripeSession $retrieveMshslStripeSession,
        private RetrieveNationalStripeSession $retrieveNationalStripeSession,
    ) {}
}

final class ConfirmationService
{
    public function __construct(
        private ConfirmSeason $confirmSeason,
        private ConfirmLeague $confirmLeague,
        private ConfirmMshsl $confirmMshsl,
        private ConfirmNational $confirmNational,
        private CheckoutSessionSeason $checkoutSessionSeason,
        private CheckoutSessionLeague $checkoutSessionLeague,
        private CheckoutSessionMshsl $checkoutSessionMshsl,
        private CheckoutSessionNational $checkoutSessionNational,
    ) {}
}

// Now RegistrationService is simple
final class RegistrationService
{
    public function __construct(
        private SessionManager $sessionManager,
        private StripeSessionFactory $stripeSessionFactory,
        private ConfirmationService $confirmationService,
    ) {}
}
```

Grouping by responsibility reduces cognitive load and makes the class easy to construct correctly.

## Domain Services That Guide Usage

A domain service should make it obvious what methods to call and in what order.

```php
<?php

declare(strict_types=1);

final class PaymentProcessor
{
    public function __construct(
        private StripeClient $stripe,
        private RegistrationRepository $registrations,
    ) {}

    public function processPayment(
        RegistrationWorkflow $workflow,
        string $paymentMethodId,
    ): PaymentResult {
        $mode = $workflow->getCurrentMode();
        $session = $this->stripe->createCheckoutSession(
            mode: $mode,
            paymentMethod: $paymentMethodId,
        );

        return PaymentResult::pending($session->id);
    }

    public function confirmPayment(string $sessionId): ConfirmedPayment
    {
        $session = $this->stripe->retrieveSession($sessionId);
        $payment = ConfirmedPayment::fromStripeSession($session);
        return $payment;
    }
}
```

The service exposes exactly two methods. You call `processPayment` first, then `confirmPayment` when the webhook arrives. There is no ambiguity about the workflow.

## Aggregate Simplification

Aggregates protect consistency boundaries. A well-designed aggregate makes invariant protection feel natural.

```php
<?php

declare(strict_types=1);

final class Appointment
{
    private \DateTimeImmutable $appointmentDateTime;
    private ?\DateTimeImmutable $finishedDateTime = null;

    public function __construct(
        private ClockInterface $appointmentClock,
        \DateTimeImmutable $appointmentDateTime,
    ) {
        $this->appointmentDateTime = $appointmentDateTime;
    }

    public function finish(): void
    {
        $this->finishedDateTime = $this->appointmentClock->now();
    }

    public function shouldSendFollowUp(\DateTimeImmutable $now): bool
    {
        if ($this->finishedDateTime === null) {
            return false;
        }

        $followUpDate = $this->finishedDateTime->modify('+24 hours');
        return $now >= $followUpDate;
    }
}
```

The aggregate exposes only the methods that make sense for the domain. You cannot mark an appointment as finished before it starts. You cannot send a follow-up before the appointment finishes.

## Making Dependencies Replaceable

Use interfaces for infrastructure dependencies. This makes testing easy and keeps domain logic pure.

```php
<?php

declare(strict_types=1);

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}

final class FrozenClock implements ClockInterface
{
    public function __construct(
        private \DateTimeImmutable $now,
    ) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
```

Your domain model depends on the interface. In production, you inject `SystemClock`. In tests, you inject `FrozenClock` with a known time. This makes testing time-dependent logic trivial.

## Testing the Simplified Model

When you make the easy path the right path, testing becomes easier too. Each method has clear preconditions and postconditions.

```php
<?php

use PHPUnit\Framework\TestCase;

class RegistrationWorkflowTest extends TestCase
{
    public function test_cannot_proceed_to_payment_without_flow(): void
    {
        $workflow = RegistrationWorkflow::captureFlow();

        $this->assertTrue(
            $workflow->isInState(RegistrationState::Initial)
        );
    }

    public function test_can_proceed_to_payment(): void
    {
        $workflow = RegistrationWorkflow::captureFlow();
        $workflow->proceedToPayment(['plan' => 'premium']);

        $this->assertTrue(
            $workflow->isInState(RegistrationState::Payment)
        );
    }

    public function test_can_complete_registration(): void
    {
        $workflow = RegistrationWorkflow::captureFlow();
        $workflow->proceedToPayment(['plan' => 'premium']);
        $workflow->complete();

        $this->assertTrue(
            $workflow->isInState(RegistrationState::Completed)
        );
    }
}
```

The test names read like a specification. Each test validates a single state transition. Because the workflow class encapsulates the state, tests cannot accidentally create invalid workflows.

## Real-World Use Cases

### Payment Processing Workflows

Subscription signup flows with multiple steps (plan selection, payment method, confirmation). Each step is a workflow state. The domain service guides the developer through the correct sequence.

### Ticket Booking Systems

Event booking requires seat selection, customer information, and payment. A booking aggregate ensures you cannot confirm a booking without selecting seats and receiving payment.

### Insurance Claims Processing

Claims go through submission, review, approval, and payout. A claims workflow makes the state transitions explicit and prevents shortcuts.

### Order Fulfillment

E-commerce orders transition through placement, payment capture, warehouse picking, shipping, and delivery. Each transition requires specific preconditions.

### Medical Appointment Scheduling

Healthcare scheduling involves patient registration, provider selection, appointment booking, reminders, and follow-ups. A workflow class ensures patients cannot book appointments with unavailable providers and that reminders are sent at the correct interval before the appointment. Each state transition enforces the business rules of the healthcare domain without scattering logic across controllers.

## Best Practices

- **Design for the consumer first** - The developer using your domain model should be able to guess the correct method sequence.
- **Encapsulate workflows** - Complex multi-step processes should have dedicated workflow classes, not scattered conditionals.
- **Group related dependencies** - A class with more than five constructor parameters likely needs dependency grouping.
- **Use parameter objects** - When a constructor needs many related settings, create a configuration object. This keeps signatures small and makes call sites self-documenting.
- **Use value objects for primitives** - Email, Money, PhoneNumber, and other primitives should be typed value objects.
- **Make illegal states unrepresentable** - If a state combination is invalid, make it impossible to create in code.
- **Favor method encapsulation over getters/setters** - Methods with business meaning are better than property setters.

## Common Mistakes to Avoid

- **Constructor explosion** - Too many constructor parameters makes the class hard to instantiate and test.
- **Leaky abstractions** - Domain logic bleeding into infrastructure or vice versa creates coupling.
- **Anemic domain models** - Domain objects with only getters and setters do not capture business rules.
- **Over-engineering** - Not every class needs a full DDD treatment. Start simple and add structure as complexity grows.
- **Ignoring workflow state** - Multi-step processes without explicit state management lead to scattered if-else chains.

## Frequently Asked Questions

### What does "Make it Easy" mean in DDD?

It means designing your domain model so that the simplest way to call your code is also the correct way. If developers can accidentally misuse your API, the design needs improvement.

### How do I know if my domain model is "easy"?

Ask another developer to implement a feature using your domain model without reading the documentation. If they struggle, the model is not easy enough.

### Should every class follow DDD principles?

No. DDD applies to the core domain where business complexity lives. Simple CRUD operations do not need aggregates, domain events, or repositories.

### How do I handle time-dependent domain logic?

Inject a clock interface. In production, use the system clock. In tests, use a frozen clock. Never call `new DateTime()` directly in domain code.

### What is the difference between a workflow and a state machine?

A workflow is a higher-level orchestration of domain operations. A state machine models the internal states of a single entity. Workflows often compose multiple state machines.

### When should I extract a domain service?

When an operation does not naturally belong to a single entity or value object. Domain services are stateless and coordinate multiple aggregates.

## Conclusion

Edward Barnard's "First Make it Easy" principle transforms DDD from a complex methodology into a practical design philosophy. By focusing on making correct code the easiest code to write, you naturally arrive at well-encapsulated aggregates, clear workflow classes, and testable domain services.

Start by examining your constructors. Group related dependencies. Extract workflow classes. Encapsulate state transitions. The result is a domain model that guides developers toward correct usage and away from bugs.

The easiest path should always be the right path. Your domain model will thank you. Start small, pick one workflow, and refactor it toward simplicity today.
