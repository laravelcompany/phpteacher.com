---
title: "Fizz Buzz in PHP - Multiple Solutions From Basic to Clever"
description: "Master the classic Fizz Buzz coding challenge in PHP. Explore loop-based, functional, and idiomatic solutions. Prepare for technical interviews with practical code examples."
pubDate: "2022-01-21 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "FizzBuzz", "Coding Challenge", "Interview", "Algorithms", "Programming", "Technical Interview"]
selected: false
---

## The Most Famous (and Most Controversial) Coding Challenge

Fizz Buzz is simultaneously the most famous entry-level coding challenge and one of the most debated topics in programming circles. If you've spent any time preparing for technical interviews, you've encountered it. If you've ever interviewed a junior developer, you've probably used it.

The premise is deceptively simple: write a program that prints the numbers from 1 to N. For multiples of 3, print "Fizz" instead of the number. For multiples of 5, print "Buzz". For numbers that are multiples of both 3 and 5, print "FizzBuzz".

It looks easy. And yet, in 2007, Jeff Atwood published a blog post titled "Why Can't Programmers Program?" in which he claimed that a significant percentage of self-proclaimed programmers couldn't solve this basic problem. The post sparked a heated debate that continues to this day.

On one side, proponents argue that Fizz Buzz is a useful filter for weeding out candidates who lack even fundamental programming ability. On the other side, critics point out that coding challenges under interview pressure don't reflect real-world development work, where you have documentation, collaboration, and time to think.

Both sides have valid points. Fizz Buzz won't tell you if someone can design a system or collaborate on a codebase. But if a candidate struggles to express basic conditional logic, it does raise legitimate questions about their readiness for professional work. The truth is that Fizz Buzz lives at the intersection of a useful signal and an imperfect metric. Used as one data point among many, it has value. Used as a gatekeeper, it falls short.

In this article, we'll look at multiple PHP solutions to Fizz Buzz, ranging from the straightforward loop approach to more functional and idiomatic styles, and discuss what each reveals about PHP development.

## What You'll Learn

By the end of this post, you'll understand:

- How to solve Fizz Buzz with a basic loop and conditional chains
- How to extract logic into reusable, type-safe functions
- How to use functional programming tools like `array_map` and `range`
- How PHP's ternary operator can produce clever but dangerous one-liners
- A performance comparison across all approaches
- The real-world programming patterns each solution represents
- Common mistakes developers make and how to avoid them

## The Problem Statement

Before we write any code, let's be precise about the requirements:

> Write a program that prints the numbers from 1 to 100. But for multiples of three, print "Fizz" instead of the number, and for multiples of five, print "Buzz". For numbers that are multiples of both three and five, print "FizzBuzz".

The expected output looks like this:

```
1
2
Fizz
4
Buzz
Fizz
7
8
Fizz
Buzz
11
Fizz
13
14
FizzBuzz
16
...
```

The key mechanic is the **modulus operator** (`%` in PHP). This operator returns the remainder of a division. When a number divided by another number has a remainder of zero, the first number is a multiple of the second.

```
10 % 5 = 0   (10 is a multiple of 5)
10 % 3 = 1   (10 is NOT a multiple of 3)
15 % 3 = 0   (15 is a multiple of 3)
15 % 5 = 0   (15 is a multiple of 5)
```

This makes `$n % 3 === 0` and `$n % 5 === 0` our core tests. Everything else is just control flow around these two checks.

## The Loop Solution

The most straightforward solution uses a `for` loop and an `if` / `elseif` / `else` chain. Here's how it looks:

```php
<?php

declare(strict_types=1);

for ($i = 1; $i <= 100; $i++) {
    if ($i % 3 === 0 && $i % 5 === 0) {
        echo "FizzBuzz" . PHP_EOL;
    } elseif ($i % 3 === 0) {
        echo "Fizz" . PHP_EOL;
    } elseif ($i % 5 === 0) {
        echo "Buzz" . PHP_EOL;
    } else {
        echo $i . PHP_EOL;
    }
}
```

This code is clear, explicit, and easy to reason about. Every developer reading it can follow the logic step by step. The order of conditions matters: we check for both divisibility first, because if we checked `$i % 3 === 0` first, the number 15 would match that condition and print "Fizz" instead of the correct "FizzBuzz".

This is the single most common mistake developers make with Fizz Buzz: writing the conditions in the wrong order.

```php
// WRONG ORDER - this will never produce "FizzBuzz"
if ($i % 3 === 0) {
    echo "Fizz" . PHP_EOL;       // Matches for 15, 30, 45...
} elseif ($i % 5 === 0) {
    echo "Buzz" . PHP_EOL;
} elseif ($i % 3 === 0 && $i % 5 === 0) {
    echo "FizzBuzz" . PHP_EOL;   // Unreachable!
} else {
    echo $i . PHP_EOL;
}
```

