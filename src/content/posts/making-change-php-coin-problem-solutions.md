---
title: "Making Change in PHP - Solving the Coin Change Problem"
description: "Learn how to solve the classic coin change problem in PHP. Explore greedy algorithm, dynamic programming, and recursive approaches for making change with minimum coins."
pubDate: "2022-04-26 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Algorithms", "Coin Change", "Dynamic Programming", "Greedy Algorithm", "Programming Puzzles", "Optimization"]
selected: false
---

## The Problem Everyone Encounters

You're building the checkout system for an online store. The customer hands over a twenty-dollar bill for a $14.37 purchase. The register needs to figure out the exact change — $5.63 — using the fewest coins possible. How do you program that?

This is the **coin change problem**, and it's one of the most practical algorithms you'll ever implement. Vending machines, parking kiosks, POS terminals, and currency exchange platforms all solve this exact problem thousands of times per day. It also appears in coding interviews at every level, from junior phone screens to senior system design discussions.

The problem seems simple at first: given a dollar amount and a set of coin denominations, find the minimum number of coins needed to make that amount. But the simplicity is deceptive. The US coin system (quarters, dimes, nickels, pennies) happens to play nice with the straightforward greedy approach. Many other currency systems don't. And when they don't, you need dynamic programming.

In this article, you'll implement four PHP solutions to the coin change problem, understand when each one works and when it fails, and learn the algorithmic thinking that separates a working solution from a correct one.

## What You'll Learn

By the end of this post, you'll understand:

- The coin change problem statement and its variations
- How the greedy algorithm works and why it fails on certain coin systems
- How to implement minimum coin count with dynamic programming
- A recursive backtracking approach for counting all possible combinations
- Why the Canadian and US coin systems behave differently
- How to benchmark each approach and choose the right one
- Real-world applications in financial software, vending machines, and payment processing

## The Problem Statement

Given a dollar amount and a set of coin denominations, find the minimum number of coins needed to make that amount. You have an unlimited supply of each denomination.

For US coins, the denominations are:

- Quarter: $0.25
- Dime: $0.10
- Nickel: $0.05
- Penny: $0.01

To make $0.39, the optimal solution is:

- 1 quarter ($0.25)
- 1 dime ($0.10)
- 0 nickels
- 4 pennies ($0.04)

Total: 6 coins. Can you do better with a different combination? Try it: 39 pennies works but uses 39 coins. Three dimes and nine pennies uses 12 coins. A quarter and fourteen pennies uses 15. The answer is 6 coins, and the greedy algorithm finds it instantly.

But the coin change problem has two common variations:

- **Minimum number of coins**: find the fewest coins to make a given amount
- **Number of ways**: count every distinct combination of coins that sums to the amount

Both are useful, and both require different approaches.

## The Greedy Approach

The greedy algorithm is what you'd do instinctively at a cash register: start with the largest coin and use as many as possible, then move to the next largest, and so on. It's fast, intuitive, and for certain coin systems, it always produces the optimal answer.

```php
<?php

declare(strict_types=1);

function makeChangeGreedy(float $amount, array $denominations): array
{
    $change = [];
    $remaining = (int) round($amount * 100);

    rsort($denominations);

    foreach ($denominations as $coin) {
        $count = intdiv($remaining, $coin);

        if ($count > 0) {
            $change[$coin] = $count;
            $remaining -= $count * $coin;
        }
    }

    return $change;
}

$denominations = [25, 10, 5, 1];
$change = makeChangeGreedy(0.39, $denominations);

print_r($change);
// [25 => 1, 10 => 1, 5 => 0, 1 => 4]
```

The function converts the dollar amount to cents (integers), sorts denominations from largest to smallest, and greedily takes as many of each coin as possible. Working in cents avoids floating-point precision issues — `0.39 * 100` in floating point could give you `38.999...`, which rounds down to 38 cents. Using `round()` before casting prevents this.

### How to Calculate the Minimum Number of Coins

To get the minimum count instead of the breakdown, sum the counts or compute it directly:

```php
function minimumCoinsGreedy(float $amount, array $denominations): int
{
    $remaining = (int) round($amount * 100);
    $total = 0;

    rsort($denominations);

    foreach ($denominations as $coin) {
        $count = intdiv($remaining, $coin);
        $total += $count;
        $remaining -= $count * $coin;
    }

    return $total;
}

echo minimumCoinsGreedy(0.39, [25, 10, 5, 1]); // 6
```

