---
title: "Finite-State Machines With PHP 8.1 - State Management With Enums"
description: "Learn how to build robust Finite-State Machines using PHP 8.1 enums. Covers states, transitions, workflow engines, and real-world examples for order processing and publishing workflows."
pubDate: "2022-10-10 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Finite-State Machine", "PHP 8.1", "Enums", "Architecture", "State Management"]
selected: false
---

Keeping track of entity state in your application is a constant challenge. Blog posts, multi-step user registration, order processing — every feature involves tracking where something is in a flow. One wrong transition introduces bugs and lost revenue. Fortunately, our industry has decades of research on managing these flows: Finite-State Machines.

## What Is a Finite-State Machine?

A Finite-State Machine (FSM) is an abstract model that can be in exactly one of a finite number of states at any given time. The machine moves from one state to another through defined transitions triggered by input.

Every FSM needs three things:

1. A list of all possible states
2. An initial state
3. Defined transitions between states

Once you learn to spot them, FSMs are everywhere. A traffic light cycles through red, green, and yellow. A door lock toggles between locked and unlocked. A vending machine waits for coins, accepts selection, and dispenses product.

Consider a US traffic light:

- States: Off, Red, Yellow, Green
- Initial state: Off
- Transitions: Off → Red (on boot), Red → Green (after X seconds), Green → Yellow (after Y seconds), Yellow → Red (after Z seconds)

Notice what's missing: there's no transition from Yellow to Green. The FSM model prevents that invalid move by never defining it.

Two more terms matter: **entry actions** and **exit actions**. These fire when the FSM enters or leaves a state — sending notifications, spawning jobs, or logging events.

## Modeling a Publishing Workflow

Let's build an FSM for a blog post's publishing flow. A post starts as a Draft. When the author finishes writing, it moves to Ready for Review. The editor can send it back to Draft with revision notes or schedule it for publication. On the scheduled date, it transitions to Published.

Our state list:

- Draft
- Ready for Review
- Scheduled
- Published

Initial state: Draft

Transitions:
- Draft → Ready for Review
- Ready for Review → Draft
- Ready for Review → Scheduled
- Scheduled → Published

## PHP 8.1 Enums Make FSMs Natural

PHP 8.1 introduced native enumerations, and they transform how we model finite states. An enum defines a type that can only hold a set of allowed values — exactly what an FSM requires.

```php
enum PublishedState {
    case Draft;
    case ReadyForReview;
    case Scheduled;
    case Published;
}
```

Assign an enum value to a property and you get compile-time safety:

```php
$this->state = PublishedState::Published;
var_dump($this->state);
// enum(PublishedState::Published)
```

Try setting an invalid value:

```php
// PHP Fatal error: Uncaught TypeError:
// Cannot assign string to property
// BlogPost::$state of type PublishedState
$this->state = "Junk value";
```

Functions gain type safety too:

```php
function updateState(PublishedState $newState): void
{
    // ...
}
```

Before enums, you'd use class constants and strings:

```php
class PublishedState {
    public const DRAFT = "Draft";
    public const READY_FOR_REVIEW = "Ready for Review";
    public const SCHEDULED = "Scheduled";
    public const PUBLISHED = "Published";
}
```

The problem? Any string passes through:

```php
// invalid state — no error until runtime chaos
$blogPost->updateState("PHP Architect");

// invalid transition — no guard at all
$blogPost->updateState(PublishedState::PUBLISHED);
```

Enums fix both problems. Your IDE auto-completes valid values. Static analysis tools catch mismatches. Runtime throws a fatal error for undefined constants:

```php
// Fatal error: Uncaught Error: Undefined constant
// PublishedState::ReadyForReviw
$post->updateState(PublishedState::ReadyFrReview);
```

## Backed Enums for Persistence

You need to store state in a database. Backed enums connect each case to a scalar value:

```php
enum PublishedState: string {
    case Draft = "Draft";
    case ReadyForReview = "Ready For Review";
    case Scheduled = "Scheduled";
    case Published = "Published";
}
```

Or with integers:

```php
enum PublishedState: int {
    case Draft = 1;
    case ReadyForReview = 2;
    case Scheduled = 3;
    case Published = 4;
}
```

PHP enforces unique backing values at parse time:

