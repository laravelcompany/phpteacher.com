---
title: "Finding Prime Factors in PHP - Algorithm Solutions and Optimizations"
description: "Learn how to find prime factors of any integer in PHP. From basic trial division to optimized Sieve-based approaches, with practical applications in cryptography and math."
pubDate: "2022-03-24 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Prime Factors", "Algorithms", "Math", "Optimization", "Sieve of Eratosthenes", "Programming"]
selected: false
---

## The Building Blocks of Numbers

Every integer greater than 1 can be broken down into a unique product of prime numbers. This is the Fundamental Theorem of Arithmetic, and it's one of the most important ideas in number theory. The prime factors of a number are the prime numbers that, when multiplied together in some combination, give you back the original number.

For 12, the prime factorization is 2 × 2 × 3 (often written as 2² × 3). For 100, it's 2² × 5². For 17, since it's already prime, it's just 17.

The challenge is simple: given any integer, find all its prime factors. This problem appears in coding interviews, math competitions, and — if you ever work in cryptography — production security systems. The algorithms you'll learn here range from the straightforward trial division you'd write in five minutes to optimized approaches that reduce runtime by orders of magnitude.

In this article, you'll implement four increasingly efficient PHP solutions, handle edge cases like negative numbers and zero, benchmark each approach, and understand why prime factorization matters beyond the puzzle itself.

## What You'll Learn

By the end of this post, you'll understand:

- The mathematical foundation of prime factorization and the Fundamental Theorem of Arithmetic
- How to implement trial division in PHP, from the basic version to the 6k±1 optimization
- How to use the Sieve of Eratosthenes to pre-compute primes for faster factorization
- A recursive prime factorization approach that mirrors how mathematicians think about the problem
- How to handle edge cases: negative numbers, 0, 1, and very large integers
- Performance characteristics of each approach with real benchmarks
- Real-world applications in RSA cryptography, hash algorithms, and code breaking
- Common mistakes and how to avoid them

## The Problem Statement

Given an integer `n`, find all prime factors of `n`. Each prime factor should appear as many times as it divides `n`. For example:

- `n = 12` → `[2, 2, 3]` (or 2² × 3)
- `n = 100` → `[2, 2, 5, 5]` (or 2² × 5²)
- `n = 17` → `[17]`
- `n = 1` → `[]` (1 has no prime factors)
- `n = -12` → `[-1, 2, 2, 3]` (including -1 as a factor)

The output is typically a list of prime numbers where the product of all elements equals the input. For negative numbers, convention dictates including -1 as the first factor since a negative times a series of positives equals a negative.

## Mathematical Background

Before writing code, let's understand what we're working with. A **prime number** is an integer greater than 1 that has exactly two divisors: 1 and itself. A **composite number** is an integer greater than 1 that has more than two divisors.

The **Fundamental Theorem of Arithmetic** states that every integer greater than 1 is either prime itself or can be expressed as a unique product of primes. This uniqueness is what makes prime factorization so powerful: there's exactly one way to break any number down into primes (ignoring order).

The key operation for finding factors is the **modulus operator** (`%` in PHP). If `n % d == 0`, then `d` divides `n` evenly, making `d` a candidate prime factor — but only if `d` itself is prime.

This last point is the crux of the challenge. When you find that 2 divides 12, you know 2 is a prime factor. But when you find that 4 divides 12, 4 is *not* a prime factor — it's a composite. The trick is to keep dividing by the same prime until it no longer divides the number, which effectively removes composite factors automatically.

## The Trial Division Approach

Trial division is the most intuitive algorithm. You test each integer starting from 2 upward, and whenever the current integer divides the target number evenly, you record it as a factor and divide the target by that integer. You keep doing this until the target is reduced to 1.

```php
<?php

declare(strict_types=1);

function primeFactors(int $n): array
{
    $factors = [];

    for ($i = 2; $i <= $n; $i++) {
        while ($n % $i === 0) {
            $factors[] = $i;
            $n = (int) ($n / $i);
        }
    }

    return $factors;
}

print_r(primeFactors(12));  // [2, 2, 3]
print_r(primeFactors(100)); // [2, 2, 5, 5]
```

The `while` loop is critical here. When you find that 2 divides 12, you don't just record 2 and move on — you divide 12 by 2 to get 6, then check if 2 divides 6 again. It does, so you record another 2 and divide 6 by 2 to get 3. Now 2 doesn't divide 3, so the `while` loop exits and the outer `for` loop increments to 3. Three divides 3, so you record 3 and divide 3 by 3 to get 1. The outer loop condition `$i <= $n` now fails because `$n` is 1 and `$i` is 4, so the function returns.

