---
title: "Sticker Swapping - Collection Completion Probability With PHP"
description: "Simulate collection completion probability with PHP. Use Monte Carlo methods to solve the coupon collector problem for sticker swapping scenarios."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-12-22 21:00:00"
tags: ["PHP", "Probability", "Statistics", "Simulation", "Collection Problem"]
---

The FIFA World Cup sticker album is a global phenomenon. Fans buy packs of stickers, hoping to complete their collection of every team's roster. The problem is classic: given a set of unique items, how many random packs do you need to collect them all? This is the coupon collector's problem, and PHP is an excellent tool for simulating it.

## The Coupon Collector's Problem

In probability theory, the coupon collector's problem asks: given `n` unique coupons, how many random draws do you expect to need to collect each coupon at least once? The expected value follows a simple formula:

```
E(n) = n × H(n)
```

Where `H(n)` is the nth harmonic number. For a World Cup album with 682 stickers (the actual size of some editions), the expected number of packs is:

```
E(682) ≈ 682 × (ln(682) + γ) ≈ 682 × 7.18 ≈ 4899 individual stickers
```

At 5 stickers per pack, that is about 980 packs. The sticker industry makes a lot of money from this math.

## The Real-World Problem: Matching Wants and Needs

In practice, collectors trade with each other. One person's duplicates fill another person's gaps. The December 2022 PHP Puzzles column presented a concrete coding problem: given two text lists of stickers, one of needed stickers and one of duplicates, find the stickers that appear in both lists.

The input looks like this:

```
FWC 2, 3, 7 9, 13, qat 5, qat 6 10 qat 11, 13, 14, qat 15,
qat 18, 19, ECU 1 2 3, 5, 6, 10, 13 19, SEN 7, 8, sen 11,
sen 19, NED 2, 3, 13, ENG 7, ENG 10, ENG 19,
```

And duplicates:

```
00, FWC 1,4, FWC 12, FWC 17, QAT 1, QAT 3,4 ECU 18 19
GER 2,8, 10, 13,14, 15,  JPN 3, JPN 10, JPN 14 15 19,
JPN 20, BEL 3 9 12 14 15 16 19, Can 5,7,17,19,
```

The format is inconsistent. Country abbreviations are uppercase or lowercase. Numbers may follow directly or be separated by commas. Spaces are irregular. Lines wrap unpredictably.

## Cleaning the Input

The first step is normalization. Convert everything to uppercase, replace commas with spaces, and collapse multiple whitespace characters:

```php
function cleanInput(string $in): string
{
    $out = strtoupper($in);
    $out = str_replace(',', ' ', $out);
    return preg_replace('/\s+/', ' ', $out);
}
```

An unexpected benefit of `preg_replace` with `\s+`: it matches newlines and line breaks, collapsing multi-line input into a single clean line.

## Building Arrays with Regex

The cleaned string looks like:

```
FWC 2 3 7 9 13 QAT 5 QAT 6 10 QAT 11 13 14 QAT 15 QAT 18 19
```

The pattern is a three-letter country code followed by one or more numbers. A regular expression captures this structure:

```php
function toStickerList(string $input): array
{
    $input = strtoupper($input);
    $input = str_replace(',', ' ', $input);
    $input = preg_replace('/\s+/', ' ', $input);

    preg_match_all('/([A-Z]+)\s([0-9 ]+)/', $input, $match);

    $result = [];
    foreach ($match[0] as $i => $m) {
        $country = $match[1][$i];
        $numbers = explode(' ', trim($match[2][$i]));
        foreach ($numbers as $number) {
            $result[] = $country . ' ' . $number;
        }
    }

    sort($result, SORT_NATURAL);
    return $result;
}
```

The regex `([A-Z]+)\s([0-9 ]+)` captures the country code in group 1 and all following numbers in group 2. For each match, explode the numbers and combine each one with the country code.

## Finding Common Stickers

PHP's standard library has a function for exactly this problem:

```php
$need = toStickerList($needsInput);
$dupes = toStickerList($dupesInput);

$swap = array_intersect($need, $dupes);

echo "You can swap the following: " . implode(', ', $swap);
```

`array_intersect` returns the elements common to both arrays. For custom comparison logic, `array_uintersect` accepts a callback:

```php
$swap = array_uintersect($need, $dupes, function ($a, $b) {
    return strcasecmp($a, $b);
});
```

You could also use `soundex` or `levenshtein` for fuzzy matching if the input data is noisy.

## Simulating the Coupon Collector's Problem

The analytical formula gives you the expected value. A Monte Carlo simulation in PHP shows you the distribution. How many packs might you need on a lucky day? How many on an unlucky one?

```php
function simulateAlbumCompletion(
    int $totalStickers,
    int $stickersPerPack = 5,
    int $trials = 10000
): array {
    $results = [];

    for ($trial = 0; $trial < $trials; $trial++) {
        $collected = [];
        $packs = 0;

        while (count($collected) < $totalStickers) {
            $packs++;
            $pack = [];
            for ($i = 0; $i < $stickersPerPack; $i++) {
                $sticker = random_int(1, $totalStickers);
                $pack[] = $sticker;
            }
            $collected = array_unique(
                array_merge($collected, $pack)
            );
        }
        $results[] = $packs;
    }

    return [
        'mean' => array_sum($results) / count($results),
        'min' => min($results),
        'max' => max($results),
        'median' => (function ($arr) {
            sort($arr);
            $mid = intdiv(count($arr), 2);
            return $arr[$mid];
        })($results),
        'percentile_95' => (function ($arr) {
            sort($arr);
            return $arr[(int) ceil(0.95 * count($arr)) - 1];
        })($results),
    ];
}

$stats = simulateAlbumCompletion(682, 5, 5000);

echo "Expected packs: " . round($stats['mean']) . PHP_EOL;
echo "Min: {$stats['min']}, Max: {$stats['max']}" . PHP_EOL;
echo "Median: {$stats['median']}" . PHP_EOL;
echo "95th percentile: {$stats['percentile_95']}" . PHP_EOL;
```