```php
// Fatal error: Duplicate value in enum PublishedState
// for cases Draft and ReadyForReview
enum PublishedState: string {
    case Draft = "Draft";
    case ReadyForReview = "Draft";
    case Scheduled = "Scheduled";
    case Published = "Published";
}
```

Access the scalar value through `.value`:

```php
echo PublishedState::Scheduled->value;
// "Scheduled"

$state = PublishedState::Scheduled;
echo $state->value;
// "Scheduled"
```

Convert back with `from()`:

```php
$state = PublishedState::from("Scheduled");
var_dump($state);
// enum(PublishedState::Scheduled)
```

Invalid values throw a ValueError:

```php
// ValueError: "junk" is not a valid backing value
$state = PublishedState::from("junk");
```

Use `tryFrom()` for safe conversion that returns null instead of throwing:

```php
$state = PublishedState::tryFrom("junk");
var_dump($state);
// null
```

Check your ORM's documentation before writing conversion code. Many already support automatic enum mapping.

## Validating Transitions in the Enum

The real power comes when you move transition logic into the enum itself. Enums can contain methods and implement interfaces:

```php
enum PublishedState: string {
    case Draft = "Draft";
    case ReadyForReview = "Ready For Review";
    case Scheduled = "Scheduled";
    case Published = "Published";

    public function isValidTransition(self $newState): bool
    {
        $transitions = [
            self::Draft->value => [
                self::ReadyForReview,
            ],
            self::ReadyForReview->value => [
                self::Draft,
                self::Scheduled,
            ],
            self::Scheduled->value => [
                self::Published,
            ],
        ];

        return in_array($newState, $transitions[$this->value] ?? []);
    }
}
```

Now your model uses the enum to guard every transition:

```php
public function updateState(PublishedState $newState): void
{
    if (!$this->state->isValidTransition($newState)) {
        $message = "Unable to transition from ";
        $message .= "{$this->state->value} to {$newState->value}";
        throw new InvalidTransitionException($message);
    }

    $this->state = $newState;
}
```

The `Scheduled` case has no outgoing transitions defined — it's a terminal state. You can add an empty array for completeness if desired, but skipping it keeps the code readable.

## Real-World Example: Order Processing

Let's apply the same pattern to an order processing workflow with more moving parts:

```php
enum OrderStatus: string {
    case Pending = "pending";
    case Paid = "paid";
    case Shipped = "shipped";
    case Delivered = "delivered";
    case Cancelled = "cancelled";
    case Refunded = "refunded";

    public function allowedTransitions(): array
    {
        return match($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered => [self::Refunded],
            self::Cancelled => [self::Refunded],
            self::Refunded => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
```

Using `match` makes the transition table more readable. Each state clearly declares where it can go next.

## Workflow Engine Abstraction

For applications with multiple FSMs, abstract the transition logic into a reusable trait or service:

```php
trait HasStateMachine
{
    public function transitionTo(BackedEnum $target): void
    {
        if (!$this->state->canTransitionTo($target)) {
            throw new InvalidTransitionException(
                "Cannot transition from {$this->state->value} to {$target->value}"
            );
        }

        $this->state = $target;
    }

    public function canTransitionTo(BackedEnum $target): bool
    {
        return $this->state->canTransitionTo($target);
    }
}
```

## Before PHP 8.1: Class-Based Fallback

If you're maintaining pre-8.1 code, you can approximate the pattern with classes:

```php
class PublishedState {
    public const DRAFT = "Draft";
    public const READY_FOR_REVIEW = "Ready for Review";
    public const SCHEDULED = "Scheduled";
    public const PUBLISHED = "Published";

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function from(string $value): self
    {
        $valid = [self::DRAFT, self::READY_FOR_REVIEW, self::SCHEDULED, self::PUBLISHED];
        if (!in_array($value, $valid, true)) {
            throw new \InvalidArgumentException("Invalid state: $value");
        }
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
```

This gives you constructor-level validation and type safety through type hints. When you finally migrate to PHP 8.1+, swap the class for the enum — the interface stays the same.

## In Review

Finite-State Machines give you a clear, declarative way to manage entity states and transitions. PHP 8.1 enums make implementing FSMs natural: they provide type safety, prevent invalid states, and keep transition rules close to the state definitions. Whether you're modeling order processing, content publishing, or user onboarding, FSMs with enums turn fragile state logic into maintainable, self-documenting code.
