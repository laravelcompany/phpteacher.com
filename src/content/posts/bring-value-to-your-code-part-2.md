---
title: "Bring Value To Your Code Part 2 - Advanced Value Objects in PHP"
description: "Advanced value objects in PHP with Doctrine ORM integration. Explore immutable domain primitives and rich modeling techniques."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-12-12 21:00:00"
tags: ["Value Objects", "PHP", "DDD", "Doctrine", "ORM", "Immutability"]
---

Value Objects are one of the foundational building blocks of Domain-Driven Design, but their benefits extend far beyond DDD projects. When implemented properly, Value Objects bring type safety, self-validation, rich behavior, and testability to any PHP codebase.

## Value Objects Are Always Valid

The most compelling property of a well-designed Value Object is that its construction guarantees validity. The constructor enforces all validation rules, so if an instance exists, it contains valid data.

```php
final class PhpArchEmailAddress
{
    public function __construct(
        public readonly string $value
    ) {
        $regex = '/.*@phparch\.com/';
        if (preg_match($regex, $value) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('<%s> is not a valid email address.', $value)
            );
        }
    }
}
```

Compare this to working with plain strings. Every time a function receives a raw string for an email address, it must validate the format. With a Value Object, the type system guarantees validity. No validation duplication, no edge cases missed, no assumptions.

## Rich Type System Through Value Objects

PHP 8.1 provides typed properties, constructor promotion, and readonly properties. Value Objects extend PHP's type system with domain-specific types that make your code self-documenting.

Without Value Objects:

```php
class PhpArchNewEditionNotifier
{
    public string $senderEmailAddress;
}
```

With Value Objects:

```php
class PhpArchNewEditionNotifier
{
    public PhpArchEmailAddress $senderEmailAddress;
}
```

The second version communicates intent directly. You know exactly what `$senderEmailAddress` must be. The type system enforces it.

## Specialized and Rich Models

Value Objects address one specific concern and encapsulate rich, context-bound business logic. Instead of a single class holding everything, move related attributes into dedicated Value Objects.

Consider a naive `Price` class:

```php
class Price
{
    public function __construct(
        public readonly float $price,
        public readonly string $currency,
        public readonly float $vatRate,
        public readonly ?float $discountRate = null,
        public readonly ?float $discountAmount = null,
    ) {
    }
}
```

This mixes concerns. Currency handling, VAT calculation, and discount logic all live in one place. Instead, decompose into focused Value Objects:

```php
use Money\Currency;
use Money\Money;

class VatRate
{
    public function __construct(
        public readonly float $rate,
    ) {
    }

    public function calculateVat(Money $money): Money
    {
        return $money->multiply($this->rate);
    }

    public function applyVat(Money $money): Money
    {
        return $money->add($this->calculateVat($money));
    }
}

interface Discount
{
    public function asRate(Money $money): float;
    public function asMoney(Money $money): Money;
    public function applyDiscount(Money $money): Money;
}

final class RateDiscount implements Discount
{
    public function __construct(
        public readonly float $rate,
    ) {
    }

    public function asRate(Money $money): float
    {
        return $this->rate;
    }

    public function asMoney(Money $money): Money
    {
        return $money->multiply($this->rate);
    }

    public function applyDiscount(Money $money): Money
    {
        return $money->subtract($this->asMoney($money));
    }
}

final class FixedAmountDiscount implements Discount
{
    public function __construct(
        public readonly Money $amount,
    ) {
    }

    public function asRate(Money $money): float
    {
        return $this->amount->getAmount() / $money->getAmount();
    }

    public function asMoney(Money $money): Money
    {
        return $this->amount;
    }

    public function applyDiscount(Money $money): Money
    {
        return $money->subtract($this->asMoney($money));
    }
}
```

Now the `Price` class becomes clean:

```php
class Price
{
    public function __construct(
        public readonly Money $price,
        public readonly VatRate $vatRate,
        public readonly Discount $discount,
    ) {
    }

    public function total(): Money
    {
        return $this->vatRate->applyVat(
            $this->discount->applyDiscount($this->price)
        );
    }
}
```

