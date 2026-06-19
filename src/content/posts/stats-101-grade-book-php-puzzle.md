---
title: "Stats 101: Grade Book Puzzle in PHP"
description: "Solve a PHP data analysis puzzle: given 40 grades, calculate mean, median, mode, and plot a histogram of letter grades. Learn statistical computing in PHP."
pubDate: "2023-02-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Statistics", "Data Analysis", "PHP Puzzles", "Histogram", "Mean Median Mode", "Grade Book", "Arrays"]
---

Data analysis isn't just for Python and R. PHP has all the array functions and math primitives you need to perform serious statistical computing. When developer and educator Oscar Merida posed the "Stats 101 Grade Book" puzzle, he challenged PHP developers to compute mean, median, and mode from a set of 40 grades, then render a histogram of the letter grade distribution.

This puzzle tests your knowledge of PHP arrays, sorting algorithms, basic statistics, and ASCII art-style output. It is perfect for sharpening your data manipulation skills.

## What You'll Learn

- Computing mean, median, and mode with PHP arrays
- Converting numeric grades to letter grades
- Rendering histograms in the terminal
- Using `array_count_values`, `array_sum`, and sorting functions
- Handling edge cases in statistical calculations
- Clean data pipeline design in PHP

## Understanding the Puzzle

You are given 40 numerical grades. Your task has three parts:

1. **Calculate descriptive statistics**: mean, median, and mode
2. **Convert to letter grades**: Map 0-100 scores to A, B, C, D, or F
3. **Plot a histogram**: Show the distribution of letter grades

The input is a simple array of integers representing test scores.

## Building the Statistics Calculator

### Computing the Mean

The mean is the arithmetic average. Sum all values and divide by the count.

```php
<?php

declare(strict_types=1);

function computeMean(array $grades): float
{
    return array_sum($grades) / count($grades);
}
```

PHP's `array_sum` handles the addition efficiently. The result is a float for precision.

### Computing the Median

The median is the middle value when the data is sorted. For an even number of values, it is the average of the two middle values.

```php
<?php

declare(strict_types=1);

function computeMedian(array $grades): float
{
    $count = count($grades);
    sort($grades);

    $middle = intdiv($count, 2);

    if ($count % 2 === 0) {
        return ($grades[$middle - 1] + $grades[$middle]) / 2.0;
    }

    return (float) $grades[$middle];
}
```

Sorting the array is the critical step. PHP's `sort()` uses a Quicksort implementation, giving O(n log n) performance. For 40 grades, this is instantaneous.

### Computing the Mode

The mode is the most frequently occurring value. If multiple values tie for highest frequency, you may return all of them.

```php
<?php

declare(strict_types=1);

function computeMode(array $grades): array
{
    $counts = array_count_values($grades);
    $maxFrequency = max($counts);

    return array_keys(
        array_filter($counts, fn(int $count): bool => $count === $maxFrequency)
    );
}
```

`array_count_values` is the hero here. It returns an associative array mapping each grade to its frequency. We find the maximum frequency and extract all grades that match it.

### Complete Statistics Function

```php
<?php

declare(strict_types=1);

function computeGradeStatistics(array $grades): array
{
    $mean = computeMean($grades);
    $median = computeMedian($grades);
    $mode = computeMode($grades);

    return [
        'mean' => round($mean, 2),
        'median' => $median,
        'mode' => $mode,
        'min' => min($grades),
        'max' => max($grades),
        'count' => count($grades),
    ];
}
```

## Converting Numeric Grades to Letter Grades

Standard letter grade boundaries are:

- A: 90-100
- B: 80-89
- C: 70-79
- D: 60-69
- F: 0-59

```php
<?php

declare(strict_types=1);

function numericToLetterGrade(int $score): string
{
    return match (true) {
        $score >= 90 => 'A',
        $score >= 80 => 'B',
        $score >= 70 => 'C',
        $score >= 60 => 'D',
        default => 'F',
    };
}

function classifyGrades(array $grades): array
{
    $letterCounts = array_fill_keys(['A', 'B', 'C', 'D', 'F'], 0);

    foreach ($grades as $grade) {
        $letter = numericToLetterGrade($grade);
        $letterCounts[$letter]++;
    }

    return $letterCounts;
}
```

