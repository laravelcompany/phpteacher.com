---
title: "Value Objects in PHP: Domain-Driven Design Building Blocks"
description: "Master value objects in PHP with immutability, value equality, and self-validation. Learn DDD patterns for cleaner domain models with PHP 8.x features."
pubDate: "2023-06-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Value Objects", "PHP", "DDD", "Domain-Driven Design", "Immutability", "Value Equality", "Validation", "PHP 8", "Design Patterns"]
---

Value Objects are one of the most important building blocks in Domain-Driven Design. They encapsulate concepts that are defined by their attributes rather than their identity. A Price, an Email, a Coordinate, or a DateRange are all examples of Value Objects.

The power of Value Objects comes from four key properties: immutability, replaceability, value equality, and side-effect-free behavior. Combined with built-in validation, they transform primitive-obsessed code into expressive, self-validating domain logic.

## What You'll Learn

- The four defining characteristics of Value Objects
- Implementing immutability with PHP 8 features
- Value equality comparison strategies
- Invariant validation on object creation
- Using phpspec to test Value Objects
- Real-world Value Object patterns
- Common pitfalls and how to avoid them

## The Four Pillars of Value Objects

### Immutability

Once created, a Value Object cannot change. Any operation that would modify it returns a new instance.

```php
<?php

declare(strict_types=1);

class Price
{
    public function __construct(
        private readonly int $amount,
        private readonly string $currency,
    ) {}

    public function add(Price $other): Price
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                'Cannot add prices with different currencies'
            );
        }

        return new Price(
            amount: $this->amount + $other->amount,
            currency: $this->currency,
        );
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
```

The `readonly` keyword (PHP 8.1) enforces immutability at the language level. Property values are set once in the constructor and never change.

### Replaceability

You replace a Value Object by assigning a new instance. There is no concept of updating in place.

```php
<?php

$oldPrice = new Price(1000, 'USD');
$newPrice = $oldPrice->add(new Price(250, 'USD'));
// $oldPrice is unchanged, $newPrice is 1250 USD
```

### Value Equality

Two Value Objects are equal if all their properties are equal. Identity does not matter.

```php
<?php

declare(strict_types=1);

class Coordinate
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public function equals(Coordinate $other): bool
    {
        return $this->latitude === $other->latitude
            && $this->longitude === $other->longitude;
    }
}

$home = new Coordinate(40.7128, -74.0060);
$office = new Coordinate(40.7128, -74.0060);

var_dump($home === $office);   // false (different objects)
var_dump($home->equals($office)); // true (same values)
```

For more complex comparison, you can generate and compare hashes:

```php
<?php

declare(strict_types=1);

abstract class ValueObject
{
    public function equals(ValueObject $other): bool
    {
        return $this->hash() === $other->hash();
    }

    public function hash(): string
    {
        return md5(serialize($this));
    }
}
```

### Side-Effect-Free Behavior

Methods on Value Objects return new instances. They never modify internal state or produce side effects like logging, database writes, or network calls.

```php
<?php

declare(strict_types=1);

class Money
{
    public function __construct(
        private readonly int $amount,
        private readonly string $currency,
    ) {}

    public function multiply(float $factor): Money
    {
        return new Money(
            amount: (int) round($this->amount * $factor),
            currency: $this->currency,
        );
    }

    public function allocate(float $percentage): array
    {
        $firstAmount = (int) round($this->amount * $percentage / 100);
        $secondAmount = $this->amount - $firstAmount;

        return [
            new Money($firstAmount, $this->currency),
            new Money($secondAmount, $this->currency),
        ];
    }
}
```

## Validation Through Invariants

A Value Object should never exist in an invalid state. Validation happens at construction time.

```php
<?php

declare(strict_types=1);

class Email
{
    public function __construct(
        public readonly string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                "Invalid email address: {$value}"
            );
        }
    }
}

class Age
{
    public function __construct(
        public readonly int $value,
    ) {
        if ($value < 0 || $value > 150) {
            throw new \InvalidArgumentException(
                "Age must be between 0 and 150, got {$value}"
            );
        }
    }
}
```

## Using Attributes for Invariants