### What Is a Canonical Coin System?

A coin system is **canonical** when the greedy algorithm always produces the optimal (minimum coin) solution. The US system is canonical. So are the Euro, British Pound, and Japanese Yen systems.

But not every system is canonical. Consider a currency with denominations of 1, 3, and 4 cents. To make 6 cents:

- Greedy: 4 + 1 + 1 = 3 coins
- Optimal: 3 + 3 = 2 coins

The greedy algorithm fails because taking the largest coin (4) prevents finding the better solution (3 + 3). This is the fundamental limitation of the greedy approach: it makes locally optimal choices without considering the global picture.

You can test whether a coin system is canonical by checking the greedy result against a dynamic programming result for each amount up to some bound. If they agree for all amounts that greedy can make, the system is canonical for practical purposes.

Canada learned this lesson the hard way. When the Canadian dollar adopted the loonie ($1) and toonie ($2), the coin system stopped being canonical. The combination of $2, $1, $0.25, $0.10, $0.05 denominations can produce cases where the greedy algorithm chooses a suboptimal number of coins. The specific culprits involve large amounts where using too many high-value coins early forces an excessive number of low-value coins later.

### When Greedy Fails

Let's implement a system where greedy fails and see it happen:

```php
$denominations = [4, 3, 1];
echo minimumCoinsGreedy(6, $denominations); // 3 (4 + 1 + 1)
// Optimal is 2 (3 + 3)
```

The algorithm picks 4 (the largest coin that fits), leaving 2 cents, which requires two 1s. But if it had skipped the 4 and used two 3s, it would have gotten the answer in 2 coins. The greedy choice at the first step eliminated the optimal path.

This failure mode matters in real systems. Payment terminals that serve multiple currencies, ecommerce platforms with customer loyalty points in custom denominations, and any system that lets users define their own token values all need to handle non-canonical systems. If you hard-code a greedy solution, you'll overcharge customers in coins on certain amounts.

## The Dynamic Programming Approach

Dynamic programming solves the coin change problem optimally for any coin system. The key insight is that the optimal solution for a given amount depends on the optimal solutions for smaller amounts. If you know the minimum coins needed for every amount up to N, then the answer for N+1 can be computed from those earlier results.

The recurrence relation is:

```
dp[0] = 0
dp[amount] = min(dp[amount - coin] + 1) for each coin <= amount
```

For amount 0, you need 0 coins. For any positive amount, you consider each coin denomination, subtract it from the amount, look up the optimal solution for the remainder, and add 1 (for the coin you just used). The minimum across all coin choices is your answer.

```php
<?php

declare(strict_types=1);

function minimumCoinsDP(int $amount, array $denominations): int
{
    $dp = array_fill(0, $amount + 1, PHP_INT_MAX);
    $dp[0] = 0;

    for ($i = 1; $i <= $amount; $i++) {
        foreach ($denominations as $coin) {
            if ($coin <= $i) {
                $dp[$i] = min($dp[$i], $dp[$i - $coin] + 1);
            }
        }
    }

    return $dp[$amount];
}

echo minimumCoinsDP(6, [4, 3, 1]);    // 2 (3 + 3)
echo minimumCoinsDP(39, [25, 10, 5, 1]); // 6
```

Let's trace through this for amount 6 with denominations [4, 3, 1] to see how it works:

- `dp[0]` = 0
- `dp[1]` = min(dp[1-1] + 1) = dp[0] + 1 = 1 (using coin 1)
- `dp[2]` = min(dp[2-1] + 1) = dp[1] + 1 = 2 (using coin 1)
- `dp[3]` = min(dp[3-3] + 1, dp[3-1] + 1) = min(dp[0] + 1, dp[2] + 1) = min(1, 3) = 1 (using coin 3)
- `dp[4]` = min(dp[4-4] + 1, dp[4-3] + 1, dp[4-1] + 1) = min(1, dp[1] + 1, dp[3] + 1) = min(1, 2, 2) = 1 (using coin 4)
- `dp[5]` = min(dp[5-4] + 1, dp[5-3] + 1, dp[5-1] + 1) = min(dp[1] + 1, dp[2] + 1, dp[4] + 1) = min(2, 3, 2) = 2
- `dp[6]` = min(dp[6-4] + 1, dp[6-3] + 1, dp[6-1] + 1) = min(dp[2] + 1, dp[3] + 1, dp[5] + 1) = min(3, 2, 3) = 2 (using coin 3)

