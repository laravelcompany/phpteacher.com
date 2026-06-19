---
title: "Bring Value To Your Code - Mastering Value Objects in PHP"
description: "Master value objects in PHP for cleaner domain modeling. Learn immutability patterns and type safety techniques for robust applications."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-11-12 21:00:00"
tags: ["Value Objects", "PHP", "DDD", "Architecture", "Immutability"]
---

Value Objects are one of the most important building blocks in Domain-Driven Design, but their usefulness extends far beyond DDD. They transform primitive-obsessed code into self-validating, expressive, and side-effect-free domain logic.

## What Is a Value Object?

Eric Evans, in *Domain-Driven Design*, defines a Value Object as:

> An object that represents a descriptive aspect of the domain with no conceptual identity.

Vernon Vaughn adds these characteristics in *Implementing Domain-Driven Design*:

- Measures, quantifies, or describes a thing in the domain
- Is immutable
- Captures a whole value
- Is replaceable
- Prevents side-effects

The defining trait: a Value Object is identified by **what it is**, not **who it is**.

### Identity vs. Value

If you buy five identical buckets of pink paint, you grab the first one and start painting. You don't care which bucket — they're interchangeable. Their value lies in the color they contain, not their identity.

In PHP, this maps to objects with no identifier:

```php
class EmailAddress
{
    public function __construct(
        public string $value
    ) {}
}
```

PHP 8.1 Enums also work as Value Objects:

```php
enum Suit
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}
```

Enums are singletons, so they always return the same instance. This doesn't disqualify them — they meet all Value Object criteria.

## Rule 1: Descriptive Attributes

A Value Object has one or more attributes whose values define it. Attributes are either primitives or other Value Objects:

```php
class Color
{
    public function __construct(
        public int $red,
        public int $green,
        public int $blue,
        public float $alpha,
    ) {}
}
```

Attributes can be other Value Objects:

```php
enum Sharpness
{
    case VerySharp;
    case NotSoSharp;
    case NotSharpAtAll;
}

enum Hardness
{
    case OhSoHard;
    case RegularHard;
    case Medium;
    case Soft;
}

class VirtualPencil
{
    public function __construct(
        public Color $color,
        public Sharpness $sharpness,
        public Hardness $hardness,
    ) {}
}

$virtualPencil = new VirtualPencil(
    new Color(0, 0, 0, 1),
    Sharpness::VerySharp,
    Hardness::OhSoHard,
);
```

## Rule 2: Immutability

Mutable Value Objects cause subtle, hard-to-debug bugs. Consider this:

```php
class VirtualPencilExtensionHandler
{
    public function __construct(
        public Color $color,
    ) {}

    public function handleExtension(TheExtensionInterface $extension): void
    {
        $extension->run($this->color);
    }
}

class NastyExtensionThatChangesColor implements TheExtensionInterface
{
    public function run(Color $color): void
    {
        $color->blue = 255;
    }
}
```

The original Color object gets mutated through a reference. PHP 8.1's `readonly` properties prevent this:

```php
class Color
{
    public function __construct(
        public readonly int $red,
        public readonly int $green,
        public readonly int $blue,
        public readonly float $alpha,
    ) {}
}
```

PHP 8.2 makes this even cleaner with readonly classes:

```php
readonly class Color
{
    public function __construct(
        public int $red,
        public int $green,
        public int $blue,
        public float $alpha,
    ) {}
}
```

If you can't use the latest PHP, use private properties with public getters and no setters. There are cases where mutable Value Objects are acceptable, but they should never be shared.

## Rule 3: Whole Value

A Value Object must be complete. Every attribute contributes to the whole — you can't leave out a color channel. Set all properties in the constructor to prevent invalid states.

For complex construction, use factory methods:

```php
class Color
{
    public function __construct(
        public readonly int $red,
        public readonly int $green,
        public readonly int $blue,
        public readonly float $alpha,
    ) {}

    public static function ofHexColor(string $hexColor, float $opacity): self
    {
        $hexColor = ltrim($hexColor, '#');
        $parts = match(strlen($hexColor)) {
            3 => [
                str_repeat(substr($hexColor, 0, 1), 2),
                str_repeat(substr($hexColor, 1, 1), 2),
                str_repeat(substr($hexColor, 2, 1), 2),
            ],
            6 => [
                substr($hexColor, 0, 2),
                substr($hexColor, 2, 2),
                substr($hexColor, 4, 2),
            ],
            default => throw new InvalidArgumentException(
                sprintf('%s is not a valid CSS color', $hexColor)
            ),
        };

        return new self(
            hexdec($parts[0]),
            hexdec($parts[1]),
            hexdec($parts[2]),
            $opacity,
        );
    }
}

var_dump(Color::ofHexColor('#f00', 1.0));
// Color Object
// (
//     [red] => 255
//     [green] => 0
//     [blue] => 0
//     [alpha] => 1
// )
```

