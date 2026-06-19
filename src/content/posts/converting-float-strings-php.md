---
title: "Converting Float Strings in PHP - Precision and Pitfalls"
description: "Learn the hidden pitfalls of converting float strings to integers in PHP. Covers floating-point precision, locale issues, BCMath for accuracy, and safe comparison techniques."
pubDate: "2022-10-22 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Floats", "Precision", "Type Conversion", "Math"]
selected: false
---

Converting data is never as straightforward as we'd expect. Users enter data incorrectly. Systems format values differently. And sometimes — especially with floating-point numbers — the computer itself gets it wrong. When you're dealing with currency, a single lost penny adds up fast.

## The Problem: Currency Strings to Pennies

Suppose a backend API returns prices as formatted strings: `$1,234.56`. Your PHP application stores prices as integers representing pennies. Converting decimal values to integers should be easy with `round()`, right?

Let's write a function that takes a currency string for dollars and converts it to pennies. The string may be formatted as `$100` or `$100.00`. The dollar sign is optional. We need to handle: `"105.00"`, `"$ 128.76"`, `"$2,487.47"`, `"11,135.65"`, and `"$71,135.71"`.

## A Naive Approach

Strip the dollar sign and commas, cast to float, multiply by 100, cast to int:

```php
$price = (float) "105.00";
$price = $price * 100;
var_dump($price); // float(10500)
$price = (int) $price;
var_dump($price); // int(10500)
```

That works for clean values. Let's build a real handler:

```php
function priceToFloat(string $input): float
{
    $output = str_replace(['$', ',', ' '], '', $input);
    return (float) $output;
}

function floatToPennies(float $input): int
{
    return (int) ($input * 100);
}
```

Feed it our test data:

```php
$raw = ["105.00", "$ 128.76", "$2,487.47", "11,135.65", "$71,135.71"];
$floats = array_map('priceToFloat', $raw);

$pennies = array_map('floatToPennies', $floats);
$result = array_combine($floats, $pennies);
var_dump($result);
```

Output:

```
array(5) {
  [105]=>    int(10500)
  [128.76]=> int(12876)
  [2487.47]=> int(248746)
  [11135.65]=> int(1113565)
  [71135.71]=> int(7113571)
}
```

Spot the bug. `2487.47` became `248746` instead of `248747`. One penny missing. Reconciliation teams will hunt this down on every invoice.

## What's Going On?

```php
$x = 100 * 2487.47;
var_dump($x); // float(248746.99999999997)
```

Computers work in binary. Floating-point values work in base 10. There's a fundamental mismatch in representational accuracy. `2487.47 * 100` yields `248746.99999999997`, and truncation to int loses the fraction.

The fix is to round before casting:

```php
function floatToPennies(float $input): int
{
    return (int) (round($input * 100));
}
```

With `round()`, all conversions produce correct penny values.

## Use BCMath for Precision

`round()` works for simple multiplication by a constant, but it won't save you from every floating-point trap. For robust precision math, use the BCMath extension:

```php
function floatToPennies(float $input): int
{
    return (int) bcmul((string) $input, '100');
}
```

BCMath works with string representations to avoid floating-point rounding entirely. Pass `$input` as a string to prevent the float from introducing errors before BCMath even sees it.

```php
var_dump(bcmul('2487.47', '100')); // string(7) "248747"
```

The extension is included with most PHP installations. If it's missing, install `phpseclib/bcmath_compat` via Composer for a userland shim:

```bash
$ composer require phpseclib/bcmath_compat
```

## Comparing Floats Safely

Never compare floats with `==`:

```php
var_dump(0.1 + 0.2 == 0.3); // bool(false)
```

Instead, compare with a tolerance (epsilon):

```php
function floatEquals(float $a, float $b, float $epsilon = 1.0e-9): bool
{
    return abs($a - $b) < $epsilon;
}

var_dump(floatEquals(0.1 + 0.2, 0.3)); // bool(true)
```

For currency, avoid floats entirely. Store integers (pennies, cents) or use an abstraction like:

```php
class Money
{
    public function __construct(
        private readonly int $pennies
    ) {}

    public static function fromDollars(float $amount): self
    {
        return new self((int) round($amount * 100));
    }

    public function toDollars(): float
    {
        return $this->pennies / 100;
    }

    public function add(self $other): self
    {
        return new self($this->pennies + $other->pennies);
    }
}
```

## Formatting Output

Formatting floats for display introduces locale issues. A value like `1234.56` displays as `1 234,56` in French locale because the comma and period swap roles:

```php
setlocale(LC_ALL, 'fr_FR.UTF-8');
echo number_format(1234.56, 2); // 1 234,56
```

Be explicit about locale when formatting currency:

```php
$amount = 1234.56;
echo '$' . number_format($amount, 2, '.', ','); // $1,234.56
```

Or use PHP's `NumberFormatter` from the intl extension:

```php
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
echo $fmt->formatCurrency(1234.56, 'USD'); // $1,234.56
```

## In Review

Floating-point math in PHP (and every other language) has well-known precision traps. When converting currency strings to integers:

1. Remove formatting characters before parsing
2. Always round before casting float to int
3. Use BCMath for critical precision operations
4. Store monetary values as integers (pennies/cents)
5. Be explicit about locale when formatting output

One lost penny is a bug. A thousand lost pennies is a class-action lawsuit. Handle your floats with care.