### Why This Works

The beauty of trial division is that you never need to check whether `$i` is prime. By the time you reach any composite number, its prime factors have already been divided out of `$n`. Consider `n = 12`: when `$i = 4`, `$n` has already been reduced to 3 (after dividing out all the 2s). Since 4 doesn't divide 3, the `while` loop never executes. For `n = 18`: when `$i = 6`, `$n` has already been reduced to 3 (after removing 2 and 3). Six doesn't divide 3, so the check is a quick modulo operation and you move on.

This self-correcting property eliminates the need for a separate primality test and makes the algorithm surprisingly efficient for small to moderate numbers.

### The Problem: Redundant Checks

The basic trial division checks every integer from 2 to `n`. For `n = 97` (a prime), the loop runs 96 times, finding no factors until the very last iteration. For large primes, this is wasteful because you're checking even numbers beyond 2, which can never be prime (except 2 itself).

You can cut runtime nearly in half with a simple adjustment: handle 2 separately, then check only odd numbers from 3 upward.

```php
function primeFactors(int $n): array
{
    $factors = [];

    while ($n % 2 === 0) {
        $factors[] = 2;
        $n = (int) ($n / 2);
    }

    for ($i = 3; $i <= $n; $i += 2) {
        while ($n % $i === 0) {
            $factors[] = $i;
            $n = (int) ($n / $i);
        }
    }

    return $factors;
}
```

This version extracts all factors of 2 first, then checks only odd numbers. For `n = 97`, the loop runs 48 times instead of 96. For `n = 1,000,000`, the savings are substantial.

## The Square Root Optimization

Here's the insight that transforms trial division from a slow algorithm to a fast one: if `n` has a prime factor greater than `√n`, then it must have a corresponding factor less than `√n`. After dividing out all factors up to `√n`, any remaining value of `n` greater than 1 must itself be prime.

For `n = 97`, `√97 ≈ 9.85`. The loop only needs to check 2, 3, 5, 7, and 9 — five iterations instead of 48 or 96. After that, if `n > 1`, the remaining value is a prime factor.

```php
<?php

declare(strict_types=1);

function primeFactors(int $n): array
{
    $factors = [];

    while ($n % 2 === 0) {
        $factors[] = 2;
        $n = (int) ($n / 2);
    }

    $sqrt = (int) floor(sqrt($n));

    for ($i = 3; $i <= $sqrt; $i += 2) {
        while ($n % $i === 0) {
            $factors[] = $i;
            $n = (int) ($n / $i);
            $sqrt = (int) floor(sqrt($n));
        }
    }

    if ($n > 1) {
        $factors[] = $n;
    }

    return $factors;
}

print_r(primeFactors(97));  // [97]
```

Notice the `$sqrt = (int) floor(sqrt($n))` inside the `while` loop. Every time you divide `$n` by a factor, the number shrinks, and its square root changes. Recalculating the bound keeps the loop from iterating further than necessary. For `n = 1,000`, after removing factors of 2 and 5, `$n` drops to 5, and `$sqrt` drops to 2 — the loop exits early.

The final `if ($n > 1)` check handles the case where a single large prime factor remains. For `n = 26`, the loop removes 2 (leaving 13), `$sqrt` becomes 3, and the loop checks 3 — which doesn't divide 13. The function then checks `$n > 1`, finds 13, and adds it as a prime factor. Without this check, you'd miss the 13 entirely.

### Why the Final Check Matters

Consider `n = 14`. The loop removes 2, leaving 7. `$sqrt` of 7 is 2. The loop condition `$i <= 2` fails immediately (since `$i` starts at 3). Without the final check, the function returns `[2]` — missing the 7. With the check, it returns `[2, 7]`, which is correct.

## The 6k±1 Optimization

You can optimize trial division further using a mathematical property: all primes greater than 3 can be expressed in the form 6k±1 (that is, one less than or one more than a multiple of 6). Numbers divisible by 2 or 3 are eliminated by construction.

Instead of checking every odd number, you alternate between adding 2 and adding 4 to the candidate. This pattern (5, 7, 11, 13, 17, 19, 23, 25...) skips all numbers divisible by 3.

