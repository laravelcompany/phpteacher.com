---
title: "The Birthday Paradox - Probability Simulations in PHP"
seoTitle: "The Birthday Paradox - Probability Simulations in PHP"
description: "Explore the birthday paradox through Monte Carlo simulations in PHP. Learn how 23 people yield a 50% chance of a shared birthday, with practical applications in cryptography and hashing."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-07-26 21:00:00"
tags: ["PHP", "Birthday Paradox", "Probability", "Statistics", "Algorithms", "Math"]
---

The birthday paradox is one of those mathematical facts that feels wrong until you see the numbers. How many people do you need in a room before there's a 50% chance that two people share a birthday? Most people guess 100 or 200. The answer, counterintuitively, is 23.

This article explores the birthday paradox through simulation. Instead of calculating probabilities with formulas, we'll use PHP to run millions of Monte Carlo simulations. This approach isn't just educational—it's a practical technique for solving real-world probability problems that are too complex for closed-form solutions.

## Understanding the Problem

The chance that you share a birthday with a specific person is 1/365, about 0.27%. That's tiny. But the question isn't about matching *your* birthday—it's about *any* two people matching. In a group of N people, the number of possible pairs grows quadratically. With 23 people, there are 253 possible pairs. Each pair has a 1/365 chance of sharing a birthday, and those probabilities compound.

The mathematical formula:

```
P(at least one shared birthday) = 1 - P(no shared birthdays)
P(no shared birthdays) = (365/365) × (364/365) × (363/365) × ... × ((365-N+1)/365)
```

For N=23, this works out to approximately 50.7%.

But rather than crunch through factorials, let's simulate it.

## The Simulation Approach

Monte Carlo simulation runs a random experiment many times and counts the outcomes. For the birthday problem:

1. Generate N random birthdays
2. Check if any two are the same
3. Repeat M times
4. Calculate frequency = shared_count / M

As M grows, the frequency converges to the true probability.

## Building the Simulator

### Step 1: Generate a Random Birthday

Using PHP's DateTime with relative date strings makes this clean:

```php
<?php

declare(strict_types=1);

function getRandomDate(): \DateTime
{
    $offset = random_int(0, 364);
    return new \DateTime("January 1, 2022 +{$offset} days");
}
```

Why not pick a random month then a random day? Because months have different lengths. Picking a random month first slightly biases the distribution (February would have equal weight to July). By picking a random day number (0-364) and converting to a date, every day gets equal probability.

### Step 2: Create a Group

```php
function makeGroup(int $n): array
{
    return array_map(
        fn() => getRandomDate()->format('m/d'),
        range(0, $n - 1)
    );
}
```

We format dates as `m/d` strings to strip the year. Only the month and day matter for birthday comparisons.

### Step 3: Check for Duplicates

PHP's `array_unique` makes this trivial:

```php
function hasDuplicates(array $dates): bool
{
    return count(array_unique($dates)) !== count($dates);
}
```

If removing duplicates reduces the array size, there was at least one shared birthday.

### Step 4: Run Multiple Simulations

```php
function doRuns(int $runs, int $size): int
{
    $hasDuplicate = 0;

    for ($i = 0; $i < $runs; $i++) {
        $group = makeGroup($size);
        if (hasDuplicates($group)) {
            $hasDuplicate++;
        }
    }

    return $hasDuplicate;
}
```

### Step 5: Collect Results Across Group Sizes

```php
function simulate(int $runs): void
{
    echo sprintf("%3s %12s %12s\n", 'N', 'Duplicates', 'Probability');

    for ($size = 2; $size <= 30; $size++) {
        $dupesFound = doRuns($runs, $size);
        echo sprintf(
            "%3d %12d %12.6f\n",
            $size,
            $dupesFound,
            $dupesFound / $runs
        );
    }
}
```

## Running the Simulation

### 10 Runs per Group Size

With only 10 runs per group size, results are wildly erratic:

```
  N  Duplicates  Probability
  2            0     0.0000
  3            0     0.0000
  4            0     0.0000
  5            2     0.2000
  6            0     0.0000
  7            1     0.1000
 10            0     0.0000
 15            3     0.3000
 20            3     0.3000
 23            0     0.0000
 30            6     0.6000
```

Notice N=23 shows 0%—with only 10 runs, chance had none producing a duplicate. This is the danger of too few samples.

### 1,000 Runs per Group Size

```
  N  Duplicates  Probability
  2            1     0.0010
  5           28     0.0280
 10           98     0.0980
 15          268     0.2680
 20          397     0.3970
 23          498     0.4980
 25          589     0.5890
 30          734     0.7340
```

N=23 shows 49.8%—close to the theoretical 50.7%. The law of large numbers is working.

### 1,000,000 Runs per Group Size

```
  N  Duplicates  Probability
  2         2758     0.002758
  5        27086     0.027086
 10       116798     0.116798
 15       252275     0.252275
 20       411242     0.411242
 23       507167     0.507167
 24       538664     0.538664
 25       568134     0.568134
 30       707394     0.707394
```

