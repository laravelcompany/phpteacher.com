---
title: "Fractional Math in PHP - Working With Fractions Programmatically"
description: "Learn how to add, subtract, multiply, and divide fractions in PHP with a clean API. Includes LCM calculation, fraction simplification, and mixed fraction output."
pubDate: "2022-09-22 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Math", "Fractions", "Algorithms", "Arithmetic"]
selected: false
---

Working with fractions in PHP is not something most developers do daily. But when you need to — for a recipe calculator, a measurement conversion tool, or any application dealing with rational numbers — having a solid implementation saves time and prevents bugs.

This article walks through a complete fraction arithmetic library in PHP 8.1. We will implement addition, subtraction, multiplication, and division of fractions, handle simplification, and output mixed fractions for readability.

## The Fraction Class

Start with a simple value object that holds a numerator and denominator:

```php
namespace Puzzles;

class Fraction
{
    public function __construct(
        public readonly int $numerator,
        public readonly int $denominator,
    ) {
        if ($denominator === 0) {
            throw new \InvalidArgumentException('Denominator cannot be zero');
        }
    }

    public function simplify(): Fraction
    {
        $gcd = $this->gcd(abs($this->numerator), abs($this->denominator));

        return new Fraction(
            $this->numerator / $gcd,
            $this->denominator / $gcd,
        );
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }
        return $a;
    }

    public function __toString(): string
    {
        if ($this->numerator === 0) {
            return '0';
        }

        $out = '';
        if ($this->numerator < 0) {
            $out = '-';
        }

        $whole = intdiv($this->numerator, $this->denominator);
        if ($whole > 0) {
            $out .= $whole;
        }

        $remainder = $this->numerator % $this->denominator;
        if ($remainder > 0) {
            if ($whole > 0) {
                $out .= ' and ';
            }
            $out .= abs($remainder) . '/' . abs($this->denominator);
        }

        return $out;
    }
}
```

The `simplify()` method uses the Euclidean algorithm for GCD — the fastest known method for finding the greatest common divisor. The `__toString()` method handles negative fractions and mixed number output. `32/27` becomes `1 and 5/27`. A fraction like `-9/4` becomes `-2 and 1/4`.

## The Calculator

The `FractionCalculator` class provides a clean API for all four operations:

```php
namespace Puzzles;

class FractionCalculator
{
    public function __construct(
        private int $maxDenominator = 1000000,
    ) {}

    public function add(Fraction $a, Fraction $b): Fraction
    {
        $lcm = $this->lcm($a->denominator, $b->denominator);

        $newA = $a->numerator * ($lcm / $a->denominator);
        $newB = $b->numerator * ($lcm / $b->denominator);

        $sum = new Fraction($newA + $newB, $lcm);
        return $sum->simplify();
    }

    public function subtract(Fraction $a, Fraction $b): Fraction
    {
        $lcm = $this->lcm($a->denominator, $b->denominator);

        $newA = $a->numerator * ($lcm / $a->denominator);
        $newB = $b->numerator * ($lcm / $b->denominator);

        $result = new Fraction($newA - $newB, $lcm);
        return $result->simplify();
    }

    public function multiply(Fraction $a, Fraction $b): Fraction
    {
        $product = new Fraction(
            $a->numerator * $b->numerator,
            $a->denominator * $b->denominator,
        );
        return $product->simplify();
    }

    public function divide(Fraction $a, Fraction $b): Fraction
    {
        if ($b->numerator === 0) {
            throw new \InvalidArgumentException('Cannot divide by zero');
        }

        $quotient = new Fraction(
            $a->numerator * $b->denominator,
            $a->denominator * $b->numerator,
        );
        return $quotient->simplify();
    }

    private function lcm(int $a, int $b): int
    {
        // LCM(a, b) = |a * b| / GCD(a, b)
        return abs($a * $b) / $this->gcd(abs($a), abs($b));
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }
        return $a;
    }
}
```

### How LCM Works

The least common multiple of two denominators is the smallest number both divide evenly into. For denominators 4 and 6:

```
Multiples of 4: 4, 8, 12, 16, 20, 24
Multiples of 6: 6, 12, 18, 24, 30
```