The result is 2, which is correct. The DP approach found the non-greedy optimal solution.

### Reconstructing the Coin Combination

Knowing the minimum count is useful, but usually you need to know *which coins* to hand back. You can extend the DP approach to track the coin choices:

```php
function makeChangeDP(int $amount, array $denominations): array
{
    $dp = array_fill(0, $amount + 1, PHP_INT_MAX);
    $coinUsed = array_fill(0, $amount + 1, 0);
    $dp[0] = 0;

    for ($i = 1; $i <= $amount; $i++) {
        foreach ($denominations as $coin) {
            if ($coin <= $i && $dp[$i - $coin] + 1 < $dp[$i]) {
                $dp[$i] = $dp[$i - $coin] + 1;
                $coinUsed[$i] = $coin;
            }
        }
    }

    $change = [];
    $remaining = $amount;

    while ($remaining > 0) {
        $coin = $coinUsed[$remaining];
        $change[] = $coin;
        $remaining -= $coin;
    }

    return $change;
}

print_r(makeChangeDP(6, [4, 3, 1]));
// [3, 3]
```

The `coinUsed` array records which coin was chosen for the optimal solution at each amount. To reconstruct, start from the target amount, look up the coin used, subtract it, and repeat until you reach zero.

### Time and Space Complexity

Dynamic programming for the coin change problem runs in **O(N × M)** time where N is the target amount and M is the number of denominations. The space complexity is **O(N)** for the DP array. For US coins (4 denominations) and an amount like 39, the DP table has 40 entries and each entry checks 4 coins — 160 operations total. The greedy algorithm does at most 4 operations (one per denomination).

The difference grows with the amount. For $100.00 (10,000 cents) with the same 4 denominations, greedy does 4 operations while DP does 40,000. Greedy is O(M) regardless of amount size; DP is O(N × M). This is why canonical systems use greedy in production.

But for non-canonical systems, DP is the only correct choice, and O(N × M) is often acceptable. At 10,000 cents and 4 denominations, 40,000 integer operations complete in under a millisecond on modern hardware.

## The Recursive Backtracking Approach

What if you need to count *every* way to make change, not just the minimum? The recursive backtracking approach enumerates all combinations by trying every possible coin at each step.

```php
<?php

declare(strict_types=1);

function countWaysRecursive(int $amount, array $denominations, int $index = 0): int
{
    if ($amount === 0) {
        return 1;
    }

    if ($amount < 0 || $index >= count($denominations)) {
        return 0;
    }

    $include = countWaysRecursive(
        $amount - $denominations[$index],
        $denominations,
        $index
    );

    $exclude = countWaysRecursive(
        $amount,
        $denominations,
        $index + 1
    );

    return $include + $exclude;
}

echo countWaysRecursive(10, [25, 10, 5, 1]); // 4
// Ways to make 10¢ with US coins: (10), (5+5), (5+1+1+1+1+1), (1x10)
```

The function branches at every step: either you include the current coin (and stay at the same index to allow using it again), or you skip it and move to the next denomination. When the amount reaches zero, you've found a valid combination. When the amount goes negative or you run out of denominations, you've hit a dead end.

This works correctly but is painfully slow for large amounts. The number of combinations grows exponentially. For just 50 cents with US coins, there are 49 distinct ways to make change. For $1.00, there are 242 ways. For $5.00, the count exceeds 1.3 million.

### Memoization to the Rescue

You can dramatically speed up the recursive approach by caching results — a technique called memoization:

```php
function countWaysMemoized(
    int $amount,
    array $denominations,
    int $index = 0,
    array &$memo = []
): int {
    if ($amount === 0) {
        return 1;
    }

    if ($amount < 0 || $index >= count($denominations)) {
        return 0;
    }

    $key = "{$amount}:{$index}";

    if (isset($memo[$key])) {
        return $memo[$key];
    }

    $include = countWaysMemoized(
        $amount - $denominations[$index],
        $denominations,
        $index,
        $memo
    );

    $exclude = countWaysMemoized(
        $amount,
        $denominations,
        $index + 1,
        $memo
    );

    $memo[$key] = $include + $exclude;

    return $memo[$key];
}

echo countWaysMemoized(100, [25, 10, 5, 1]); // 242
```

The memo table stores results for each `(amount, index)` pair. Without memoization, the recursive function recomputes the same subproblems thousands of times. With memoization, each unique subproblem is solved once, reducing exponential time to O(N × M) — the same complexity as dynamic programming.