PHP 8 attributes provide a clean way to declare validation rules on Value Objects.

```php
<?php

declare(strict_types=1);

#[Attribute(Attribute::TARGET_METHOD)]
class Invariant
{
}

abstract class ValueObject
{
    public function __construct()
    {
        $this->validateInvariants();
    }

    private function validateInvariants(): void
    {
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(Invariant::class);

            if (!empty($attributes)) {
                $method->invoke($this);
            }
        }
    }
}

class Person extends ValueObject
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {
        parent::__construct();
    }

    #[Invariant]
    protected function assertAdult(): void
    {
        if ($this->age < 18) {
            throw new \RuntimeException(
                'Person must be at least 18 years old'
            );
        }
    }

    #[Invariant]
    protected function assertNameIsLongEnough(): void
    {
        if (strlen($this->name) < 3) {
            throw new \RuntimeException(
                'Name must be at least 3 characters'
            );
        }
    }
}

// This throws: Person must be at least 18 years old
$person = new Person(name: 'Alice', age: 12);
```

## Value Objects with Type Safety

PHP 8's constructor promotion and promoted properties make Value Objects concise.

```php
<?php

declare(strict_types=1);

class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $postalCode,
        public readonly string $country,
    ) {}
}

class PhoneNumber
{
    public function __construct(
        public readonly string $countryCode,
        public readonly string $number,
    ) {
        if (!preg_match('/^\+\d{1,3}$/', $countryCode)) {
            throw new \InvalidArgumentException(
                'Invalid country code'
            );
        }
    }

    public function fullNumber(): string
    {
        return "{$this->countryCode} {$this->number}";
    }
}
```

## Testing Value Objects with phpspec

phpspec is a testing framework that complements Value Object design. It emphasizes behavior specification.

```php
<?php

class PersonSpec extends \PhpSpec\ObjectBehavior
{
    function let(): void
    {
        $this->beConstructedWith(
            name: 'Alice',
            age: 30,
        );
    }

    function it_is_an_adult_person(): void
    {
        $this->shouldThrow(\Throwable::class)
            ->during('set', ['age' => 12]);
    }

    function its_name_is_not_too_short(): void
    {
        $this->shouldThrow(\Throwable::class)
            ->during('set', ['name' => 'a']);
    }
}
```

## JSON Serialization

Value Objects often need to be serialized for API responses or storage. Implement `JsonSerializable` for consistent serialization.

```php
<?php

declare(strict_types=1);

class Money implements \JsonSerializable
{
    public function __construct(
        private readonly int $amount,
        private readonly string $currency,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount / 100,
            'currency' => $this->currency,
            'formatted' => number_format($this->amount / 100, 2) . ' ' . $this->currency,
        ];
    }
}

// Now Money works with json_encode() automatically
$money = new Money(2999, 'USD');
echo json_encode($money);
// {"amount":29.99,"currency":"USD","formatted":"29.99 USD"}
```

## Doctrine Integration

When using Doctrine ORM, Value Objects are mapped as embeddables. This stores their properties as columns in the owning entity's table.

```php
<?php

namespace App\Domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Address
{
    #[ORM\Column(length: 255)]
    public readonly string $street;

    #[ORM\Column(length: 100)]
    public readonly string $city;

    #[ORM\Column(length: 20)]
    public readonly string $postalCode;

    #[ORM\Column(length: 2)]
    public readonly string $country;

    public function __construct(
        string $street,
        string $city,
        string $postalCode,
        string $country,
    ) {
        $this->street = $street;
        $this->city = $city;
        $this->postalCode = $postalCode;
        $this->country = $country;
    }
}

#[ORM\Entity]
class Customer
{
    #[ORM\Embedded(class: Address::class, columnPrefix: 'billing_')]
    public readonly Address $billingAddress;

    #[ORM\Embedded(class: Address::class, columnPrefix: 'shipping_')]
    public readonly Address $shippingAddress;
}
```

Doctrine creates six columns in the `customer` table: `billing_street`, `billing_city`, `billing_postal_code`, `billing_country`, `shipping_street`, `shipping_city`, `shipping_postal_code`, and `shipping_country`. No join tables, no foreign keys.

