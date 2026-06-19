---
title: "Domain Logic in One Place - Stop Scattering Business Rules in PHP"
description: "Learn to keep business logic centralized in your PHP application. Stop scattering rules across controllers, services, and database layers with simple OOP techniques."
pubDate: "2023-01-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Domain Logic", "PHP", "OOP", "Business Rules", "Clean Code", "Entities", "Refactoring", "SOLID", "Architecture"]
selected: false
---

Your application works. Features ship on time. But every time you need to change a business rule — a discount calculation, a validation constraint, a default value — you find yourself spelunking through ten different files. The rule is half in a controller, quarter in a repository method, and the rest tucked inside a database migration as a column default.

This is scattered business logic. It's one of the most common problems in PHP applications, and it makes every change slower and riskier than it needs to be.

## What You'll Learn

- How to recognize scattered business logic in your codebase
- Why developers fall into the scatter trap
- How to use plain PHP objects to consolidate business rules
- Practical refactoring from arrays to expressive objects

## Why Business Logic Gets Scattered

As developers, we love making things work. When a new feature request comes in, the instinct is to jump straight into implementation. Open the editor, grab the request payload, stuff it into an array, pass it to a repository, and close the ticket.

The problem isn't that this approach fails — it works fine for the first iteration. The problem is what happens next.

Six months later, someone needs to add a validation rule. Where does it go? The controller? The repository? A new service class? Without a designated home for business logic, each developer makes an arbitrary choice, and the rules spread across the codebase like a slow-motion explosion.

```php
// Controller code — where business logic often hides
$book['title'] = $inputs['title'];

if (isset($inputs['discount'])) {
    $book['discount'] = $inputs['discount'];
}

$this->repository->add($book);
```

This code looks innocent enough. But ask yourself: what's the default discount for a book? Where is that defined? Is it in the repository's `add` method? A column default in the database? Some middleware? What happens if the title is only one character? Who enforces that rule?

When a business person asks "What's the default discount for a book?", you should have exactly one place to look for the answer.

## The Array Problem

Arrays are PHP's default data structure, and they're the primary vector for scattered business logic. An array has no behavior, no validation, and no self-documenting structure. It's just a bag of values waiting for interpretation.

When you pass arrays around your application, every consumer must independently figure out:

- What keys exist and what types they should be
- What values are valid or invalid
- What default values apply
- What transformations to perform

Each consumer makes these decisions independently, which means each one can make different decisions. That's how business logic gets scattered.

## The Solution: Use Objects

The fix is simple, and it doesn't require design patterns, frameworks, or architectural overhauls. Use objects to represent your domain concepts.

```php
class Book
{
    protected string $title;
    protected float $discount = 0;

    public function __construct(string $title, float $discount = 0)
    {
        $this->setTitle($title);
        $this->applyDiscount($discount);
    }

    public function setTitle(string $title): void
    {
        if (strlen($title) < 3) {
            throw new InvalidArgumentException(
                'Title must be at least 3 characters long'
            );
        }

        $this->title = $title;
    }

    public function applyDiscount(float $discountAmount): void
    {
        if ($discountAmount < 0) {
            throw new InvalidArgumentException(
                'Discount cannot be a negative number'
            );
        }

        $this->discount = $discountAmount;
    }
}
```

Now the `Book` class is the single source of truth for everything about a book. The validation rules, default values, and behavior all live in one place.

```php
// Usage — clean and expressive
$book = new Book($inputs['title'], $inputs['discount'] ?? 0);
$this->repository->add($book);
```

If a developer needs to know what a valid title looks like, they look at `Book::setTitle()`. If they need to know the default discount, they look at the `$discount` property declaration. Everything is centralized.

## Revealing Intent Through Naming

One of the hardest problems in software development is naming things. It's also one of the most important. Your variable names, method names, and class names are the primary way your code communicates intent.

The array-based approach reveals nothing:

```php
$book['title'] = $inputs['title'];
$book['discount'] = $inputs['discount'] ?? 0;
```

What is `$book`? Is it a validated entity? A DTO? A query result? No one knows without tracing the code.

The object-based approach communicates clearly:

```php
$book = new Book($inputs['title'], $inputs['discount'] ?? 0);
```

This tells you: a Book object is being created with a title and an optional discount. The constructor validates both. The intent is explicit.

Use real-world language in your code. If your business users say "apply a discount," name the method `applyDiscount()`. If they say "set a title," name the method `setTitle()`. Your code should tell a story that matches the business domain.

## Real-World Use Cases

**E-Commerce Pricing** — A `Product` class with `applyDiscount()`, `calculateTax()`, and `isEligibleForFreeShipping()` methods keeps all pricing logic centralized instead of scattered across cart controllers and checkout services.

**User Registration** — A `User` class that validates email format, password strength, and enforces uniqueness rules prevents registration logic from leaking into controllers, form requests, and event listeners.

**Booking Systems** — A `Reservation` class with `confirm()`, `cancel()`, and `reschedule()` methods ensures all reservation state transitions are validated consistently.

**Subscription Management** — A `Subscription` class that handles upgrade, downgrade, cancel, and renew logic prevents billing rules from duplicating across webhook handlers and admin interfaces.

## Best Practices

**Construct Valid Objects** — Enforce invariants in the constructor. If an object is constructed, it should be valid. This prevents the need for separate validation steps.

**Use Domain Language** — Name classes, methods, and properties using the vocabulary of your business domain, not technical implementation details.

**Keep Infrastructure Out** — Your domain objects should not know about databases, APIs, or UI frameworks. Pure business logic only.

**Prefer Value Objects** — For concepts like `Money`, `Email`, or `ISBN`, create dedicated classes with their own validation and behavior rather than using primitives.

**Test Business Rules in Isolation** — Once business logic is centralized in domain objects, you can test it without mocking databases or HTTP requests.

## Common Mistakes to Avoid

**Anemic Domain Models** — Don't create objects that are just property bags with getters and setters. Put behavior in the object, not in external services.

**Leaking Persistence into Domain** — Don't add `save()` or `load()` methods to your domain objects. That's infrastructure concerns bleeding into business logic.

**Over-Engineering From the Start** — Start with simple classes. Don't introduce repositories, unit of work, or event sourcing until you have a demonstrated need.

**Setters Without Validation** — If you provide setter methods, validate the input. Otherwise, you've just created a fancy array with extra steps.

**Ignoring Immutability** — Where possible, make objects immutable. This prevents accidental state changes and makes your code easier to reason about.

## Frequently Asked Questions

**Q: Should every business rule live in a domain object?**

Every rule that concerns the intrinsic nature of an entity should live in that entity's class. Cross-entity validation or workflow orchestration can go in domain services.

**Q: How do I handle complex validation that requires database access?**

Use domain services for validation that requires external data, like checking email uniqueness. Keep simple invariants (min length, required fields) in the entity itself.

**Q: What about form request validation in Laravel?**

Form request validation is fine for input sanitization and transport-layer concerns. But business rules — like "discount cannot be negative" — should also be enforced in the domain object.

**Q: How big should a domain class be?**

As big as it needs to be to encapsulate all behavior for that concept. A `User` class with 10 methods is fine. A `User` class with 100 methods probably means you're mixing multiple responsibilities.

**Q: Should I refactor existing code immediately?**

Start with new features. When you touch old code for other reasons, extract business logic into domain objects as part of the change. Don't do a big-bang rewrite.

## Conclusion

Scattered business logic is the silent killer of codebase maintainability. It makes every change slower, riskier, and more mentally taxing. The solution isn't complicated architecture — it's disciplined use of plain PHP objects.

Start consolidating your business logic today. The next time you reach for an array, ask yourself: "Does this concept deserve its own class?" Most of the time, the answer is yes.