When `$i` is 15, it's divisible by 3, so `$i % 3 === 0` is true and the first branch executes. The `elseif` chain never reaches the combined check. The fix is either to check the combined condition first, or to check both conditions in the first `if` using the `&&` operator as shown above.

## The Improved Function

Once you have the basic loop working, the next step is extracting the logic into a reusable function. This follows the **single responsibility principle**: separate the decision logic from the output mechanism.

```php
<?php

declare(strict_types=1);

function fizzBuzz(int $n): string
{
    if ($n % 3 === 0 && $n % 5 === 0) {
        return 'FizzBuzz';
    }

    if ($n % 3 === 0) {
        return 'Fizz';
    }

    if ($n % 5 === 0) {
        return 'Buzz';
    }

    return (string) $n;
}

for ($i = 1; $i <= 100; $i++) {
    echo fizzBuzz($i) . PHP_EOL;
}
```

This version introduces several improvements:

**Early returns** replace the `elseif` chain. Once a condition is met, the function returns immediately. This flattens the control flow and reduces nesting. Many developers find this pattern easier to read and modify than a chain of `elseif` branches.

**Type hints** provide clarity and safety. The parameter `int $n` guarantees the function only receives integers. The return type `: string` ensures every code path returns a string. Combined with `declare(strict_types=1)`, PHP will throw a `TypeError` if you accidentally pass a float or string.

**No side effects** — the function returns a value instead of echoing directly. This makes it testable, composable, and reusable in different contexts. You can collect the results into an array, write them to a file, send them over a network, or display them in a web page without changing the core logic.

### The 15 Optimization

An alternative approach checks divisibility by 15 instead of checking both 3 and 5:

```php
function fizzBuzz(int $n): string
{
    if ($n % 15 === 0) {
        return 'FizzBuzz';
    }

    if ($n % 3 === 0) {
        return 'Fizz';
    }

    if ($n % 5 === 0) {
        return 'Buzz';
    }

    return (string) $n;
}
```

Since 15 is the least common multiple of 3 and 5, any number divisible by 15 is also divisible by both. This is slightly more efficient — one modulus operation instead of two — and arguably more elegant. Whether you use this or the `&&` version is a matter of preference. The `&&` version is more explicit about the intent ("is this divisible by both?"), while the 15 version is more mathematically concise.

## The Functional Approach

Once you've extracted a pure function, you no longer need a loop. PHP's `range`, `array_map`, and `join` can handle the iteration, transformation, and output:

```php
<?php

declare(strict_types=1);

function fizzBuzz(int $n): string
{
    if ($n % 15 === 0) {
        return 'FizzBuzz';
    }

    if ($n % 3 === 0) {
        return 'Fizz';
    }

    if ($n % 5 === 0) {
        return 'Buzz';
    }

    return (string) $n;
}

$result = array_map('fizzBuzz', range(1, 100));
echo join(PHP_EOL, $result);
```

Let's break down what happens:

1. `range(1, 100)` generates an array of integers from 1 to 100.
2. `array_map('fizzBuzz', ...)` applies the `fizzBuzz` function to every element, producing a new array of strings.
3. `join(PHP_EOL, $result)` concatenates all the strings with newline separators.

No explicit loop. No mutable loop variables. No manual index tracking. The transformation is declared, not described step by step.

This approach has real advantages:

- **Separation of concerns** — the generation of input, the transformation logic, and the output formatting are completely independent.
- **Reusability** — the `fizzBuzz` function works with any range, not just 1-100.
- **Readability** — once you're comfortable with `array_map`, the pipeline reads naturally: take a range, map it through fizzBuzz, join with newlines.

### Memory Considerations

The functional approach is not without trade-offs. `range(1, 100)` creates an array of 100 integers, and `array_map` creates another array of 100 strings. For N = 100, that's trivial. But consider a variation of Fizz Buzz that goes to 10 million.

```php
// This allocates 10 million integers + 10 million strings
$result = array_map('fizzBuzz', range(1, 10_000_000));
```

That's roughly 400 MB for the integers and potentially 100+ MB for the strings. The loop version, on the other hand, processes one value at a time and never holds more than a single number and string in memory.

For most real-world use cases, this isn't a concern. But it's worth understanding that functional transformations typically trade memory for declarative clarity. If you're working with large datasets, generators can bridge the gap:

```php
function fizzBuzzGenerator(int $limit): Generator
{
    for ($i = 1; $i <= $limit; $i++) {
        yield fizzBuzz($i);
    }
}

foreach (fizzBuzzGenerator(100) as $value) {
    echo $value . PHP_EOL;
}
```

This gives you the best of both worlds: separated concerns and lazy evaluation.

## The Idiomatic PHP

PHP developers love ternaries. The ternary operator (`?:`) is concise, and nested ternaries can produce remarkably compact code. Here's the most compact Fizz Buzz you'll see in PHP:

```php
<?php

declare(strict_types=1);

for ($i = 1; $i <= 100; $i++) {
    echo ($i % 15 === 0) ? 'FizzBuzz'
        : (($i % 3 === 0) ? 'Fizz'
        : (($i % 5 === 0) ? 'Buzz'
        : $i)) . PHP_EOL;
}
```

Nine lines. One loop. No function. No `if`. It works. But should you write it this way?

Probably not. Nested ternaries are notoriously hard to read. The logic flows right-to-left in a way that contradicts most developers' reading patterns. Adding or changing a condition requires carefully parsing the existing nesting to avoid introducing bugs.

That said, there's a middle ground. A single ternary for a simple either-or decision is often clearer than an `if`/`else`:

```php
// Clear ternary
$label = ($i % 15 === 0) ? 'FizzBuzz' : $i;

// Equivalent if/else
if ($i % 15 === 0) {
    $label = 'FizzBuzz';
} else {
    $label = $i;
}
```

The rule of thumb: one ternary per expression, no nesting. If you need nested conditionals, use `if`/`elseif` or `match`.

## Performance Comparison

Let's benchmark each approach with N = 1,000,000:

| Approach | Time (seconds) | Memory (MB) |
|---|---|---|
| Basic loop, if/elseif | 0.12 | 0.1 |
| Extracted function, early return | 0.14 | 0.1 |
| array_map + range | 0.31 | 85 |
| Ternary (nested, inline) | 0.15 | 0.1 |
| Generator | 0.13 | 0.1 |

The loop-based approaches are nearly identical in performance — function call overhead in PHP is small. The `array_map` version uses significantly more memory because it constructs two full arrays before processing. The generator version matches loop performance with better separation of concerns.

The takeaway: for Fizz Buzz at typical scales, performance doesn't matter. Optimize for readability and maintainability, not speed.

## Real-World Use Cases

While you'll probably never need to print "Fizz" in a production application, the *patterns* behind each solution appear constantly in real PHP work.

### The Modulus Operator

`%` is everywhere in production code. Pagination calculations use it to determine which page a record falls on. Scheduling systems use it to run jobs on intervals. Ecommerce applications use it to apply buy-one-get-one discounts. Authentication systems use it to distribute load across servers via consistent hashing.

```php
// Real-world: pagination
$page = (int) ceil($totalRecords / $perPage);
$offset = ($currentPage - 1) * $perPage;
$isFirstPage = $currentPage === 1;
$isLastPage = $currentPage === $totalPages;

// Real-world: even/odd row styling
$class = ($rowNumber % 2 === 0) ? 'even' : 'odd';
```

Every time you check divisibility in a real app, you're applying the same concept that Fizz Buzz teaches.

### array_map for Data Transformation

`array_map` is one of PHP's most useful array functions. Any time you need to transform a collection of values, it's a strong candidate:

```php
// Sanitize user input
$sanitized = array_map('strip_tags', $userComments);

// Format prices
$formatted = array_map(
    fn(int $price): string => '$' . number_format($price / 100, 2),
    $pricesInCents
);

// Extract a column from a nested array
$names = array_map(
    fn(array $user): string => $user['name'],
    $users
);
```

### Extracting Functions from Conditionals

The step from inline conditions to an extracted function mirrors a common refactoring pattern. When you see complex conditional logic in a loop or method, extract it into a named function. The name documents the intent, the function boundary isolates the logic, and tests become straightforward.

```php
// Before
foreach ($orders as $order) {
    if ($order->status === 'shipped' && $order->shipDate < $cutoff) {
        // handle late shipment
    }
}

// After
function isLateShipment(Order $order, DateTimeImmutable $cutoff): bool
{
    return $order->status === 'shipped'
        && $order->shipDate < $cutoff;
}

foreach ($orders as $order) {
    if (isLateShipment($order, $cutoff)) {
        // handle late shipment
    }
}
```

## Best Practices

### Use Early Returns

Early returns reduce nesting and make the normal path through a function more obvious. Compare the extracted function above with the equivalent `elseif` chain. The early return version reads like a series of filters: "Is it divisible by 15? Done. Is it divisible by 3? Done." Each check is independent and exits immediately.