Each Value Object handles its own concern. The `VatRate` knows how VAT works. The `Discount` interface lets you swap strategies. The `Price` orchestrates them. This is specialization at work.

## Testing Value Objects

Value Objects dramatically simplify testing. Without them, every class that accepts an email address string needs validation tests. With them, test the Value Object once, and every consumer gets validity for free.

```php
it('accepts valid email addresses', function () {
    $email = new PhpArchEmailAddress('user@phparch.com');
    expect($email->value)->toBe('user@phparch.com');
});

it('rejects invalid email addresses', function () {
    expect(fn () => new PhpArchEmailAddress('not-an-email'))
        ->toThrow(\InvalidArgumentException::class);
});
```

No other test in your codebase needs to validate email format. The Value Object's existence guarantees it.

## Cognitive Load Reduction

More classes but smaller ones. Each class has a single responsibility and clear boundaries. When you read `VatRate`, you understand VAT. When you read `Discount`, you understand discount strategies. The context is isolated, the behavior is predictable, and the immutability means nothing changes unexpectedly.

## Caching and Memoization

Immutability makes Value Objects excellent candidates for caching. You can safely cache them indefinitely because they never change.

```php
$cacheKey = PhpArchEmailAddress::class . ':' . $emailAsString;

if (! $cache->hasItem($cacheKey)) {
    $email = new PhpArchEmailAddress($emailAsString);
    $cache->save($cache->getItem($cacheKey)->set($email));
}

$emailAddress = $cache->getItem($cacheKey)->get();
```

For expensive computations, memoization caches results locally:

```php
final class ComplicatedMathematicalValueObject
{
    private ?float $result = null;

    public function __construct(
        public readonly float $val1,
        public readonly float $val2,
    ) {
    }

    public function complicatedOperation(): float
    {
        if (is_null($this->result)) {
            $this->result = /* very long operation */ 0;
        }
        return $this->result;
    }
}
```

Be careful: memoization breaks the `==` operator because the internal state changes. After calling `complicatedOperation()`, two otherwise identical objects will not be equal via `==`. The solution is an explicit `isEqual()` method:

```php
final class ComplicatedMathematicalValueObject
{
    public function isEqual(self $other): bool
    {
        return $this->val1 === $other->val1
            && $this->val2 === $other->val2;
    }
}
```

This also handles inheritance correctly. The `==` operator requires exact type match, so a subclass instance and parent class instance with the same values will not be equal. An `isEqual()` method compares only the values:

```php
class Color
{
    public function __construct(
        public readonly int $red,
        public readonly int $green,
        public readonly int $blue,
        public readonly float $alpha,
    ) {
    }

    public function isEqual(Color $other): bool
    {
        return $this->red === $other->red
            && $this->green === $other->green
            && $this->blue === $other->blue
            && $this->alpha === $other->alpha;
    }
}

$colorA = new Color(255, 255, 255, 1.0);
$colorB = new Color(255, 255, 255, 1.0);

var_dump($colorA->isEqual($colorB)); // true
```

## Value Objects vs Data Transfer Objects

A common confusion is the difference between Value Objects and DTOs. They serve different purposes.

DTOs live on application boundaries. They carry data in and out of the system. They do not need to be complete, properties can be nullable, and validation is not their responsibility. DTOs rarely contain business logic.

Value Objects live within the application, especially in the domain layer. They must always be valid. They encapsulate business logic and behavior. They are specialized and focused on one concern.

DTOs make data interchangeable. Value Objects make data meaningful and safe.

## Persisting Value Objects

The biggest challenge with Value Objects is persistence. Unlike entities, Value Objects have no identity. Your database does not need to mirror your code structure.

### Alongside the Encapsulating Entity

```php
class Book
{
    public readonly string $uid;
    private Author $author;
    private Title $title;
    private ?Color $coverColor;
}
```