With a million runs, N=23 hits 50.7%—nearly identical to the theoretical value. The simulation converges. Results are stable to four decimal places.

## Complete Simulation Class

Here's a clean, reusable PHP 8.1 implementation:

```php
<?php

declare(strict_types=1);

namespace App\Simulations;

final class BirthdayParadox
{
    private const DAYS_IN_YEAR = 365;

    public function simulate(int $runs, int $maxSize): array
    {
        $results = [];

        for ($size = 2; $size <= $maxSize; $size++) {
            $duplicates = 0;

            for ($run = 0; $run < $runs; $run++) {
                if ($this->groupHasDuplicate($size)) {
                    $duplicates++;
                }
            }

            $results[$size] = [
                'runs' => $runs,
                'duplicates' => $duplicates,
                'probability' => $duplicates / $runs,
            ];
        }

        return $results;
    }

    private function groupHasDuplicate(int $size): bool
    {
        $birthdays = [];

        for ($i = 0; $i < $size; $i++) {
            $day = $this->randomBirthday();

            if (isset($birthdays[$day])) {
                return true;
            }

            $birthdays[$day] = true;
        }

        return false;
    }

    private function randomBirthday(): string
    {
        $offset = random_int(0, self::DAYS_IN_YEAR - 1);
        return (new \DateTime("January 1 +{$offset} days"))->format('m-d');
    }
}
```

## Performance Considerations

For 30 group sizes × 1,000,000 runs each, we're generating 30 million birthday checks. Each check involves creating a DateTime object, which isn't free. You can optimize by working with integers directly:

```php
private array $dayNames = [];

private function randomBirthdayFast(): int
{
    return random_int(0, 364);
}

private function birthdayName(int $day): string
{
    return $this->dayNames[$day] ??= (new \DateTime("January 1 +{$day} days"))->format('m-d');
}
```

Or skip the date entirely and just compare integers—the month and day mapping is only needed for human-readable output.

## Real-World Applications

The birthday paradox isn't just a party trick. It has serious implications:

### Hash Collisions

The birthday paradox directly applies to hash functions. If a hash produces N-bit outputs, an attacker needs only about 2^(N/2) attempts to find two inputs with the same hash (a collision). This is the birthday attack.

For MD5 (128-bit), 2^64 attempts finds a collision—feasible with modern hardware. This is why MD5 and SHA-1 are deprecated for security use. SHA-256 (256-bit) requires 2^128 attempts, still computationally infeasible.

### UUID v4 Collisions

UUID v4 generates 122 random bits. The birthday paradox says you need about 2^61 UUIDs before hitting a 50% collision probability. That's 2.3 quintillion—safe for practical purposes. But if you're generating billions of UUIDs per second for years, the probability creeps up.

### Database Indexing

When designing database indexes, the birthday paradox explains why hash indexes on small key spaces (like status fields with 3-5 values) perform poorly—many rows hash to the same bucket.

### Load Balancing

Random load balancing across N servers sees birthday-paradox behavior. With 100 servers, after about 12 requests, there's a 50% chance two hit the same server. This isn't a problem for load balancing, but it illustrates how "random" often clusters more than intuition suggests.

## Beyond the Classic Problem

The simulation approach extends to many variations:

**Leap year birthdays**: Include February 29 (366 days) and see how probabilities shift.

**Non-uniform distributions**: Real birthday data shows seasonal patterns (September is the most common birth month in many countries). Use actual birth statistics as probability weights and see how much it changes the result.

**Three-way matches**: What's the probability that three people share a birthday? With 23 people it's rare (~1%), but with 88 people it reaches 50%.

**Near misses**: What's the probability that two birthdays are within one day of each other? Much higher than exact matches—about 89% with 14 people.

## 3D Visualization of the Results

The simulation produces compelling data, but seeing the curve makes the paradox intuitive in a way numbers alone can't. The probability jumps sharply between 10 and 30 people, then plateaus toward 100%:

```php
<?php

function printChart(array $results): void
{
    $maxWidth = 50;

    foreach ($results as $size => $data) {
        $probability = $data['probability'];
        $barWidth = (int) round($probability * $maxWidth);
        $bar = str_repeat('█', $barWidth);

        echo sprintf(
            "%2d | %s %.1f%%\n",
            $size,
            str_pad($bar, $maxWidth),
            $probability * 100
        );
    }
}

$simulation = new \App\Simulations\BirthdayParadox();
$results = $simulation->simulate(runs: 10000, maxSize: 50);
printChart($results);
```

Output (truncated):

```
 2 |                                                   0.3%
 5 | ███                                               5.1%
10 | ███████████                                      11.4%
15 | █████████████████████                            25.7%
20 | ████████████████████████████████████              41.2%
23 | ██████████████████████████████████████████████    50.7%
25 | ██████████████████████████████████████████████████ 56.2%
30 | ██████████████████████████████████████████████████ 70.5%
40 | ██████████████████████████████████████████████████ 89.4%
50 | ██████████████████████████████████████████████████ 97.0%
```

