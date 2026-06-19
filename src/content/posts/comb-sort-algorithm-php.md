---
title: "Comb Sort Algorithm in PHP - Faster Than Bubble Sort"
description: "Learn how comb sort improves on bubble sort by comparing non-adjacent elements. Includes PHP implementation, shrink factor analysis, and benchmarking data."
pubDate: "2023-09-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Algorithms", "Sorting", "Comb Sort", "Bubble Sort", "Performance", "Data Structures"]
selected: false
---

Bubble sort is the first sorting algorithm most developers learn. It is intuitive: compare adjacent elements, swap them if they are in the wrong order, repeat until no swaps are needed. But intuitive does not mean efficient. Bubble sort struggles with a specific problem: small values that start at the end of the array.

These small values, called "turtles," move only one position toward the front on each pass. If the smallest element starts at position N, it takes N passes to reach the front. For an array of 10,000 elements, that is 10,000 passes just to fix one value.

Comb sort addresses this limitation with a deceptively simple change: instead of comparing adjacent elements, it compares elements separated by a "gap." The gap shrinks with each pass until it reaches one, at which point the algorithm behaves like bubble sort. By that time, the array is nearly sorted, and bubble sort's weakness — moving turtles — no longer matters.

## What You'll Learn

- The turtle problem that makes bubble sort slow on large arrays
- How comb sort uses a shrinking gap to move elements quickly
- Building a PHP implementation of comb sort
- Finding the optimal shrink factor through benchmarking
- Comparing comb sort performance against bubble sort
- Sorting 10,000 elements 200x faster

## The Turtle Problem

Consider an array where the smallest element is in the last position. With bubble sort, each pass moves it one slot closer to the beginning. You need N passes just to move that single element to its correct position. These problematic small elements are called "turtles" — they are slow to move.

Large values near the start of the array ("rabbits") move quickly. A large value at position 0 can reach the end in a single pass. The asymmetry is the problem: rabbits are fast, turtles are glacially slow.

Comb sort breaks this asymmetry by comparing non-adjacent elements. On the first pass, it compares elements that are half the array's length apart. A turtle in the last position can leap halfway to the front in a single comparison.

## How Comb Sort Works

The algorithm starts with a gap value, typically calculated as the array length divided by a shrink factor. It then performs a bubble-sort-like pass, but compares `$list[$i]` with `$list[$i + $gap]` instead of `$list[$i]` with `$list[$i + 1]`. After each pass, the gap is reduced by the shrink factor until it reaches one.

```php
function comb_sort(array &$list): void
{
    $max = count($list);
    $shrinkFactor = 2;
    $gap = (int) ceil($max / $shrinkFactor);

    do {
        $swapped = false;

        for ($i = 0; $i < $max - $gap; $i++) {
            $target = $i + $gap;

            if ($list[$i] > $list[$target]) {
                $swapped = true;
                swap_elements($list, $i, $target);
            }
        }

        $gap = (int) ceil($gap / $shrinkFactor);
    } while ($swapped || $gap > 1);
}
```

### Breaking Down the Implementation

The outer `do...while` loop continues as long as swaps are happening or the gap is greater than one. The inner `for` loop iterates through the array, comparing elements `$gap` positions apart.

The `$target` variable calculates the index of the element being compared. If the current element is larger than the target, they are swapped.

After each full pass, the gap is recalculated by dividing by the shrink factor. With a shrink factor of 2 and an array of 100 elements, the gaps would be: 50, 25, 13, 7, 4, 2, 1.

The loop runs at least once with a gap of 1 (pure bubble sort) to ensure the array is fully sorted. This final pass requires few swaps because the earlier passes eliminated the turtles.

### Edge Case: Gap Reaching Zero

A subtle bug occurs when the gap calculation drops to zero. If you use `floor()` instead of `ceil()`, and the gap is less than the shrink factor, it becomes zero. With a gap of zero, the algorithm compares each element with itself, performs no swaps, and exits with a partially sorted array.

Always use `ceil()` when calculating the gap to ensure it never drops below one.

## Finding the Optimal Shrink Factor

The original comb sort paper recommended a shrink factor of 1.3 after empirical testing on over 200,000 random lists. But is that the best value for PHP? Benchmarking tells the story.

```php
function benchmarkShrinkFactors(
    int $arraySize,
    array $factors
): array {
    $results = [];

    foreach ($factors as $factor) {
        $times = [];

        for ($run = 0; $run < 50; $run++) {
            $array = [];
            for ($i = 0; $i < $arraySize; $i++) {
                $array[] = rand(0, $arraySize);
            }

            $start = microtime(true);
            comb_sort_with_factor($array, $factor);
            $times[] = microtime(true) - $start;
        }

        $results[$factor] = [
            'mean' => array_sum($times) / count($times),
            'fastest' => min($times),
            'slowest' => max($times),
        ];
    }

    return $results;
}
```

Benchmarking against an array of 5,000 elements:

| Shrink Factor | Mean Time (seconds) |
|---|---|
| 8 | 1.0361 |
| 4 | 0.7492 |
| 2 | 0.4758 |
| 1.8 | 0.1352 |
| 1.7 | 0.1146 |
| 1.6 | 0.08339 |
| 1.5 | 0.03946 |
| 1.4 | 0.00805 |
| **1.3** | **0.00638** |
| 1.2 | 0.00750 |
| 1.1 | 0.01098 |

The data confirms the original research. A shrink factor of 1.3 produces the fastest sorts. Factors above 1.3 are too aggressive — they shrink the gap too quickly, leaving too many turtles for the final pass. Factors below 1.3 shrink too slowly, making unnecessary comparisons.

## Performance Comparison: Comb Sort vs. Bubble Sort

With the optimal shrink factor established, we can compare comb sort against bubble sort across different array sizes.