```sql
CREATE TABLE book (
    uid              VARCHAR(36)  NOT NULL,
    author_uid       VARCHAR(36)  NOT NULL,
    title            VARCHAR(255) NOT NULL,
    cover_color_rgba CHAR(8)      NULL,
    FOREIGN KEY (author_uid) REFERENCES author(uid)
);
```

The `Title` Value Object maps directly to a string column. The `Color` Value Object stores as RGBA notation in a single field rather than four separate columns.

### Denormalization

Denormalization stores Value Object fields directly in the parent table rather than creating separate tables with foreign keys. This avoids joins and improves query performance.

```sql
CREATE TABLE book (
    color_red       INT     NULL,
    color_green     INT     NULL,
    color_blue      INT     NULL,
    color_alpha     FLOAT   NULL,
    price_amount    INT     NULL,
    price_currency  CHAR(3) NULL
);
```

The `Money` Value Object stores as two columns: amount and currency. The `Color` Value Object stores as four columns, one per channel. No joins needed.

### Serialization

Serialize the Value Object to JSON and store in a single column:

```php
// Color stored as:
// {"red":255,"green":0,"blue":0,"alpha":1.0}
```

Modern RDBMS engines support JSON queries directly, so this remains queryable if needed.

### Doctrine Embeddables

Doctrine ORM provides native Value Object support through embeddables:

```php
#[Embeddable]
class Address
{
    #[Column(type: 'string')]
    public readonly string $street;

    #[Column(type: 'string')]
    public readonly string $city;

    #[Column(type: 'string')]
    public readonly string $postalCode;
}

#[Entity]
class Customer
{
    #[Id]
    #[Column(type: 'integer')]
    private int $id;

    #[Embedded(class: Address::class)]
    private Address $address;
}
```

Doctrine flattens the embeddable columns into the parent table automatically. No separate table, no joins, no foreign keys. The PHP code expresses the domain concept while the database stores flat columns.

### As an Entity (Last Resort)

When other options are not feasible, Value Objects can be stored as entities with a private, auto-generated identifier. This identifier is never exposed through the public API:

```php
trait ValueObjectWithId
{
    private int $id;
}

class Color
{
    use ValueObjectWithId;

    public function __construct(
        public readonly int $red,
        public readonly int $green,
        public readonly int $blue,
        public readonly float $alpha,
    ) {
    }
}
```

The database generates the ID. Doctrine or another ORM hydrates the private property. The Value Object's public API never reveals the ID exists.

### Dedicated Persistence Types

ORMs like Doctrine and Eloquent support custom type mapping. You create a converter that handles the Value Object to database column transformation. Once configured, the conversion happens automatically.

## PHP 8.1 Enums and Value Objects

PHP 8.1 introduced native enums, which are a form of Value Object. However, think carefully before mapping them directly to database enum types. MySQL enums have well-documented issues: adding new values requires `ALTER TABLE`, they are not reusable across tables, and the ordering semantics are counterintuitive.

Store enum values as simple strings or integers in the database and let the PHP enum class provide the rich type:

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
```

The database column stores `'pending'`, `'shipped'`, etc. The PHP code works with the typed enum. No database schema changes needed when adding new cases.

## Notable Value Object Implementations

The PHP ecosystem has several excellent Value Object libraries worth studying:

- **ramsey/uuid** — The definitive UUID implementation in PHP, now with UUID v7 support
- **moneyphp/money** — Solves floating-point precision issues and provides a textbook Value Object example
- **symfony/string** — An immutable string object with rich functionality for slugification, truncation, inflection, and more
- **Larry Garfield's Maybe and Result** — Explore alternatives to null through Value Object monads

## Conclusion

Value Objects transform how you think about data in your application. They guarantee validity at construction, eliminate duplicated validation logic, provide rich domain-specific types, encapsulate business behavior, and simplify testing.

Domain-Driven Design is gaining traction in the PHP community because PHP has matured dramatically. Value Objects are accessible even without a full DDD adoption. Start with an email address, a color, or a monetary amount. The benefits compound as the pattern spreads through your codebase.

Another building block to bring value to your code.