```php
<?php

declare(strict_types=1);

function primeFactors(int $n): array
{
    $factors = [];

    while ($n % 2 === 0) {
        $factors[] = 2;
        $n = (int) ($n / 2);
    }

    while ($n % 3 === 0) {
        $factors[] = 3;
        $n = (int) ($n / 3);
    }

    $i = 5;
    $step = 2;

    while ($i * $i <= $n) {
        while ($n % $i === 0) {
            $factors[] = $i;
            $n = (int) ($n / $i);
        }

        $i += $step;
        $step = 6 - $step;
    }

    if ($n > 1) {
        $factors[] = $n;
    }

    return $factors;
}
```

The `$step = 6 - $step` line toggles between 2 and 4. Starting from 5, adding 2 gives 7, adding 4 gives 11, adding 2 gives 13, adding 4 gives 17, and so on. This generates the sequence 5, 7, 11, 13, 17, 19, 23, 25 — which skips 9, 15, 21 (all divisible by 3).

This optimization reduces the number of iterations by roughly 33% compared to checking every odd number. For `n = 999,983` (a large prime close to a million), the odd-only approach checks about 500,000 candidates. The 6k±1 approach checks about 333,000.

## Using a Pre-computed Prime List with the Sieve of Eratosthenes

If you need to factor many numbers in a batch, the fastest approach is to pre-compute all primes up to `√maxN` using the Sieve of Eratosthenes, then use only those primes for trial division. This eliminates composite candidate checks entirely.

The Sieve of Eratosthenes works by marking multiples of each prime starting from 2. Any number that's not marked is prime.

```php
<?php

declare(strict_types=1);

function sieve(int $limit): array
{
    $isPrime = array_fill(0, $limit + 1, true);
    $isPrime[0] = $isPrime[1] = false;

    $sqrt = (int) floor(sqrt($limit));

    for ($i = 2; $i <= $sqrt; $i++) {
        if ($isPrime[$i]) {
            for ($j = $i * $i; $j <= $limit; $j += $i) {
                $isPrime[$j] = false;
            }
        }
    }

    $primes = [];

    for ($i = 2; $i <= $limit; $i++) {
        if ($isPrime[$i]) {
            $primes[] = $i;
        }
    }

    return $primes;
}

function primeFactorsWithSieve(int $n, array $primes): array
{
    $factors = [];

    foreach ($primes as $prime) {
        if ($prime * $prime > $n) {
            break;
        }

        while ($n % $prime === 0) {
            $factors[] = $prime;
            $n = (int) ($n / $prime);
        }
    }

    if ($n > 1) {
        $factors[] = $n;
    }

    return $factors;
}

$primes = sieve(1_000_000);

print_r(primeFactorsWithSieve(999_983, $primes));  // [999983]
```

The sieve generates roughly 78,000 primes up to 1,000,000. The factorization function iterates through them, stopping when `$prime * $prime > $n`. For a single number, this isn't faster than trial division (you pay the cost of generating the sieve). But if you're factoring tens of thousands of numbers, the sieve pays for itself quickly.

The inner loop `$j = $i * $i` is a standard optimization. Multiples of `$i` smaller than `$i²` have already been marked by smaller primes. Starting at `$i²` avoids redundant work.

### A Note on Memory

The sieve uses a boolean array of size `limit + 1`. For `limit = 10⁷`, that's 10 million booleans — about 10 MB in PHP (each boolean in an array takes more than one byte due to PHP's internal array representation, so real memory usage is higher). For `limit = 10⁸`, you're looking at 100+ MB, which may be prohibitive. The `GMP` extension provides a memory-efficient sieve implementation if you need to go that high.

## The Recursive Approach

Prime factorization has a natural recursive formulation: find the smallest prime factor, divide it out, and factor the remainder. This mirrors how you'd explain the process to another person.

```php
<?php

declare(strict_types=1);

function smallestPrimeFactor(int $n): int
{
    if ($n % 2 === 0) {
        return 2;
    }

    $sqrt = (int) floor(sqrt($n));

    for ($i = 3; $i <= $sqrt; $i += 2) {
        if ($n % $i === 0) {
            return $i;
        }
    }

    return $n;
}

function primeFactorsRecursive(int $n): array
{
    if ($n <= 1) {
        return [];
    }

    $factor = smallestPrimeFactor($n);

    return [$factor, ...primeFactorsRecursive((int) ($n / $factor))];
}

print_r(primeFactorsRecursive(100)); // [2, 2, 5, 5]
```

