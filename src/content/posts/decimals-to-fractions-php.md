---
title: "Converting Decimals to Fractions in PHP - Algorithm Solutions"
description: "Learn how to convert decimal numbers to fractions in PHP. Covers GCD algorithms, handling repeating decimals, floating-point precision, and building a Fraction class."
pubDate: "2022-08-24 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Math", "Fractions", "Algorithms", "Precision"]
selected: false
---

Given any floating-point number, can you write a function that returns the number as a fraction in its simplest form? Convert 0.8 to "4/5", and 0.025 to "1/40". This problem touches on fundamental math concepts that every PHP developer should understand: floating-point representation, the greatest common divisor, and algorithm design.

## Understanding Fractions

Fractions have two parts: a numerator (above the divider) and a denominator (below it). The numerator counts the equal parts of the whole, and the denominator is the total number of parts available.

```
  7  (numerator)
-----
 12  (denominator)
```

Seven units out of twelve available units.

Decimals are another way of writing fractions. The denominator is always a power of ten (10, 100, 1000), determined by how many digits appear after the decimal point. The digits after the decimal form the numerator.

`0.025` is equivalent to:

```
  25
-----
1000
```

Reduced to lowest terms, that's `1/40`.

The solution breaks down into two steps:

1. Convert the decimal to a numerator and denominator
2. Reduce the fraction to its lowest terms

For simplicity, we'll work with numbers between 0 and 1. Extending the solution to handle values greater than one is a matter of splitting the whole number and fractional parts.

## Step 1: Decimal to Numerator and Denominator

PHP's type juggling makes this straightforward. Treat the decimal as a string and count the characters after the decimal point:

```php
$input = 0.8;
$parts = explode('.', (string) $input);
$count = strlen($parts[1]);

var_dump($count); // int(1)
```

The numerator is the digits after the decimal point. The denominator is 10 raised to the power of the digit count:

```php
$numerator = (int) $parts[1];
$denominator = 10 ** $count;

var_dump($numerator);   // int(8)
var_dump($denominator); // int(10)
```

What about trailing zeros? `0.4500` as a float cleans itself up:

```php
$input = 0.4500;
$parts = explode('.', (string) $input);

var_dump($parts[1]); // string(2) "45"
```

PHP's float parsing drops the trailing zeros. But if the input comes as a string:

```php
$input = "0.4500";
$parts = explode('.', $input);

var_dump($parts[1]); // string(4) "4500"
```

Using a type hint to coerce `float` inputs solves the problem:

```php
class Fraction
{
    public function __construct(
        public int $numerator,
        public int $denominator,
    ) {}

    public static function fromFloat(float $input): self
    {
        $parts = explode('.', (string) $input);
        $count = strlen($parts[1]);

        return new self(
            numerator: (int) $parts[1],
            denominator: 10 ** $count,
        );
    }
}
```

PHP 8's property promotion and named arguments make the class clean and readable:

```php
$frac = Fraction::fromFloat(0.4500);
var_dump($frac);

// object(Fraction)#1 (2) {
//   ["numerator"]=>   int(45)
//   ["denominator"]=> int(100)
// }
```

## Step 2: Simplifying the Fraction

To simplify a fraction, find the Greatest Common Divisor (GCD)—the largest factor that both the numerator and denominator share.

First, find all factors of each number:

```php
/**
 * @return int[]
 */
function findFactors(int $product): array
{
    $factors = range(1, (int) sqrt($product));
    $flat = [];

    array_walk(
        $factors,
        function (int $factor) use ($product, &$flat): void {
            if ($product % $factor === 0) {
                $flat[] = $factor;
                $flat[] = $product / $factor;
            }
        }
    );

    sort($flat);
    return array_unique($flat);
}
```

This function iterates up to the square root of the number. For each factor found, it records both the factor and its complement. This is significantly faster than checking every number from 1 to N.

Find the common factors and extract the largest:

```php
$numeratorFactors = findFactors($numerator);
$denominatorFactors = findFactors($denominator);
$commonFactors = array_intersect(
    $numeratorFactors,
    $denominatorFactors
);
$GCF = array_pop($commonFactors);
```

For `0.8`, the numerator is 8 and the denominator is 10. Common factors are 1 and 2. The GCD is 2.

If the GCD is greater than 1, divide both parts by it:

```php
if ($GCF > 1) {
    $numerator /= $GCF;
    $denominator /= $GCF;
}

var_dump($numerator, $denominator);
// int(4)
// int(5)
```

## The Complete Fraction Class