Add multiple static constructors for different notations:

```php
class Color
{
    public static function ofHsvColor(int $hue, int $saturation, int $value, float $opacity): self
    {
        // convert, validate, instantiate
    }

    public static function ofCmykColor(int $cyan, int $magenta, int $yellow, int $black, float $opacity): self
    {
        // convert, validate, instantiate
    }
}
```

Named static constructors are more expressive than generic constructors. Make the constructor private and provide one or more static methods:

```php
class Color
{
    private function __construct(
        public readonly int $red,
        public readonly int $green,
        public readonly int $blue,
        public readonly float $alpha,
    ) {}

    public static function ofRgba(int $red, int $green, int $blue, float $alpha = 1.0): self
    {
        // validation
        return new self($red, $green, $blue, $alpha);
    }
}
```

### Evolvable Objects

For complex construction chains, use the evolvable object pattern:

```php
class Color
{
    public function __construct(
        public readonly int $red = 0,
        public readonly int $blue = 0,
        public readonly int $green = 0,
        public readonly float $alpha = 1,
    ) {}

    public function withRed(int $red): self
    {
        return new self($red, $this->blue, $this->green, $this->alpha);
    }

    public function withBlue(int $blue): self
    {
        return new self($this->red, $blue, $this->green, $this->alpha);
    }
}

$color = new Color();
// Color (red: 0, green: 0, blue: 0, alpha: 1)

$otherColor = $color->withRed(255);
// Color (red: 255, green: 0, blue: 0, alpha: 1)
```

Each method returns a new valid instance, never mutating the original.

## Rule 4: Replaceable

Immutable Value Objects can't change — they get replaced. This mirrors how scalar values work:

```php
$firstname = 'Eric';
$firstname = 'John';  // Eric didn't become John, the value was replaced
```

Same with Value Objects:

```php
$firstname = new Firstname('Eric');
$firstname = new Firstname('John');
```

Never do `$firstname->value = 'John'`.

## Rule 5: Side-Effect-Free

Operations on Value Objects should never alter their state:

```php
class Total
{
    public function __construct(
        public readonly int $value,
    ) {}

    public function add(int $valueToAdd): self
    {
        return new self($this->value + $valueToAdd);
    }
}

$originalTotal = new Total(2);
$newTotal = $originalTotal->add(3);

echo $originalTotal->value;  // 2
echo $newTotal->value;       // 5
```

The original remains unchanged. A new instance carries the result.

## When to Use Value Objects

Replace primitives with Value Objects whenever you have:

- **Validation rules**: An `Email` object always contains a valid email — callers never check format.
- **Composite data**: Coordinates, money amounts, addresses, date ranges.
- **Business logic**: Methods on the object itself rather than scattered helpers.
- **Type safety**: `function pay(Money $amount)` prevents accidentally passing a temperature.

### Doctrine Integration

Value Objects integrate naturally with Doctrine through embeddables:

```php
#[Embeddable]
class Address
{
    public function __construct(
        #[Column(type: 'string')]
        public readonly string $street,
        #[Column(type: 'string')]
        public readonly string $city,
        #[Column(type: 'string')]
        public readonly string $postalCode,
        #[Column(type: 'string')]
        public readonly string $country,
    ) {}
}
```

The database stores the columns inline in the parent table, and Doctrine handles hydration automatically.

## Benefits Over Primitives

| Concern | Primitives | Value Objects |
|---------|-----------|---------------|
| Validation | Every caller must check | Self-validating in constructor |
| Type safety | `string $email` accepts any string | `Email $email` guarantees format |
| Behavior | Functions scattered across codebase | Methods on the object |
| Documentation | Comments or README | The code itself |
| Refactoring | Search-and-replace on strings | Type system guides changes |

## Rules Summary

1. A Value Object defines itself by its **value**, not its identity
2. It has one or more **descriptive attributes** — primitives or other Value Objects
3. It should be **immutable** (readonly properties, no setters)
4. It captures a **whole value** — complete and valid from construction
5. Value Objects are **replaced**, not updated
6. Operations are **side-effect-free** — they return new instances

Value Objects turn "stringly-typed" code into a domain model that communicates intent. Start small — wrap an email address or a monetary amount — and watch how the design improves from the edges inward.