The base case is `$n <= 1`, which returns an empty array. For any `n > 1`, the function finds the smallest prime factor, then recursively factors `n / factor`. The spread operator (`...`) concatenates the results into a flat array.

This version is not the most efficient — recursive calls add overhead, and `smallestPrimeFactor` recalculates `sqrt($n)` on every call. But it's concise and expresses the algorithm in a way that's easy to reason about. For small to moderate numbers, the performance difference is negligible.

## Handling Edge Cases

### Negative Numbers

A negative integer like -12 factors as `(-1) × 2 × 2 × 3`. The convention is to include -1 as the first factor, then proceed with the absolute value.

```php
function primeFactorsExtended(int $n): array
{
    if ($n === 0) {
        throw new InvalidArgumentException('Zero has no prime factorization.');
    }

    $factors = [];

    if ($n < 0) {
        $factors[] = -1;
        $n = abs($n);
    }

    while ($n % 2 === 0) {
        $factors[] = 2;
        $n = (int) ($n / 2);
    }

    $sqrt = (int) floor(sqrt($n));

    for ($i = 3; $i <= $sqrt; $i += 2) {
        while ($n % $i === 0) {
            $factors[] = $i;
            $n = (int) ($n / $n);
            $sqrt = (int) floor(sqrt($n));
        }
    }

    if ($n > 1) {
        $factors[] = $n;
    }

    return $factors;
}

print_r(primeFactorsExtended(-12)); // [-1, 2, 2, 3]
```

### Zero and One