```php
<?php

namespace Puzzles;

class Fraction
{
    public function __construct(
        public int $numerator,
        public int $denominator,
    ) {}

    public static function fromFloat(float $input): self
    {
        $parts = explode('.', (string) $input);
        $count = strlen($parts[1]);

        return new self(
            numerator: (int) $parts[1],
            denominator: 10 ** $count,
        );
    }

    public function __toString(): string
    {
        return $this->numerator . '/' . $this->denominator;
    }

    public function simplify(): self
    {
        $numeratorFactors = $this->findFactors($this->numerator);
        $denominatorFactors = $this->findFactors($this->denominator);

        $commonFactors = array_intersect(
            $numeratorFactors,
            $denominatorFactors
        );
        $gcf = array_pop($commonFactors);

        if ($gcf > 1) {
            return new self(
                $this->numerator / $gcf,
                $this->denominator / $gcf,
            );
        }

        return $this;
    }

    /**
     * @return int[]
     */
    private function findFactors(int $product): array
    {
        $factors = range(1, (int) sqrt($product));
        $flat = [];

        array_walk(
            $factors,
            function (int $factor) use ($product, &$flat): void {
                if ($product % $factor === 0) {
                    $flat[] = $factor;
                    $flat[] = $product / $factor;
                }
            }
        );

        sort($flat);
        return array_unique($flat);
    }
}
```

Usage:

```php
$frac = Fraction::fromFloat(0.80);
$simpl = $frac->simplify();

echo $frac . ' simplifies to ' . $simpl;
// 8/10 simplifies to 4/5
```

## Using Euclid's Algorithm for GCD

The `findFactors` approach works but is overkill. Euclid's algorithm computes the GCD directly and is dramatically faster:

```php
function gcd(int $a, int $b): int
{
    while ($b !== 0) {
        $temp = $b;
        $b = $a % $b;
        $a = $temp;
    }

    return $a;
}
```

Simplify a fraction in one step:

```php
class Fraction
{
    public function simplify(): self
    {
        $g = $this->gcd($this->numerator, $this->denominator);

        if ($g > 1) {
            return new self(
                $this->numerator / $g,
                $this->denominator / $g,
            );
        }

        return $this;
    }

    private static function gcd(int $a, int $b): int
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

Euclid's algorithm is O(log(min(a, b))). The factor-finding approach is O(sqrt(N)). For large denominators, the difference is enormous.

## Handling Repeating Decimals

The current solution doesn't handle repeating decimals. `1/3` is `0.3333...`—the 3 repeats infinitely. PHP's float representation truncates this:

```php
$frac = Fraction::fromFloat(1/3);
$simpl = $frac->simplify();

echo $frac . ' simplifies to ' . $simpl;
// 33333333333333/100000000000000 simplifies to
// 33333333333333/100000000000000
```

The result is correct mathematically—it represents the floating-point approximation accurately—but it's not what a human expects.

Detecting repeating decimals requires a different approach. One method is:

1. Record each remainder during long division
2. If a remainder repeats, the digits between the two occurrences repeat
3. Extract the repeating block and represent it as a fraction with 9s in the denominator

For example, `0.333...` has a repeating block of length 1: `3/9 = 1/3`.

## Floating-Point Precision Caveats

PHP's floats follow IEEE 754 double precision. Some decimal numbers cannot be represented exactly:

```php
printf('%.20f', 0.1 + 0.2);
// 0.30000000000000004441
```

This means `Fraction::fromFloat(0.1 + 0.2)` produces a fraction close to but not exactly `3/10`. The `fromFloat()` method accepts float input specifically to avoid string-based precision issues, but the underlying IEEE 754 limitation remains.

For applications requiring exact decimal arithmetic—financial calculations, precise measurements—consider using PHP's `bcmath` or `gmp` extensions:

```php
function decimalToFraction(string $decimal, int $precision = 10): string
{
    if (!str_contains($decimal, '.')) {
        return $decimal . '/1';
    }

    $parts = explode('.', $decimal);
    $numerator = (int) $parts[1];
    $denominator = 10 ** strlen($parts[1]);
    $g = gcd($numerator, $denominator);

    $whole = (int) $parts[0];
    $num = $numerator / $g + $whole * ($denominator / $g);

    return $num . '/' . ($denominator / $g);
}
```

## Performance Considerations

Euclid's GCD algorithm is efficient for most cases. For very large numbers (hundreds of digits), the GMP extension's `gmp_gcd()` function is faster:

```php
function gcdGMP(string $a, string $b): string
{
    return gmp_strval(gmp_gcd($a, $b));
}
```

The factor-finding approach from earlier is useful for understanding the math but should not be used in production. Always prefer Euclid's algorithm.

## Conclusion

Converting decimals to fractions in PHP is a two-step process: express the decimal as a numerator and denominator based on a power of ten, then reduce using the GCD. Euclid's algorithm makes the reduction step efficient.

PHP 8.1's constructor property promotion and named arguments make the Fraction class clean and expressive. Handle repeating decimals with long division and remainder tracking for a complete solution.