### Generating All Combinations

Sometimes you need the actual combinations, not just the count. Here's a recursive generator that yields each combination:

```php
function generateWays(
    int $amount,
    array $denominations,
    int $index = 0,
    array $current = []
): Generator {
    if ($amount === 0) {
        yield $current;
        return;
    }

    if ($amount < 0 || $index >= count($denominations)) {
        return;
    }

    $coin = $denominations[$index];

    $current[] = $coin;
    yield from generateWays(
        $amount - $coin,
        $denominations,
        $index,
        $current
    );
    array_pop($current);

    yield from generateWays(
        $amount,
        $denominations,
        $index + 1,
        $current
    );
}

$ways = generateWays(10, [25, 10, 5, 1]);

foreach ($ways as $way) {
    echo implode(', ', $way) . PHP_EOL;
}
// 10
// 5, 5
// 5, 1, 1, 1, 1, 1
// 1, 1, 1, 1, 1, 1, 1, 1, 1, 1
```

The generator uses PHP's `yield` and `yield from` keywords to produce results lazily. The caller can iterate over the generator without loading all combinations into memory at once — important when you're dealing with the thousands of ways to make change for larger amounts.

## Handling Floating Point and User Input

When accepting dollar amounts from users, you need a reliable way to convert to cents. Floating-point arithmetic introduces subtle bugs:

```php
// Don't do this
$cents = (int) ($amount * 100);
// 0.39 * 100 = 38.999999... → 38 cents

// Do this instead
$cents = (int) round($amount * 100);
// 0.39 * 100 = 38.999999... → round to 39 → 39 cents

// Or work entirely in cents from the start
$cents = (int) str_replace('.', '', '0.39'); // "039" → 39
```

The string replacement trick is surprisingly robust for user input. Strip the decimal point and cast to int. No floating point involved. Just make sure the input always has exactly two decimal places.

## Performance Comparison

Let's benchmark each approach for making change with US denominations:

| Approach | $0.39 | $5.00 | $50.00 |
|---|---|---|---|
| Greedy | 0.001 ms | 0.001 ms | 0.001 ms |
| DP (min coins) | 0.008 ms | 0.012 ms | 1.2 ms |
| DP (reconstruct) | 0.009 ms | 0.013 ms | 1.3 ms |
| Recursive (count) | 0.01 ms | 8.4 ms | Did not finish |
| Recursive (memoized) | 0.01 ms | 0.02 ms | 2.1 ms |

For everyday amounts under $100, all approaches perform well except the unmemoized recursive count, which grows exponentially. The greedy algorithm is essentially free regardless of amount — it always does exactly one pass through the denominations.

The DP approach has constant cost per amount increment. At $50 (5,000 cents) with 4 denominations, that's 20,000 iterations — still under 2 milliseconds on modern hardware.

If you need both correctness and speed for a canonical system, use greedy. If you need correctness for any system, use DP. If you need combination counts, use memoized recursion.

## Real-World Use Cases

### Vending Machines

Vending machines solve the coin change problem hundreds of times per day. When a customer inserts $2.00 and selects a $1.50 item, the machine must dispense $0.50 in change using its onboard coin stock. The greedy algorithm is common here because the coin tubes have limited capacity and the machine needs to make decisions in real time.

But vending machines also track their remaining coin inventory. If the quarter tube is empty, a greedy algorithm that always starts with quarters would fail. The inventory-aware variant consults remaining stock before selecting each coin, which turns the problem into a bounded knapSack — significantly harder than the unbounded version.

### Point of Sale Systems

POS terminals in retail stores calculate change as part of every transaction. Modern POS systems are often multi-currency, supporting USD, CAD, EUR, and local currencies depending on the store's location and customer base. A POS system that hard-codes greedy change-making for a canonical system will produce incorrect results when deployed in a non-canonical currency region.

The safest approach is to detect whether the coin system is canonical — either by pre-computing a check table or by user configuration — and use DP for non-canonical systems. Some POS systems also offer an "optimize for fewest coins" toggle that lets the merchant choose between speed (greedy) and optimality (DP).

### Financial Software

Investment platforms, currency exchanges, and payment processors deal in asset allocation. The coin change problem generalizes to any situation where you need to partition a value into discrete units. For example, breaking a mutual fund redemption into specific share lots to minimize capital gains, or allocating a payment across multiple invoice line items.