## Composition with Value Objects

Value Objects compose naturally to build richer domain concepts.

```php
<?php

declare(strict_types=1);

class FullName
{
    public function __construct(
        public readonly string $firstName,
        public readonly ?string $middleName,
        public readonly string $lastName,
    ) {}

    public function full(): string
    {
        return trim(
            "{$this->firstName} {$this->middleName} {$this->lastName}"
        );
    }

    public function lastFirst(): string
    {
        return "{$this->lastName}, {$this->firstName}";
    }
}

class Customer
{
    public function __construct(
        public readonly CustomerId $id,
        public readonly FullName $name,
        public readonly Email $email,
        public readonly PhoneNumber $phone,
    ) {}
}
```

## Real-World Use Cases

### E-Commerce Pricing

Money, Price, TaxRate, Discount, and Currency are Value Objects. They ensure pricing calculations are consistent and type-safe.

### Healthcare Systems

BloodPressure, Temperature, MedicationDosage, and PatientId are Value Objects with domain-specific validation rules.

### Financial Applications

AccountNumber, TransactionAmount, InterestRate, and MaturityDate are Value Objects that prevent calculation errors through type safety.

### Geolocation Services

Coordinate, Distance, BoundingBox, and GeoPoint Value Objects ensure geographic calculations are correct.

## Best Practices

- **Keep Value Objects small** - Focus on a single concept. An EmailAddress class should not contain a person's name.
- **Use readonly properties** - PHP 8.1's readonly keyword enforces immutability without boilerplate.
- **Validate on construction** - If a Value Object exists, it is valid. Throw exceptions for invalid data.
- **Implement equals()** - Provide a type-safe equality method. Override __clone as private to prevent cloning.
- **Avoid entity references** - Value Objects should not reference entities. They describe entities.
- **Use named constructors** - Static factory methods improve readability over complex constructors.

## Common Mistakes to Avoid

- **Making Value Objects mutable** - Without immutability, Value Objects can be accidentally modified, leading to subtle bugs.
- **Skipping validation** - A Value Object with invalid data defeats its purpose. Validate aggressively.
- **Overusing Value Objects** - Not every string needs to be a Value Object. Use them for domain concepts with rules.
- **Inheriting from entities** - Value Objects should not extend entity base classes. They have different semantics.
- **Ignoring performance** - Creating many Value Objects in hot loops can impact performance. Profile before optimizing.

## Frequently Asked Questions

### What is the difference between a Value Object and an Entity?

Entities have identity that persists across state changes. Value Objects are defined solely by their attributes. Two Value Objects with the same properties are interchangeable. Two entities with the same properties are different if they have different identities.

### Should Value Objects be stored in the database?

Yes. Value Objects are typically stored as columns in the owning entity's table (embedded value pattern). They are not stored in separate tables with foreign keys.

### Can Value Objects have behavior?

Absolutely. Value Objects should encapsulate behavior related to their concept. A Money object should know how to add, subtract, and multiply.

### Should Value Objects be JSON serializable?

Yes. Implement `JsonSerializable` for easy serialization. This is especially useful in API responses.

### How do I handle Value Object collections?

Use an array or a typed collection class. The collection itself can be a Value Object if it has domain-specific behavior.

### Can Value Objects reference other Value Objects?

Yes. Composition of Value Objects is encouraged. An Address can contain a Coordinate and a PostalCode.

### Does Doctrine support Value Objects?

Doctrine supports embeddables, which map Value Objects to columns in the owning entity's table. This is the standard persistence strategy for Value Objects.

## Conclusion

Value Objects transform domain modeling in PHP. By encapsulating concepts with immutability, validation, and behavior, they eliminate primitive obsession and make your domain logic self-documenting.

PHP 8's constructor promotion, readonly properties, and attributes make Value Objects more concise than ever. Combined with frameworks like phpspec that emphasize specification testing, you can build domain models that are both correct and expressive.

Start by identifying primitive types in your domain. Extract each into a Value Object. Add validation rules. Watch your code become clearer, safer, and more aligned with your business domain.