| Array Size | Bubble Sort Mean | Comb Sort Mean | Times Faster |
|---|---|---|---|
| 1,000 | 0.0321 s | 0.00105 s | 30x |
| 5,000 | 0.8414 s | 0.00634 s | 132x |
| 10,000 | 3.3420 s | 0.01420 s | 235x |

The performance gap widens dramatically as the array grows. For 10,000 elements, comb sort is over 200 times faster than bubble sort. This is not incremental improvement — it is a different performance class.

The reason is the turtle problem compounding. In a 10,000-element array, there are likely many turtles. Bubble sort takes approximately N^2/2 comparisons. Comb sort with optimal gap sequencing approaches N log N performance for average cases.

## The Comb Sort Pseudocode

For reference, here is the algorithm in pseudocode form, which makes the gap logic explicit:

```
function comb_sort(array A):
    max = length(A)
    shrink = 1.3
    gap = max

    while gap > 1 or swapped:
        gap = floor(gap / shrink)
        if gap < 1:
            gap = 1
        
        swapped = false
        i = 0
        while i + gap < max:
            if A[i] > A[i + gap]:
                swap(A[i], A[i + gap])
                swapped = true
            i = i + 1
```

The critical detail is the `while` condition: it continues as long as the gap is greater than one OR swaps are still happening. When the gap is one and no swaps occur, the array is sorted and the loop exits.

## Benchmarking Methodology

The benchmarks presented in the earlier tables require rigorous methodology to be meaningful. Each test should:

1. Generate a fresh random array for each run to avoid caching effects
2. Run multiple iterations (50+) and collect fastest, slowest, and mean times
3. Test across multiple array sizes to understand scaling behavior
4. Exclude array generation time from the measurement

```php
function benchmarkSort(
    callable $sortFunction,
    int $arraySize,
    int $iterations = 50
): array {
    $times = [];

    for ($run = 0; $run < $iterations; $run++) {
        $array = [];
        for ($i = 0; $i < $arraySize; $i++) {
            $array[] = rand(0, $arraySize * 10);
        }

        $arrayCopy = $array;
        $start = hrtime(true);
        $sortFunction($arrayCopy);
        $times[] = hrtime(true) - $start;
    }

    return [
        'mean' => array_sum($times) / count($times) / 1e9,
        'fastest' => min($times) / 1e9,
        'slowest' => max($times) / 1e9,
    ];
}
```

Using `hrtime()` instead of `microtime()` provides nanosecond precision, which matters when measuring algorithms that complete in milliseconds.

## Real-World Use Cases

**Embedded Systems.** Comb sort's in-place sorting uses O(1) extra memory. For systems with constrained RAM, this is preferable to merge sort or quicksort, which require O(N) auxiliary space.

**Educational Settings.** Comb sort demonstrates how a simple algorithmic change — comparing non-adjacent elements — produces dramatic performance improvements. It is an excellent teaching tool for introducing the concept of algorithmic complexity.

**Pre-Sorting for Complex Algorithms.** Comb sort can quickly bring an array to near-order before a more sophisticated algorithm finishes the job. This hybrid approach is used in production sorting libraries.

**Small-to-Medium Datasets.** For arrays under 100,000 elements, comb sort's simplicity and low overhead make it competitive with more complex algorithms.

## Best Practices

**Always use 1.3 as the shrink factor.** The empirical research is clear. Values above 1.5 waste passes. Values below 1.2 waste comparisons.

**Use ceil() to prevent zero gaps.** A zero gap means no sorting happens. Always round the gap up to the nearest integer.

**Implement swap_elements as a separate function.** Keeping the swap logic isolated makes the sorting algorithm easier to read and test.

**Benchmark against your actual data.** Random data is not the same as nearly-sorted data or reverse-sorted data. Test with data that resembles your production workload.

## Common Mistakes to Avoid

**Using floor() for gap calculation.** This causes the gap to reach zero for small arrays, producing incorrect results.

**Forgetting the final gap-1 pass.** The algorithm must run at least once with gap equal to one to verify the array is sorted.

**Choosing a shrink factor smaller than 1.** Shrink factors must be greater than one. A value of 0.5 would increase the gap each pass, never reaching one.

**Not resetting swapped to false each iteration.** Without this, the outer loop runs only once and exits before the array is sorted.

## Frequently Asked Questions

**Is comb sort faster than quicksort?**
For most datasets, quicksort is faster. Comb sort's advantage is simplicity and guaranteed O(N log N) worst-case performance with the right gap sequence. Quicksort can degrade to O(N^2) with poor pivot selection.

**Does comb sort work with associative arrays?**
The implementation shown works on sequential integer keys. For associative arrays, extract the values into a list, sort, and reconstruct.

**Can comb sort be parallelized?**
Yes. Different sections of the array can be processed in parallel, similar to parallel bubble sort. The gap pattern makes it easier to partition than bubble sort.

**What is the space complexity?**
O(1). Comb sort sorts in place using a constant amount of extra memory.

**Is there a built-in PHP function for comb sort?**
No. PHP's `sort()` function uses a compiled quicksort implementation. For PHP code, comb sort is an educational exercise or a fallback when the built-in sort is unavailable.

## Conclusion

Comb sort transforms bubble sort from a textbook curiosity into a practical algorithm by solving the turtle problem. The change is minimal — compare non-adjacent elements with a shrinking gap — but the performance impact is dramatic: over 200x faster for 10,000 elements.

The optimal shrink factor of 1.3 is not arbitrary. It is the result of empirical testing across hundreds of thousands of random arrays. Use it. Do not guess.

Next time you reach for a sorting implementation, consider comb sort. It is simple enough to implement from memory, efficient enough for most practical purposes, and a testament to how a single insight can transform an algorithm's performance.