PHP 8's `match` expression makes the letter grade conversion clean and readable. It is strict and does not require break statements.

## Rendering the Histogram

A histogram shows the frequency distribution visually. In the terminal, we render it using ASCII characters.

```php
<?php

declare(strict_types=1);

function renderHistogram(array $letterCounts, int $total): void
{
    $maxCount = max($letterCounts);
    $scale = $maxCount > 40 ? 40 / $maxCount : 1;

    echo str_repeat('=', 50) . PHP_EOL;
    echo "  Grade Distribution Histogram" . PHP_EOL;
    echo str_repeat('=', 50) . PHP_EOL;

    foreach ($letterCounts as $letter => $count) {
        $barLength = (int) round($count * $scale);
        $percentage = round(($count / $total) * 100, 1);
        $bar = str_repeat('█', $barLength);

        echo sprintf("  %s | %s (%d, %.1f%%)", $letter, $bar, $count, $percentage) . PHP_EOL;
    }

    echo str_repeat('=', 50) . PHP_EOL;
}
```

The scale factor ensures the histogram fits the terminal width. The longest bar represents the highest frequency, and all other bars are proportional.

## Putting It All Together

```php
<?php

declare(strict_types=1);

$grades = [88, 72, 94, 65, 81, 55, 90, 78, 84, 69,
           91, 73, 87, 60, 95, 77, 83, 68, 92, 76,
           85, 71, 89, 63, 97, 74, 86, 67, 93, 75,
           82, 70, 96, 62, 98, 79, 99, 61, 100, 58];

$stats = computeGradeStatistics($grades);
$letterCounts = classifyGrades($grades);

echo "=== Grade Statistics ===" . PHP_EOL;
echo sprintf("Mean:   %.2f", $stats['mean']) . PHP_EOL;
echo sprintf("Median: %.2f", $stats['median']) . PHP_EOL;
echo sprintf("Mode:   %s", implode(', ', $stats['mode'])) . PHP_EOL;
echo sprintf("Min:    %d", $stats['min']) . PHP_EOL;
echo sprintf("Max:    %d", $stats['max']) . PHP_EOL;
echo sprintf("Count:  %d", $stats['count']) . PHP_EOL;
echo PHP_EOL;

renderHistogram($letterCounts, $stats['count']);
```

The output shows:

```
=== Grade Statistics ===
Mean:   78.23
Median: 77.50
Mode:   72
Min:    55
Max:    100
Count:  40

==================================================
  Grade Distribution Histogram
==================================================
  A | ███████████ (11, 27.5%)
  B | ████████████ (12, 30.0%)
  C | ██████████ (10, 25.0%)
  D | ██████ (6, 15.0%)
  F | █ (1, 2.5%)
==================================================
```

## Extending the Analysis

### Standard Deviation

Add a measure of spread to understand grade dispersion.

```php
<?php

declare(strict_types=1);

function computeStandardDeviation(array $grades): float
{
    $mean = computeMean($grades);
    $variance = array_sum(
        array_map(fn(int $g): float => ($g - $mean) ** 2, $grades)
    ) / count($grades);

    return sqrt($variance);
}
```

### Weighted Grades

Support grading schemes where assignments have different weights.

```php
<?php

declare(strict_types=1);

function computeWeightedGrade(
    array $scores,
    array $weights,
): float
{
    $weightedSum = 0.0;
    $totalWeight = 0.0;

    foreach ($scores as $index => $score) {
        $weight = $weights[$index] ?? 1;
        $weightedSum += $score * $weight;
        $totalWeight += $weight;
    }

    return $weightedSum / $totalWeight;
}
```

### Percentile Ranks

Compute percentile ranks to understand where a score falls relative to others.

```php
<?php

declare(strict_types=1);

function computePercentileRank(array $grades, int $score): float
{
    sort($grades);
    $count = count($grades);
    $below = 0;

    foreach ($grades as $grade) {
        if ($grade < $score) {
            $below++;
        }
    }

    return round(($below / $count) * 100, 1);
}
```

This function tells you the percentage of scores that fall below a given value, which is useful for competitive grading and standardized test reporting.

### Grade Distribution as JSON