Running this simulation reveals that while the expected value is around 980 packs, the 95th percentile can be significantly higher. Some unlucky collectors need 1500+ packs. The long tail of the coupon collector distribution is punishing.

## The Effect of Trading

Trading changes the math dramatically. Instead of one person collecting all 682 stickers independently, two people trading duplicates effectively doubles their combined draw rate.

```php
function simulateWithTrading(
    int $totalStickers,
    int $stickersPerPack = 5,
    int $numCollectors = 2,
    int $trials = 5000
): array {
    $results = [];

    for ($trial = 0; $trial < $trials; $trial++) {
        $collected = array_fill(0, $numCollectors, []);
        $packs = 0;

        while (true) {
            $packs++;
            for ($c = 0; $c < $numCollectors; $c++) {
                $pack = [];
                for ($i = 0; $i < $stickersPerPack; $i++) {
                    $pack[] = random_int(1, $totalStickers);
                }
                $collected[$c] = array_unique(
                    array_merge($collected[$c], $pack)
                );
            }

            // Trading: pool all unique stickers
            $pool = [];
            foreach ($collected as $c) {
                $pool = array_unique(array_merge($pool, $c));
            }

            // Everyone gets the pooled set
            if (count($pool) >= $totalStickers) {
                break;
            }
            foreach ($collected as &$c) {
                $c = $pool;
            }
            unset($c);
        }
        $results[] = $packs;
    }

    return [
        'mean' => array_sum($results) / count($results),
        'min' => min($results),
        'max' => max($results),
    ];
}
```

With two collectors trading, the expected packs per person drops dramatically. Instead of 980 packs each, they might complete their albums in 600 packs each. More traders means faster completion for everyone. This is why sticker swap events are so popular.

## Real-World Applications

The coupon collector's problem appears everywhere:

- **Gacha games** and loot boxes in video games
- **Pokemon card packs** and other trading card games
- **Sales territory coverage** for field sales teams
- **Network sampling** and packet capture analysis
- **Quality assurance** for testing coverage across environments

## Improving the Simulation

The basic simulation assumes equal probability for every sticker. Real sticker packs have distribution quirks: some stickers are common, others are rare. To model this:

```php
function simulateWithWeightedRarity(
    int $totalStickers,
    array $rarityWeights,
    int $stickersPerPack = 5,
    int $trials = 5000
): array {
    // rarityWeights maps sticker ID => relative probability
    $results = [];

    for ($trial = 0; $trial < $trials; $trial++) {
        $collected = [];
        $packs = 0;

        while (count($collected) < $totalStickers) {
            $packs++;
            for ($i = 0; $i < $stickersPerPack; $i++) {
                $rand = random_int(1, array_sum($rarityWeights));
                $cumulative = 0;
                foreach ($rarityWeights as $id => $weight) {
                    $cumulative += $weight;
                    if ($rand <= $cumulative) {
                        $collected[$id] = true;
                        break;
                    }
                }
            }
        }
        $results[] = $packs;
    }

    return $results;
}
```

With rare stickers weighted at 0.5x probability and common ones at 2x, the expected completion time stretches further.

## The Shared Birthday Day Challenge

The same probabilistic thinking applies to the companion challenge: given two people's birthdays, determine:

- Were they born on the same day of the week?
- Are they born in the same meteorological season?
- Were they both born on an even or odd day?
- What is the difference in ages in years, months, and days?
- In a given calendar year, how many days are between their birthdays?

```php
function compareBirthdays(string $date1, string $date2): array
{
    $d1 = new \DateTimeImmutable($date1);
    $d2 = new \DateTimeImmutable($date2);

    return [
        'same_day_of_week' => $d1->format('l') === $d2->format('l'),
        'same_season' => getSeason($d1) === getSeason($d2),
        'both_even_or_odd' =>
            ($d1->format('d') % 2) === ($d2->format('d') % 2),
        'age_difference' => $d1->diff($d2)->format('%y years, %m months, %d days'),
        'days_between_birthdays' => daysBetweenBirthdays($d1, $d2),
    ];
}

function getSeason(\DateTimeImmutable $date): string
{
    $month = (int) $date->format('n');
    return match (true) {
        $month >= 3 && $month <= 5 => 'Spring',
        $month >= 6 && $month <= 8 => 'Summer',
        $month >= 9 && $month <= 11 => 'Fall',
        default => 'Winter',
    };
}

function daysBetweenBirthdays(
    \DateTimeImmutable $d1,
    \DateTimeImmutable $d2
): int {
    $currentYear = (int) (new \DateTimeImmutable())->format('Y');
    $bday1 = new \DateTimeImmutable(
        sprintf('%d-%s', $currentYear, $d1->format('m-d'))
    );
    $bday2 = new \DateTimeImmutable(
        sprintf('%d-%s', $currentYear, $d2->format('m-d'))
    );
    return abs((int) $bday1->diff($bday2)->format('%a'));
}
```

## Conclusion

Probability problems like the coupon collector are ideal for PHP simulation. The language's built-in array functions and `random_int` make it straightforward to model stochastic processes. The results are intuitive: collecting all items is harder than it looks, and trading makes everyone better off.

The sticker swapping problem also demonstrates a valuable coding lesson: clean your input data first. Normalizing inconsistent formatting before processing turns a messy problem into a clean `array_intersect` call.

Next time you open a pack of stickers, remember the math. It is not luck. It is probability.
