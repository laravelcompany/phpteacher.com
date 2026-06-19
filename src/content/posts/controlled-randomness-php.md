---
title: "Controlled Randomness in PHP - Generating Predictable Random Values"
description: "Learn how to generate and control randomness in PHP. From random numbers to shuffled arrays, seeded generators, and practical applications in testing and game development."
pubDate: "2022-05-24 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Randomness", "RNG", "Testing", "Seeds", "Games", "Algorithms"]
selected: false
---

Randomness is a strange thing in programming. Sometimes you want true unpredictability — generating cryptographic keys, creating unique tokens, rolling dice. Other times you want the *appearance* of randomness but with the ability to reproduce the exact same sequence on demand.

PHP gives you tools for both. The trick is knowing which to use and when.

## PHP's Random Functions

PHP provides several random number generators, each with different characteristics.

### `rand()` and `getrandmax()`

`rand()` uses a linear congruential generator (LCG). It's PHP's original random function, and it's the weakest option available.

```php
$number = rand(1, 10); // integer between 1 and 10 inclusive
```

`rand()` is predictable, has a relatively short period, and exhibits statistical biases in some implementations. Don't use it for anything serious.

### `mt_rand()` and `mt_getrandmax()`

The Mersenne Twister (`mt_rand`) is faster and produces better quality randomness than `rand()`. It was the default for many years in PHP.

```php
$number = mt_rand(1, 100); // integer between 1 and 100 inclusive
```

Mersenne Twister is still not cryptographically secure. Given enough output samples, an attacker can predict future values. Use it for games, simulations, and testing — not for security.

### `random_int()`

PHP 7.0 introduced `random_int()`, which uses the operating system's CSPRNG (Cryptographically Secure Pseudo-Random Number Generator).

```php
$number = random_int(1, 100); // cryptographically secure random integer
```

`random_int()` is the default choice for anything that matters. It generates unbiased, unpredictable values. Use it for anything related to security, fairness, or where predictability would cause harm.

### `random_bytes()`

Need raw random bytes? `random_bytes()` returns a string of cryptographically secure random bytes.

```php
$bytes = random_bytes(32); // 32 random bytes
$token = bin2hex($bytes);  // 64-character hex string
```

Common uses: API tokens, CSRF tokens, encryption keys, nonces.

## Seeded Random Number Generators

A seeded RNG produces the same sequence of values every time you provide the same seed. This is the foundation of controlled randomness.

```php
mt_srand(42);

echo mt_rand(1, 100); // always 42
echo mt_rand(1, 100); // always 11
echo mt_rand(1, 100); // always 8

// Reset the seed to reproduce the same sequence
mt_srand(42);

echo mt_rand(1, 100); // 42 again
echo mt_rand(1, 100); // 11 again
echo mt_rand(1, 100); // 8 again
```

Important caveat: `mt_srand()` seeds the Mersenne Twister generator used by `mt_rand()`. It does NOT affect `rand()` or `random_int()`. In PHP 8.2+, `mt_srand()` gained a `$mode` parameter for backward compatibility with PHP 7.x seeding behavior.

PHP 8.2 also introduced `\Random\Randomizer` and `\Random\Engine` classes, giving you more explicit control:

```php
use Random\Engine\Mt19937;
use Random\Randomizer;

$engine = new Mt19937(42); // seed 42
$randomizer = new Randomizer($engine);

echo $randomizer->getInt(1, 100); // 42
echo $randomizer->getInt(1, 100); // 11
echo $randomizer->getInt(1, 100); // 8
```

The `Randomizer` class wraps any engine and provides a consistent API. You can swap engines without changing your consuming code.

## Controlled Randomness for Testing

Seeded randomness is invaluable in tests. You can write deterministic tests that verify behavior around random values without mocking the random functions.

```php
use Random\Engine\Mt19937;
use Random\Randomizer;

class Lottery
{
    public function __construct(
        private Randomizer $randomizer
    ) {}

    public function drawWinner(array $players): string
    {
        return $players[$this->randomizer->getInt(0, count($players) - 1)];
    }
}

// Test
it('draws the correct winner with a seeded randomizer', function () {
    $engine = new Mt19937(12345);
    $randomizer = new Randomizer($engine);
    $lottery = new Lottery($randomizer);

    $players = ['Alice', 'Bob', 'Charlie', 'Diana'];

    // With seed 12345, the first getInt call always returns the same index
    expect($lottery->drawWinner($players))->toBe('Charlie');
});
```

This approach is far better than mocking `mt_rand()` or `random_int()`. You're testing real behavior with reproducible inputs. The same seed always produces the same sequence, so your tests are deterministic.

## Shuffling Arrays

`shuffle()` randomizes array order in place, using the Mersenne Twister:

```php
$cards = ['A♠', 'K♠', 'Q♠', 'J♠'];
shuffle($cards);
// $cards is now in random order
```

For reproducible shuffles, you can implement Fisher-Yates using a seeded randomizer:

```php
function seededShuffle(array &$items, Randomizer $randomizer): void
{
    $count = count($items);

    for ($i = $count - 1; $i > 0; $i--) {
        $j = $randomizer->getInt(0, $i);
        [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
    }
}

// Usage
$engine = new Mt19937(42);
$randomizer = new Randomizer($engine);
$deck = ['A♠', 'K♠', 'Q♠', 'J♠', '10♠'];
seededShuffle($deck, $randomizer);

// $deck will always be the same order with seed 42
```