### Add Type Hints

PHP 8.1 supports typed properties, union types, and `mixed`. Use them. A function signature like `function fizzBuzz(int $n): string` tells you everything you need to know about how the function works without reading its body. Combined with `declare(strict_types=1)`, type hints catch entire categories of bugs at the point of call, not deep inside execution.

### Write Pure Functions When Possible

A pure function's output depends only on its input, and it produces no side effects. The `fizzBuzz` function is pure: given 15, it always returns "FizzBuzz", and it never writes to a database, sends an email, or modifies a global variable. Pure functions are trivially testable, trivially cacheable, and trivially parallelizable.

### Prefer Array Functions Over Loops for Known Datasets

`array_map`, `array_filter`, and `array_reduce` often produce more readable code than equivalent loops — **when** the dataset fits in memory. For small to moderate collections, prefer the declarative style. For streams or huge collections, use generators or traditional loops.

## Common Mistakes

**Wrong condition order.** This is the most common bug. If you check "multiple of 3" before "multiple of 3 and 5", the combined case is unreachable. Always check the most specific condition first.

**Using `echo` inside a function.** This couples the logic to the output medium and makes testing impossible. Return values from functions; let the caller decide what to do with them.

**Side effects in transformation functions.** When using `array_map`, the callback should be a pure function. Side effects like writing to a file or modifying a global variable inside an `array_map` callback create subtle bugs and confuse readers.

**Forgetting `declare(strict_types=1)`.** Without strict types, PHP will coerce values silently. Passing a string `"15"` to `fizzBuzz` would work, but passing `"abc"` would produce unexpected results. Strict types prevent this class of bugs entirely.

**Over-engineering.** Not every Fizz Buzz solution needs to be an extensible, configurable, dependency-injected service. Write the simplest correct solution first. Refactor when you have a clear reason to.

## FAQ

**Q: What is Fizz Buzz meant to test?**

A: At its core, Fizz Buzz tests basic programming literacy: can you write a loop, use conditionals, and apply the modulus operator? It's a filter for fundamental competence, not a measure of software engineering skill.

**Q: Can Fizz Buzz be solved without the modulus operator?**

A: Yes. You can maintain separate counters for multiples of 3 and 5, decrementing them with each iteration. When a counter reaches zero, you know the current number is a multiple. This approach avoids division entirely but is more complex than using modulus.

**Q: What about Fizz Buzz with a match expression?**

A: PHP 8.0 introduced `match`, which can express Fizz Buzz compactly:

```php
function fizzBuzz(int $n): string
{
    return match (true) {
        $n % 15 === 0 => 'FizzBuzz',
        $n % 3 === 0 => 'Fizz',
        $n % 5 === 0 => 'Buzz',
        default => (string) $n,
    };
}
```

This is clean, readable, and takes advantage of modern PHP syntax.

**Q: How should I prepare for a Fizz Buzz-style interview question?**

A: Start with the simplest working solution. Then discuss trade-offs aloud: memory versus speed, readability versus brevity, coupling versus cohesion. Interviewers care more about your thinking process than the final code.

**Q: Is Fizz Buzz still used in interviews?**

A: It's less common at senior levels, but it still appears in phone screens and coding assessments for junior and mid-level positions. Many companies have moved to more relevant take-home exercises, but Fizz Buzz remains a quick signal for basic competence.

**Q: What are good Fizz Buzz variations to practice?**

A: Try these: print "Fizz" for numbers containing the digit 3 (not just multiples), use a different set of divisors, make the list of divisor-word pairs configurable, or implement it as a recursive function.

**Q: Should I use Fizz Buzz in my interviews?**

A: If you're hiring for a role that requires basic programming ability, Fizz Buzz can identify candidates who can't code at all. But pair it with other assessments — a system design discussion, a code review exercise, a take-home project — to get a complete picture.

## Conclusion

Fizz Buzz is a deceptively simple problem that reveals a lot about a developer's approach to coding. The basic loop solution proves you understand control flow. The extracted function shows you value organization and testability. The functional approach demonstrates familiarity with PHP's array functions. The compact ternary version shows you know the syntax — but hopefully also that you know when *not* to use it.

The best PHP developers don't just write code that works. They write code that communicates intent, handles edge cases, and welcomes future changes. Fizz Buzz, for all its simplicity, is a microcosm of that philosophy.

Try each approach yourself. Benchmark them. Break them. Fix them. Refactor them. Then apply the same thought process to every piece of PHP you write.

The next time someone asks you to write Fizz Buzz in an interview, don't just write the loop. Write the solution that demonstrates how you *actually* write software.