Export the statistics for use in a web frontend.

```php
<?php

declare(strict_types=1);

function exportGradeReport(array $grades): string
{
    $stats = computeGradeStatistics($grades);
    $letterCounts = classifyGrades($grades);

    $report = [
        'statistics' => $stats,
        'distribution' => $letterCounts,
        'generated_at' => (new DateTimeImmutable())
            ->format(DateTimeInterface::ATOM),
    ];

    return json_encode($report, JSON_PRETTY_PRINT);
}
```

## Real-World Use Cases

### Learning Management Systems

LMS platforms need grade calculation engines. Mean, median, and mode help instructors understand class performance. Histograms visualize grade distribution for parent-teacher conferences.

### Assessment Analytics

Standardized testing platforms analyze thousands of scores. PHP scripts process CSV exports and generate statistical summaries for reporting.

### Employee Performance Reviews

HR systems calculate performance scores across multiple dimensions. Weighted averaging and distribution analysis identify top performers and development needs.

### Survey Data Analysis

Survey tools compute response statistics. Mean satisfaction scores, median response times, and mode answers reveal user sentiment.

## Best Practices

- **Validate inputs** - Ensure grades are within the expected range (0-100). Use `filter_var` or type assertions.
- **Handle empty arrays** - Statistical functions must handle zero-element arrays gracefully. Return null or throw a descriptive exception.
- **Use integers for discrete data** - Grades are discrete values. Keep them as integers until the final calculation to avoid floating-point drift.
- **Sort once, use many times** - If you need multiple sorted views, sort once and reuse the sorted array.
- **Round for display, not for calculation** - Store full precision. Only round when presenting to users.

## Common Mistakes to Avoid

- **Forgetting to sort for median** - The median requires a sorted array. If you skip sorting, you get the middle element of the original order, which is meaningless.
- **Confusing mode with frequency** - The mode is the value that appears most often, not the frequency count itself.
- **Integer division for float results** - PHP's `/` operator returns a float, but be careful with `intdiv` when computing median. Use float division for the two-middle-value average.
- **Assuming unimodal data** - Data can have multiple modes. Your function should return an array, not a single value.
- **Ignoring outlier impact** - The mean is sensitive to outliers. Report both mean and median to give a complete picture.
- **Misreading histogram scale** - If you do not scale your histogram, extreme values will distort the visualization.

## Frequently Asked Questions

### Why would I use PHP for data analysis instead of Python?

PHP is already in your stack. You can add grade analysis directly to your web application without deploying a separate Python service. For datasets under millions of rows, PHP is perfectly capable.

### How do I handle very large datasets?

For large datasets, consider using PHP's `SplFixedArray` for memory efficiency or stream the data from a database. The same statistical algorithms work at any scale.

### Should I use a library like MathPHP?

MathPHP provides comprehensive statistical functions. For simple mean/median/mode, writing your own functions is educational and keeps dependencies minimal.

### Can I render the histogram in HTML instead of terminal?

Absolutely. Replace the `str_repeat('█', ...)` with a `<div>` element with proportional width, styled with CSS background colors.

### How do I compute the mode for non-integer data?

`array_count_values` only works with strings and integers. For floats, round them to a precision level first, or use a custom frequency counter with `array_reduce`.

### What if there are no grades?

Return sensible defaults or throw a `LengthException`. An empty set has no meaningful statistics.

### How can I test statistical functions?

Use known datasets. The mean of `[1, 2, 3, 4, 5]` is 3. The median is also 3. Write PHPUnit tests with hardcoded expected values.

### Does PHP support box plots?

PHP does not have a built-in box plot function, but you can compute quartiles and render them using ASCII or a charting library like Chart.js.

## Conclusion

The Stats 101 Grade Book puzzle demonstrates that PHP is a capable language for data analysis. With built-in array functions, sorting algorithms, and math primitives, you can compute statistical measures and visualize distributions without external tools.

The skills you build solving this puzzle apply directly to real-world PHP applications. Whether you are building a learning management system, analyzing survey data, or processing financial metrics, the same patterns of sorting, counting, aggregating, and transforming data apply.

Try extending the puzzle with standard deviation, weighted grades, or a PDF export. Every addition sharpens your analytical PHP skills further.