PHP's `shuffle()` has no seed parameter, so this manual approach gives you the control you need.

## Weighted Random Selection

Sometimes you want some outcomes to be more likely than others. A loot drop table, for instance:

```php
function weightedRandom(array $items, Randomizer $randomizer): mixed
{
    $totalWeight = array_sum(array_column($items, 'weight'));
    $roll = $randomizer->getInt(1, $totalWeight);

    foreach ($items as $item) {
        $roll -= $item['weight'];

        if ($roll <= 0) {
            return $item['value'];
        }
    }
}

$lootTable = [
    ['value' => 'Sword of a Thousand Truths', 'weight' => 1],
    ['value' => 'Epic Armor Piece',            'weight' => 5],
    ['value' => 'Health Potion',               'weight' => 30],
    ['value' => 'Gold Coins',                  'weight' => 64],
];

$engine = new Mt19937(time());
$randomizer = new Randomizer($engine);
$drop = weightedRandom($lootTable, $randomizer);
```

Test this with a seeded randomizer to verify the weighting logic:

```php
it('distributes weighted loot correctly', function () {
    $engine = new Mt19937(999);
    $randomizer = new Randomizer($engine);
    $drops = [];

    for ($i = 0; $i < 10000; $i++) {
        $drops[] = weightedRandom($lootTable, $randomizer);
    }

    $goldCount = count(array_filter($drops, fn($d) => $d === 'Gold Coins'));

    // Gold should appear roughly 64% of the time
    expect($goldCount)->toBeBetween(6200, 6600);
});
```

## Cryptographic vs Non-Cryptographic Randomness

This is the most important distinction to understand:

| | Non-Crypto (mt_rand) | Crypto (random_int) |
|---|---|---|
| **Predictable with seed?** | Yes | No |
| **Speed** | Fast | Slower |
| **Use for games** | Yes | Overkill |
| **Use for tokens** | No | Required |
| **Use for shuffling** | Yes | Yes |
| **Use for key generation** | Never | Required |

The rule: if an attacker could gain advantage by predicting your random values, use `random_int()` or `random_bytes()`. If not, `mt_rand()` is fine.

## Real-World Uses

### AB Testing

Assign users to experiment groups deterministically based on their user ID:

```php
function getExperimentGroup(int $userId, string $experiment): string
{
    $seed = crc32($experiment . $userId);
    $engine = new Mt19937($seed);
    $randomizer = new Randomizer($engine);

    return $randomizer->getInt(0, 1) === 0 ? 'control' : 'treatment';
}
```

Same user always gets the same group for the same experiment, without storing assignments in a database.

### Game RNG

A seeded RNG lets players replay battles with identical outcomes for debugging:

```php
class BattleSimulator
{
    private Randomizer $randomizer;

    public function __construct(?int $seed = null)
    {
        $engine = new Mt19937($seed ?? random_int(PHP_INT_MIN, PHP_INT_MAX));
        $this->randomizer = new Randomizer($engine);
    }

    public function simulate(Player $attacker, Player $defender): BattleResult
    {
        $attackRoll = $this->randomizer->getInt(1, 20);
        // deterministic given the same seed
    }
}
```

### Deterministic Testing

Generate test fixtures that look realistic but are perfectly repeatable:

```php
class FixtureFactory
{
    private Randomizer $randomizer;

    public function __construct(int $seed = 42)
    {
        $this->randomizer = new Randomizer(new Mt19937($seed));
    }

    public function createUser(): array
    {
        return [
            'name'  => $this->names()[$this->randomizer->getInt(0, 99)],
            'age'   => $this->randomizer->getInt(18, 80),
            'score' => $this->randomizer->getInt(0, 10000),
        ];
    }
}
```

## PHP 8.2+ and the Future of Random

PHP 8.2 introduced the `Random\Randomizer` class family, which is the future of randomness in PHP:

```php
use Random\Randomizer;

$randomizer = new Randomizer();
$randomizer->getInt(1, 100);       // random int
$randomizer->getBytes(32);         // random bytes
$randomizer->shuffleArray($items); // shuffle array
$randomizer->shuffleBytes($data);  // shuffle string
```

The new API is cleaner, more object-oriented, and makes it trivial to swap between seeded and unseeded modes. It's the recommended approach for new PHP 8.2+ code.

## Summary

| Need | Function | Seeded? |
|---|---|---|
| Quick random number | `mt_rand()` | Yes (`mt_srand`) |
| Secure random number | `random_int()` | No |
| Random bytes/tokens | `random_bytes()` | No |
| Array shuffle | `shuffle()` or `Randomizer::shuffleArray()` | No (use custom Fisher-Yates) |
| Reproducible sequence | `Randomizer` + seeded `Mt19937` | Yes |
| Weighted selection | Custom function + `Randomizer` | Yes |

Controlled randomness turns a testing nightmare into a predictable, debuggable system. Seed your RNG, pass it as a dependency, and your tests become deterministic without sacrificing realism. The `Randomizer` API in PHP 8.2+ makes this cleaner than ever.