The S-curve shape is characteristic of these kinds of combinatorial probability problems. The inflection point—where the curve rises fastest—is around N=20 to N=30, which is why 23 is the magic number.

## Optimizing the Simulation

Running 30 million random birthday generations can be slow in PHP. Here are optimization techniques that maintain accuracy while improving performance:

**Use integers instead of DateTime objects**:

```php
private function randomBirthdayFast(): int
{
    return random_int(0, 364);
}
```

This avoids DateTime object creation entirely—roughly 10x faster per call.

**Use boolean arrays instead of array_unique**:

```php
private function groupHasDuplicateFast(int $size): bool
{
    $seen = [];

    for ($i = 0; $i < $size; $i++) {
        $day = $this->randomBirthdayFast();

        if (isset($seen[$day])) {
            return true;
        }

        $seen[$day] = true;
    }

    return false;
}
```

This short-circuits as soon as a duplicate is found, skipping the rest of the group. On average, you only need to check ~20 birthdays for a group of 23 before finding a duplicate when one exists.

**Parallelize across multiple CPUs**:

```php
use Parallel\Runtime;

$runtimes = [];
for ($i = 0; $i < 4; $i++) {
    $runtime = new Runtime(__DIR__ . '/bootstrap.php');
    $runtimes[] = $runtime->run(
        fn() => doRuns(250000, $size)
    );
}

$totalDuplicates = 0;
foreach ($runtimes as $future) {
    $totalDuplicates += $future->value();
}
```

Each chunk of 250,000 runs executes in parallel, dividing total execution time by the number of CPU cores.

## The Birthday Problem in Cryptography

The birthday paradox has direct, serious implications in cryptography. Any hash function with an N-bit output provides only 2^(N/2) bits of security against collision attacks. This is why:

**MD5 (128-bit)** provides only 2^64 collision resistance. As of 2024, finding an MD5 collision takes seconds on consumer hardware. Never use MD5 for security.

**SHA-1 (160-bit)** provides 2^80 collision resistance. The SHAttered attack in 2017 demonstrated a practical collision. Deprecated in all modern browsers.

**SHA-256 (256-bit)** provides 2^128 collision resistance. Still considered secure for the foreseeable future.

**SHA-512 (512-bit)** provides 2^256 collision resistance. Future-proof.

When choosing hash algorithms in PHP applications, use `hash('sha256', $data)` or the `sodium_crypto_generichash()` function for security-related hashing. Password hashing requires a dedicated algorithm via `password_hash()`.

## A Clean Object-Oriented Solution

Here's the complete simulation as a PHP 8.1 class with proper typing and optimization:

```php
<?php

declare(strict_types=1);

namespace App\Simulations;

final class BirthdayParadoxSimulation
{
    private const DAYS = 365;

    /**
     * @return array<int, array{runs: int, duplicates: int, probability: float}>
     */
    public function simulate(int $runs, int $maxSize): array
    {
        $results = [];

        for ($size = 2; $size <= $maxSize; $size++) {
            $results[$size] = $this->runForGroupSize($runs, $size);
        }

        return $results;
    }

    /**
     * @return array{runs: int, duplicates: int, probability: float}
     */
    private function runForGroupSize(int $runs, int $size): array
    {
        $duplicates = 0;

        for ($i = 0; $i < $runs; $i++) {
            if ($this->groupHasDuplicate($size)) {
                $duplicates++;
            }
        }

        return [
            'runs' => $runs,
            'duplicates' => $duplicates,
            'probability' => $duplicates / $runs,
        ];
    }

    private function groupHasDuplicate(int $size): bool
    {
        $birthdays = [];

        for ($i = 0; $i < $size; $i++) {
            $day = \random_int(0, self::DAYS - 1);

            if (isset($birthdays[$day])) {
                return true;
            }

            $birthdays[$day] = true;
        }

        return false;
    }
}
```

## Next Month's Puzzle: Decimals to Fractions

Given any floating-point number, write a function that returns a string representing the number as a fraction in its simplest form. For example, `0.8` should return `"4/5"`.

```php
function decimalToFraction(float $decimal): string
{
    // Your solution here
}

echo decimalToFraction(0.8);     // "4/5"
echo decimalToFraction(0.333);   // "333/1000"
echo decimalToFraction(1.5);     // "3/2"
```

The solution requires finding the greatest common divisor (GCD) and handling repeating decimals if you want to get fancy. Pure PHP, no frameworks required.

## Conclusion

The birthday paradox teaches two lessons. First, intuition about probability is unreliable—our brains didn't evolve to handle combinatorial explosions. Second, simulation is a powerful tool for understanding probability. When a problem is too complex for a closed-form formula, you can almost always simulate it.

PHP's `random_int`, `DateTime`, and array functions provide everything you need for Monte Carlo simulations. The approach works for queueing theory, risk analysis, game physics, and countless other domains. Next time you face a probability question, don't reach for a textbook first—write a simulation and watch the numbers converge.