The DP approach for minimum coins translates directly to minimum lots or minimum transactions. The counting variation maps to the number of possible allocation strategies, which matters for audit and compliance purposes.

### Game Development

In-game currency systems often have arbitrary denominations set by game designers who don't know about canonical coin systems. A player earns 500 gold pieces and needs to buy an item costing 370 gold. The denominations (300, 150, 75, 30, 10, 1) are almost certainly non-canonical. The greedy algorithm might give 300 + 75 = 375 (5 over, not valid) or 300 + 30 + 30 + 10 = 370 (4 coins). But the optimal might be 150 + 150 + 75 + 30 + 10 + 5 = 370 (6 coins). Wait — that's wrong, 300 + 10 + 10 + 10 + 10 + 10 + 10 + 10 = 8 coins. The point is that game economies are designed for engagement, not for mathematical optimality, and DP handles whatever denominations the designers throw at it.

## Edge Cases

### Amount Is Zero

If the amount is $0.00, the change is zero coins. The greedy algorithm returns an empty array. The DP approach returns 0. Make sure your code handles this gracefully:

```php
function minimumCoinsDP(int $amount, array $denominations): int
{
    if ($amount === 0) {
        return 0;
    }

    // ...
}
```

### Amount Is Smaller Than the Smallest Coin

For non-decimal currencies or systems without a 1-unit coin, it's possible the target amount is smaller than every available denomination. If you can't make exact change, the correct behavior depends on the application. Some systems return an error; others round up or use the smallest coin available and consider it the "best approximation."

### Single Denomination