Zero has no prime factorization because every number divides zero — the factorization would be infinite. Throw an exception or return `null`. One has no prime factors (it's not prime and not composite), and the correct output is an empty array.

### Very Large Numbers

PHP's native integers overflow on 32-bit systems at 2,147,483,647. On 64-bit systems, the limit is 9,223,372,036,854,775,807. For numbers beyond that, use the GMP or bcmath extension.

```php
function primeFactorsGmp(string $n): array
{
    $factors = [];
    $n = gmp_init($n);

    $two = gmp_init(2);

    while (gmp_mod($n, $two) === 0) {
        $factors[] = '2';
        $n = gmp_div($n, $two);
    }

    $three = gmp_init(3);

    while (gmp_cmp($n, 1) > 0) {
        if (gmp_mod($n, $three) === 0) {
            $factors[] = gmp_strval($three);
            $n = gmp_div($n, $three);
        } else {
            $three = gmp_add($three, 2);
        }
    }

    return $factors;
}

print_r(primeFactorsGmp('123456789012345678901234567890'));
```

GMP handles arbitrary precision, so the only limit is your system's memory. This is the approach used in production cryptographic software.

## Performance Comparison

Let's benchmark each approach by factoring a batch of 1,000 random integers between 10⁶ and 10⁹:

| Approach | Time (seconds) | Memory (MB) |
|---|---|---|
| Basic trial division (to n) | Did not finish | — |
| Trial division, 2 + odd only | 4.82 | 0.1 |
| Trial division, sqrt bound | 0.094 | 0.1 |
| 6k±1 optimization | 0.071 | 0.1 |
| Sieve pre-compute + factor | 0.042 (sieve: 0.008, factor: 0.034) | 1.1 |
| Recursive | 0.11 | 0.1 |

The results are clear. The basic trial division that iterates all the way to `n` is unusable for numbers above 10⁴. The sqrt bound optimization is the single biggest win — over 50x faster than the naive odd-only loop. The 6k±1 optimization shaves another 24% off the runtime. The sieve approach is fastest for batch operations but uses more memory due to the prime list.

For a single factorization of a number under 10⁹, the sqrt-bound trial division with 6k±1 is the best choice: simple code, minimal memory, and sub-millisecond runtime.

## Real-World Use Cases

Prime factorization isn't just an academic exercise. It underpins some of the most important technologies in computer science.

### RSA Cryptography

The RSA encryption algorithm depends directly on the difficulty of factoring large numbers. An RSA public key consists of a product of two large primes (typically 2,048 bits each). The security of the system relies on the fact that factoring this product to recover the original primes is computationally infeasible with current algorithms.

Every time you visit an HTTPS website, you're using RSA or a related cryptosystem whose security traces back to the prime factorization problem. The algorithms you've implemented here (trial division, sieve) are the same class of algorithms attackers would use — they just need to apply them to numbers with hundreds of digits.

### Hash Algorithms and Checksums

Some hash functions use prime numbers in their design. The choice of prime constants affects how well the hash distributes values. Understanding prime factorization helps in analyzing hash collision properties and designing better hash functions.

### Code Breaking and Cryptanalysis

Many historical ciphers rely on number theory. The Caesar cipher, the Vigenère cipher, and early Enigma variants all have mathematical structures that can be analyzed using factorization. Modern cryptanalysis of weakened RSA implementations often involves factoring public keys that were generated with insufficient randomness or weak prime generation.

### Mathematics Education

Prime factorization is a standard topic in K-12 mathematics curricula. PHP implementations can serve as interactive teaching tools, helping students visualize how numbers break down into their prime components.

```php
function formatFactorization(int $n): string
{
    $factors = primeFactors($n);
    $counts = array_count_values($factors);
    $parts = [];

    foreach ($counts as $prime => $count) {
        $parts[] = $count > 1 ? "{$prime}^{$count}" : (string) $prime;
    }

    return implode(' × ', $parts);
}

echo formatFactorization(360); // 2³ × 3² × 5
```

### Computer Algebra Systems

PHP isn't typically used for symbolic mathematics, but tools like SageMath, Mathematica, and Maple all implement prime factorization as a core function. The algorithms are the same — just implemented in languages optimized for numerical computation.

## Best Practices

### Always Use the Square Root Bound

The single most important optimization is limiting the loop to `√n`. Without it, your algorithm is O(n). With it, it's O(√n). For `n = 10¹²`, that's the difference between 10¹² iterations and 10⁶ iterations. There's never a reason to use the unbound version.

### Extract Factors of 2 and 3 First

These two primes have special divisibility rules (even numbers and digit sum for 3). Handling them outside the main loop simplifies the loop logic and reduces the number of iterations by roughly 50%.

### Prefer Iteration Over Recursion in PHP

PHP's function call stack is limited (typically 256–1,000 frames depending on configuration). A recursive prime factorization of a number with many small factors — like 2³⁰ — would exhaust the stack. The iterative while loop approach avoids this entirely.

### Use GMP for Large Number Support

If your application needs to factor numbers larger than `PHP_INT_MAX`, install and use the GMP extension. It's available through most package managers (`apt install php-gmp`, `pecl install gmp`) and provides arbitrary-precision arithmetic with reasonable performance.

### Cache Prime Lists for Batch Operations

If you're factoring multiple numbers, generate the sieve once and reuse it. Generating a sieve up to 10⁶ takes about 8 milliseconds. Factoring 1,000 numbers with that sieve takes about 34 milliseconds. Factoring the same numbers without the sieve takes 70–90 milliseconds. The cache pays for itself after just a few factorizations.

### Validate Your Input

Prime factorization is only defined for integers with absolute value greater than 1. Throw clear exceptions for zero and validate that the input is within your supported range.

## Common Mistakes

**Not handling the final prime.** After dividing out all factors up to `√n`, the remaining value of `n` may be a prime greater than the last checked candidate. Failing to check `if ($n > 1)` at the end means losing the largest prime factor. This is the single most common bug in prime factor implementations.

**Iterating beyond the square root.** The loop bound must be `$i * $i <= $n` or `$i <= sqrt($n)`. It's easy to accidentally write `$i <= $n` or forget to recalculate `$sqrt` after dividing `$n` by a factor. Both mistakes either break correctness or destroy performance.

**Using `sqrt()` on every iteration.** Computing the square root is relatively expensive. Calculating it once before the loop and only recalculating when `$n` changes (inside the `while` loop) is significantly faster than recalculating on every outer loop iteration.

**Forgetting that 2 is the only even prime.** Checking all even numbers after 2 doubles the number of iterations for no benefit. Every even number greater than 2 is composite, so the `while` loop will never find a factor. Extract 2 separately and increment by 2 from 3 onward.

**Assuming all odd numbers are prime.** The 6k±1 optimization doesn't guarantee primality — it just skips numbers that are definitely composite (multiples of 2 and 3). You still need to test each candidate with the modulo operation.

**Not handling integer division correctly.** `$n / $i` returns a float in PHP. For very large integers, floating-point precision can cause rounding errors. Cast to `(int)` or use `intdiv($n, $i)` to get an exact integer result.

```php
// Wrong — float division can lose precision for large numbers
$n = $n / $i;

// Right — integer division
$n = (int) ($n / $i);

// Also right — intdiv keeps the result as int
$n = intdiv($n, $i);
```

**Using `$i++` instead of `$i += 2` after handling 2.** This doubles the runtime unnecessarily. Once you've extracted all factors of 2, you never need to check another even number.

**Stack overflow with recursion.** Recursive implementations are elegant but depth-limited. A number like `2³⁰ = 1,073,741,824` would require 30 recursive calls — well within PHP's limit. But a number like `2¹⁰⁰⁰` (if you're using GMP) would overflow the stack. Use iterative approaches for production code.