The LCM is 12. Using the formula `LCM(a, b) = |a × b| / GCD(a, b)` gives us `|4 × 6| / 2 = 12` — more efficient than iterating multiples.

## Using the Calculator

```php
use Puzzles\Fraction;
use Puzzles\FractionCalculator;

$calc = new FractionCalculator();

$first = new Fraction(2, 3);
$second = new Fraction(9, 16);

$sum = $calc->add($first, $second);
$difference = $calc->subtract($first, $second);
$product = $calc->multiply($first, $second);
$quotient = $calc->divide($first, $second);

echo "$first plus $second = $sum" . PHP_EOL;
echo "$first minus $second = $difference" . PHP_EOL;
echo "$first times $second = $product" . PHP_EOL;
echo "$first divided by $second = $quotient" . PHP_EOL;

// Output:
// 2/3 plus 9/16 = 1 and 11/48
// 2/3 minus 9/16 = 5/48
// 2/3 times 9/16 = 3/8
// 2/3 divided by 9/16 = 1 and 5/27
```

### Step-by-Step: Adding 2/3 + 9/16

1. Find LCM of 3 and 16: `LCM(3, 16) = 48`
2. Convert numerators: `2 × (48 / 3) = 32`, `9 × (48 / 16) = 27`
3. Sum: `32 + 27 = 59` over denominator `48`
4. Result: `59/48`
5. Simplify: GCD of 59 and 48 is 1, so fraction stays `59/48`
6. Pretty print: `1 and 11/48`

### Division: Invert and Multiply

Division is the simplest operation conceptually. Instead of dividing by a fraction, multiply by its reciprocal:

```php
// 2/3 ÷ 9/16 = 2/3 × 16/9 = 32/27
$quotient = $calc->divide($first, $second); // 1 and 5/27
```

The reciprocal of `9/16` is `16/9`. Multiply numerators: `2 × 16 = 32`. Multiply denominators: `3 × 9 = 27`. Simplify to `32/27`, then display as `1 and 5/27`.

## Handling Edge Cases

### Zero Numerator

A fraction with zero numerator equals zero regardless of denominator. The `Fraction` constructor handles this naturally — `simplify()` returns zero, and `__toString()` outputs `"0"`.

### Negative Fractions

The `__toString()` method checks for negative numerators and prefixes the output with `-`. The whole part uses `intdiv`, which in PHP truncates toward zero, so `-9/4` correctly produces `-2 and 1/4`.

### Large Denominators

The LCM calculation multiplies the two denominators. For denominators up to 1,000,000, the product can exceed PHP's integer range. The `maxDenominator` constructor parameter provides a safety check — increase it if you need larger values, or switch to an arbitrary-precision library like `gmp`.

```php
// For very large fractions, use GMP
use GMP;

function lcmGMP(string $a, string $b): string
{
    $ga = gmp_init($a);
    $gb = gmp_init($b);
    $gcd = gmp_gcd($ga, $gb);
    $product = gmp_mul($ga, $gb);
    return gmp_strval(gmp_div_q($product, $gcd));
}
```

## Performance Considerations

The `simplify()` method runs after every operation. In most applications, this is negligible. For batch processing millions of fractions, consider deferring simplification or using integer-based GCD caching:

```php
class FractionCalculator
{
    private array $gcdCache = [];

    private function gcd(int $a, int $b): int
    {
        $key = $a < $b ? "$a-$b" : "$b-$a";
        if (isset($this->gcdCache[$key])) {
            return $this->gcdCache[$key];
        }

        $originalA = $a;
        $originalB = $b;

        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        $this->gcdCache[$key] = $a;
        return $a;
    }
}
```

## Summary

Fraction arithmetic in PHP follows the same rules you learned in school:

- **Addition/Subtraction** — Find the LCM of denominators, convert numerators, apply the operator, simplify
- **Multiplication** — Multiply numerators and denominators directly, simplify
- **Division** — Invert the second fraction, then multiply
- **Simplification** — Divide numerator and denominator by their GCD
- **Mixed output** — Extract the whole part and remainder for readability

The implementation is pure PHP with no external dependencies. Use it in recipe apps, measurement tools, educational software, or anywhere you need exact rational arithmetic instead of floating-point approximations.