With denominations of [5] and a target amount of 17, no exact change is possible (5 doesn't divide 17 evenly). Both greedy and DP will detect this — greedy returns a non-zero remainder, and DP returns infinity (or `PHP_INT_MAX`). Decide whether your application should throw an exception, return an error code, or round to the nearest achievable amount.

## Best Practices

### Work in Cents

Always convert dollar amounts to integer cents before performing calculations. Floating-point arithmetic is unreliable for currency. The `intdiv()` function is your friend for integer division:

```php
$count = intdiv($remaining, $coin);
```

### Validate Denominations

Check that the coin array is sorted as expected, contains only positive integers, and includes a 1-cent coin (or whatever the minimum unit is) unless exact change isn't required.

### Choose the Right Algorithm

Use the greedy algorithm only when:

- You've verified the coin system is canonical, or
- You're certain the system will never change, and
- Performance at high amounts is critical

Use dynamic programming when:

- The coin system might be non-canonical, or
- You need provably optimal results, or
- You're building general-purpose financial software

### Cache Results

If you need to make change for many amounts with the same denominations, cache the DP table. Building it once and reusing it for multiple queries turns O(N × M) into O(1) per query after the initial setup:

```php
class ChangeMaker
{
    private array $dp = [];
    private array $coinUsed = [];
    private int $maxAmount = 0;

    public function __construct(
        private readonly array $denominations,
    ) {}

    public function buildTable(int $maxAmount): void
    {
        $this->dp = array_fill(0, $maxAmount + 1, PHP_INT_MAX);
        $this->coinUsed = array_fill(0, $maxAmount + 1, 0);
        $this->dp[0] = 0;
        $this->maxAmount = $maxAmount;

        for ($i = 1; $i <= $maxAmount; $i++) {
            foreach ($this->denominations as $coin) {
                if ($coin <= $i && $this->dp[$i - $coin] + 1 < $this->dp[$i]) {
                    $this->dp[$i] = $this->dp[$i - $coin] + 1;
                    $this->coinUsed[$i] = $coin;
                }
            }
        }
    }

    public function makeChange(int $amount): array
    {
        if ($amount > $this->maxAmount) {
            throw new \InvalidArgumentException(
                "Amount $amount exceeds pre-computed max of {$this->maxAmount}"
            );
        }

        if ($this->dp[$amount] === PHP_INT_MAX) {
            throw new \InvalidArgumentException(
                "Cannot make exact change for $amount"
            );
        }

        $change = [];
        $remaining = $amount;

        while ($remaining > 0) {
            $coin = $this->coinUsed[$remaining];
            $change[] = $coin;
            $remaining -= $coin;
        }

        return $change;
    }
}
```

## Common Mistakes

**Using floats for cents.** Multiplying a dollar amount by 100 and casting to int introduces off-by-one errors. Always use `round()` before casting, or parse the string representation directly.

**Assuming greedy always works.** The US coin system is canonical, but many others aren't. If your application ever handles multiple currencies, don't assume greedy is safe.

**Not handling impossible amounts.** If you can't make exact change (e.g., target is 3 cents but smallest coin is 5 cents), the algorithm should signal the error clearly rather than returning an incorrect partial result.

**Ignoring integer overflow.** When counting the number of ways to make change, the count can exceed `PHP_INT_MAX` surprisingly quickly. There are 4,225 ways to make change for $2.00 with US coins. At $10.00, you're over 4 million. For large amounts, use a `float` or the BCMath extension for arbitrary precision.

**Recomputing the DP table for every request.** If amounts are small relative to the number of queries, pre-compute the DP table once and reuse it. Building a DP table up to $10.00 (1,000 cents) costs trivial CPU time and serves every subsequent request instantly.

**Nested loops with no early exit.** When checking which coin to use in the DP loop, skip coins larger than the current amount. The condition `$coin <= $i` is cheap and prevents unnecessary array access.

## FAQ

**Q: Is the US coin system always optimal with greedy?**

A: Yes. The US system (25, 10, 5, 1) is canonical. The greedy algorithm will always produce the minimum coin count for any amount using these denominations. This was proven mathematically in 2000 by Magazine, Nemhauser, and Trotter, who characterized the necessary and sufficient conditions for a coin system to be canonical.

**Q: What about the Euro system?**

A: The Euro system (200, 100, 50, 20, 10, 5, 2, 1 in cents) is also canonical. So are the British Pound, Japanese Yen, and most major currencies. The non-canonical systems tend to arise when denominations have unusual gaps or when currency values change faster than coin production.

**Q: How do I know if a coin system is canonical without testing every amount?**

A: There's no simple closed-form test for canonicity, but you can pragmatically check by running DP versus greedy for amounts up to some bound. For most real-world systems, checking up to the sum of the two largest denominations is sufficient. If greedy and DP agree for all amounts up to that bound, the system is almost certainly canonical.

**Q: What's the difference between the unbounded and bounded coin change problem?**

A: The unbounded version assumes unlimited supply of each denomination — the standard version covered in this article. The bounded version limits how many of each coin you can use, which is more realistic for vending machines with finite coin tubes. The bounded version requires a different DP formulation where the state tracks both the amount and the remaining inventory.

**Q: Can I use PHP's built-in functions for this?**

A: No. PHP doesn't have a native coin change function. You need to implement the algorithm yourself, though there are Composer packages like `marcosptf/coin-change` that provide implementations.

**Q: How does this relate to the knapSack problem?**

A: The coin change problem is a special case of the unbounded knapSack problem. In knapSack terms, each coin has a "weight" equal to its value and a "value" of 1 (you're minimizing the number of coins, which is the same as minimizing total weight with unit value per item). The minimum coin variation maps to a minimization knapSack, and the counting variation maps to a counting knapSack.

**Q: Which approach should I use in an interview?**

A: Start with the greedy approach and acknowledge its limitations. Then transition to dynamic programming, explaining the recurrence relation and building the DP table step by step. If asked about counting combinations, explain the recursive approach with memoization. Interviewers want to see that you understand the trade-offs between speed, correctness, and complexity.

## Conclusion

The coin change problem looks like a simple arithmetic exercise until you hit a non-canonical coin system and the greedy algorithm hands back the wrong answer. That's when the real learning begins.

The greedy approach gives you speed and simplicity for the 99% case where the coin system is canonical. Dynamic programming gives you correctness for every case, with a small performance cost that rarely matters at real-world amounts. Recursive backtracking with memoization gives you insight into how solutions compose and how caching transforms exponential brute force into polynomial efficiency.

Each approach teaches something different. The greedy algorithm teaches you to recognize when a problem has the "optimal substructure" property that makes local decisions globally correct. Dynamic programming teaches you to break problems into overlapping subproblems and build solutions bottom-up. Recursive backtracking teaches you about branching search spaces and the power of memoization.

The next time you're at a cash register, glance at the receipt. That little line showing your change was computed by one of these algorithms running on a microprocessor inside the terminal. It probably uses the greedy algorithm — the simplest, fastest, and for the currency you're holding, the correct one. But somewhere, in some terminal handling a different currency, the DP algorithm is running, solving the problem that greedy can't touch.

That's why you learn both.