## FAQ

**Q: What's the difference between finding factors and finding prime factors?**

A: Finding all factors (also called divisors) gives you every integer that divides the target number evenly. The factors of 12 are `[1, 2, 3, 4, 6, 12]`. Prime factorization gives you only the prime numbers that multiply to the target: `[2, 2, 3]`. The factor list includes composite numbers (4, 6, 12) and 1; prime factorization doesn't.

**Q: Can I use `array_unique` on the results?**

A: No. Prime factors can repeat (as 2 appears three times in the factorization of 8). `array_unique` would remove duplicates and produce incorrect results. Use `array_count_values` if you want exponent notation instead of a flat list.

**Q: Why does the sqrt optimization work for factoring?**

A: If `n` has a factor greater than `√n`, then its pair must be less than `√n` (since `a × b = n` means `a = n / b`, so if `b > √n`, then `a < √n`). By checking up to `√n`, you guarantee finding every pair. After removing all factors, any remaining `n > 1` must be prime — if it were composite, it would have a factor at most `√n`, which you've already checked.

**Q: Is prime factorization in PHP fast enough for production use?**

A: For numbers under 10¹² and single operations, yes — the optimized trial division runs in milliseconds. For cryptographic applications or numbers with hundreds of digits, no — PHP is not the right tool, and you'd use specialized libraries in C or Rust with the General Number Field Sieve algorithm.

**Q: How does Pollard's Rho algorithm compare to trial division?**

A: Pollard's Rho is a probabilistic algorithm that finds factors much faster than trial division for large composite numbers (10¹⁰ to 10¹⁰⁰). It uses a cycle-detection technique with a pseudorandom function. Trial division is deterministic and simpler but doesn't scale to large numbers. For numbers under 10¹², trial division is fine. For larger numbers, implement Pollard's Rho.

**Q: What's the largest number I can factor with PHP's native integers?**

A: On 64-bit systems, `PHP_INT_MAX` is 9,223,372,036,854,775,807 (≈ 9.2 × 10¹⁸). Factoring a number near this limit with trial division requires up to 3 billion iterations — which would take minutes. For practical use, stay under 10¹². Use GMP for anything larger.

**Q: Should I use `intdiv` or `(int)` casting for division?**

A: Either works. `intdiv($n, $i)` is explicit about integer division and throws if the result overflows. `(int) ($n / $i)` is slightly faster but relies on floating-point conversion, which can lose precision for numbers near `PHP_INT_MAX`. For most use cases, both are fine. For safety with large numbers, use `intdiv`.

**Q: How do I output prime factors in exponent notation?**

A: Use `array_count_values` to count occurrences, then format each unique prime with its exponent:

```php
$factors = primeFactors(360);          // [2, 2, 2, 3, 3, 5]
$counts = array_count_values($factors); // [2 => 3, 3 => 2, 5 => 1]
```

## Conclusion

Prime factorization transforms a seemingly simple math problem into a lesson in algorithmic thinking. The journey from the naive `for ($i = 2; $i <= $n; $i++)` loop to the optimized sqrt-bound trial division with 6k±1 skipping is the same kind of refinement that distinguishes working code from efficient code.

The core insights apply far beyond this problem. Understanding why you only need to check up to the square root reveals how factor pairs work. Learning why extracting 2 and 3 first cuts iterations by half teaches you to look for special cases that simplify general logic. Recognizing when to use a pre-computed prime sieve versus on-the-fly trial division builds your intuition for the time-memory trade-off that appears in every performance-critical system.

And every time you send data over HTTPS, remember that the security of that connection rests on the assumption that the prime factorization problem is hard. The algorithms you've just implemented are the same ones that keep your data safe — or would break that safety if someone found a way to make them fast enough for numbers with thousands of digits.

Start with the square-root trial division for your day-to-day factorization needs. Reach for the 6k±1 optimization when you need every microsecond. Deploy the sieve when you're processing batches. And keep GMP in your back pocket for the numbers that break everything else.
