---
title: "Shellsort Algorithm in PHP: Implementation Guide"
description: "Master Shellsort in PHP with complete implementation, benchmarks, and gap sequence optimization. Compare Ciura gaps vs exponential gaps with performance data."
pubDate: "2023-11-20 23:45:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Shellsort", "Sorting Algorithms", "Algorithms", "PHP Puzzles", "Data Structures", "Performance", "PHP 8.x"]
selected: false
---

Shellsort is one of those elegant algorithms that improves on a classic by recognizing its fundamental weakness. Just as Comb Sort improves Bubble Sort by comparing distant elements, Shellsort improves Insertion Sort by first comparing items far apart in the array, then gradually reducing the gap until adjacent elements are compared.

The result is an algorithm that, while not as fast as quicksort for random data, delivers consistent O(n log n) performance with remarkably simple code.

## What You'll Learn

- How Shellsort improves on Insertion Sort through gap-based comparison
- Implementing Shellsort in PHP with exponential gap sequences
- Optimizing with Ciura's experimentally derived gap sequence
- Benchmarking Shellsort against Bubble, Insertion, and Comb sorts
- Understanding how gap selection affects performance characteristics

## Understanding Shellsort

Insertion Sort works by maintaining a sorted portion at the beginning of the array. On each pass, it takes the next unsorted element and inserts it into the correct position within the sorted portion. This requires many comparisons between adjacent elements, especially when a small element is near the end of a large array.

Shellsort solves this by comparing elements that are a "gap" distance apart. Early passes use large gaps to quickly move elements close to their final positions. Subsequent passes use smaller gaps for fine-tuning. The final pass uses a gap of 1, which is a standard insertion sort — but by this point, the array is nearly sorted, so few comparisons are needed.

## The Algorithm

The pseudocode for Shellsort is straightforward:

```
for each gap in gaps:
    for i = gap; i < n; i += 1:
        temp = a[i]
        for j = i; j >= gap && a[j - gap] > temp; j -= gap:
            a[j] = a[j - gap]
        a[j] = temp
```

The outer loop iterates through a sequence of decreasing gaps. For each gap, we perform a gapped insertion sort — comparing elements that are `gap` positions apart instead of adjacent.

### Tracing Through an Example

Consider this array of eight elements with an initial gap of 4:

```
9  15  24  19  2  1  3  21
```

With gap 4, we compare elements four positions apart:

```
9 -------- 2
15 -------- 1
24 -------- 3
19 -------- 21
```

After sorting each pair:

```
2  1  3  19  9  15  24  21
```

Gap 2 splits the array into two interleaved sequences. After sorting each:

```
2 - 3 -- 9 -- 24
1 - 15 - 19 - 21
```

Result: `2  1  3  15  9  19  24  21`

Finally, gap 1 is a standard insertion sort. But notice how the array is already nearly sorted — only a few swaps are needed to complete the sort.

## PHP Implementation

### Exponential Gap Sequence

The original Shellsort uses gaps that halve each time, starting from `n/2`:

```php
<?php

function swap_elems(array &$list, int $i, int $j): void
{
    if (!array_key_exists($i, $list) || !array_key_exists($j, $list)) {
        throw new InvalidArgumentException("Invalid index specified");
    }

    $tmp = $list[$i];
    $list[$i] = $list[$j];
    $list[$j] = $tmp;
}

function shell_sort(array &$list): void
{
    $gap = intdiv(count($list), 2);

    while ($gap > 0) {
        $i = $gap;

        while ($i < count($list)) {
            for (
                $j = $i;
                $j >= $gap && $list[$j - $gap] > $list[$j];
                $j -= $gap
            ) {
                swap_elems($list, $j, $j - $gap);
            }

            ++$i;
        }

        $gap = intdiv($gap, 2);
    }
}
```

Using `intdiv` (PHP 7+) ensures integer division for the gap calculation.

### Ciura's Gap Sequence

The gap sequence dramatically affects performance. Ciura's experimentally derived sequence minimizes the average number of comparisons:

```
1, 4, 10, 23, 57, 132, 301, 701
```

```php
<?php

function shell_sort_ciura(array &$list): void
{
    $data = [1, 4, 10, 23, 57, 132, 301, 701];
    $gaps = array_reverse($data);

    foreach ($gaps as $gap) {
        if ($gap > count($list)) {
            continue;
        }

        $i = $gap;

        while ($i < count($list)) {
            for (
                $j = $i;
                $j >= $gap && $list[$j - $gap] > $list[$j];
                $j -= $gap
            ) {
                swap_elems($list, $j, $j - $gap);
            }

            ++$i;
        }
    }
}
```

The gaps are processed in descending order by reversing the array. Arrays smaller than the current gap skip that iteration.

## Benchmarking Results

Performance measurements across different array sizes, showing mean times from multiple runs:

### Exponential Gaps

| Array Size | Fastest | Slowest | Mean |
|---|---|---|---|
| 1,000 | 0.02035s | 0.03222s | 0.02462s |
| 5,000 | 0.1704s | 0.2394s | 0.2003s |
| 10,000 | 0.3968s | 0.9712s | 0.6082s |

### Ciura's Gaps

| Array Size | Fastest | Slowest | Mean |
|---|---|---|---|
| 1,000 | 0.01789s | 0.03301s | 0.02097s |
| 5,000 | 0.1333s | 0.2012s | 0.1594s |
| 10,000 | 0.3559s | 0.5008s | 0.4106s |

### Algorithm Comparison

Ciura's sequence consistently outperforms exponential gaps, with the gap widening as array size increases. Both Shellsort variants outperform Bubble and Insertion sorts significantly:

| Sort | 1,000 | 5,000 | 10,000 |
|---|---|---|---|
| Bubble | 0.0321s | 0.8414s | 3.3420s |
| Insertion | 0.0249s | 0.6525s | 2.7604s |
| Shellsort (exp) | 0.0246s | 0.2003s | 0.6082s |
| Shellsort (Ciura) | 0.0210s | 0.1594s | 0.4106s |
| Comb | 0.00873s | 0.06275s | 0.1286s |

Shellsort lands in the middle of the pack — faster than quadratic sorts but slower than Comb and Quicksort for random data. However, its implementation is simpler than quicksort and its worst-case behavior is more predictable.

## Real-World Use Cases

- **Embedded systems**: Simple implementation with no recursion, suitable for memory-constrained environments
- **Educational tools**: Teaching algorithm improvement patterns (taking a basic algorithm and optimizing it)
- **Near-sorted data**: Shellsort performs well on data that's already mostly ordered
- **Resource-constrained APIs**: When you can't rely on PHP's built-in `sort()` for custom ordering needs
- **Middleware sorting**: Processing moderately sized datasets where quicksort overhead is disproportionate

## Best Practices

- **Use Ciura's sequence**: It's the best known gap sequence for practical Shellsort implementations
- **Benchmark with real data**: Theoretical performance matters less than actual performance on your data patterns
- **Consider built-in sort first**: PHP's `sort()` is implemented in C and will always be faster for production use
- **Test edge cases**: Empty arrays, single elements, and already-sorted arrays
- **Use by-reference passing**: Sorting algorithms modify arrays in place for efficiency

## Common Mistakes to Avoid

- **Starting with the wrong gap**: If the first gap is too small, you lose the benefit of long-distance comparison
- **Not skipping large gaps**: If a gap exceeds the array size, skip it rather than using the array size
- **Confusing gap with step**: The gap determines which elements are compared, not how the loop iterates
- **Ignoring gap sequence research**: Don't assume the default exponential sequence is optimal
- **Using Shellsort when built-in sort suffices**: PHP's `sort()` uses a highly optimized quicksort implementation

## Shellsort vs. Other Sorting Algorithms in Detail

Understanding how Shellsort compares to alternatives helps choose the right algorithm for your use case.

### Bubble Sort

Bubble Sort repeatedly steps through the list, comparing adjacent elements and swapping them if they're in the wrong order. Its O(n²) average complexity makes it impractical for anything but tiny arrays. Shellsort's gap-based approach is fundamentally more efficient for arrays larger than a few dozen elements.

### Insertion Sort

Insertion Sort builds the final sorted array one element at a time. It's efficient for small arrays and nearly sorted data but degrades to O(n²) for random data. Shellsort can be thought of as a generalization of Insertion Sort that pre-orders elements using progressively smaller gaps, making the final insertion pass nearly linear.

### Merge Sort

Merge Sort provides consistent O(n log n) performance with stable sorting but requires O(n) additional memory. Shellsort uses O(1) extra space. For memory-constrained environments, Shellsort wins despite slightly worse theoretical bounds.

### Quicksort

Quicksort averages O(n log n) with O(log n) memory (for recursion). It's faster than Shellsort for most random datasets. However, Quicksort can degrade to O(n²) with poor pivot selection. Shellsort's worst case is more predictable, making it suitable for systems where performance consistency matters.

## When Shellsort Beats the Built-In Sort

PHP's `sort()` function is implemented in C using a highly optimized quicksort variant. For almost all practical purposes, use `sort()`. But Shellsort offers advantages in specific scenarios:

1. **Learning and demonstration**: Implementing Shellsort teaches algorithmic thinking that using built-in functions doesn't provide
2. **Custom sorting criteria**: When you need sorting behavior that doesn't match PHP's comparison semantics, implementing from scratch gives full control
3. **Memory-constrained environments**: Shellsort sorts in place with O(1) extra memory
4. **Non-recursive guarantee**: For extremely large arrays, Shellsort avoids any risk of stack overflow from recursion
5. **Embedded or restricted PHP**: In environments where extensions or functions are limited, having a self-contained sort implementation is valuable

## Visualizing the Gap Progression

The magic of Shellsort lies in how gaps shrink. With the exponential sequence on an array of 1000 elements:

- Gap 500: Elements move up to 500 positions per swap
- Gap 250: Elements move up to 250 positions per swap
- Gap 125: Elements move up to 125 positions per swap
- ... down to gap 1

Early passes do the heavy lifting of getting elements near their final positions. Later passes fine-tune. The total work is dramatically less than Insertion Sort's all-at-once approach.

## Frequently Asked Questions

**Why does gap sequence matter so much?**
The gap sequence determines how quickly elements move toward their final positions. Too many gaps add overhead; too few gaps make the final insertion sort work harder.

**Is Shellsort stable?**
No, Shellsort is not stable. Elements with equal keys may change relative order because of long-distance swaps.

**Can Shellsort be used with linked lists?**
Shellsort requires random access for gap-based comparison, making it unsuitable for linked lists.

**What's the best gap sequence?**
Ciura's sequence (1, 4, 10, 23, 57, 132, 301, 701) is experimentally the best for practical array sizes. For very large arrays, extensions of Tokuda's sequence perform well.

**How does Shellsort compare to Mergesort?**
Mergesort is O(n log n) with stable sorting but requires O(n) extra memory. Shellsort is in-place with slightly worse theoretical bounds but better cache performance.

**When should I choose Shellsort over Quicksort?**
When you need simplicity, predictable performance, and in-place sorting without recursion. Shellsort is the safer choice when you can't risk quicksort's worst-case O(n²).

**Can I parallelize Shellsort?**
Yes, Shellsort has natural parallelism because comparisons within the same gap are independent. Each gap-sorted subarray can be sorted concurrently.

## Conclusion

Shellsort demonstrates a beautiful algorithmic insight: by comparing distant elements before adjacent ones, you dramatically improve on Insertion Sort's performance while maintaining its essential simplicity. The choice of gap sequence is the key design decision, with Ciura's sequence offering the best practical performance.

While Shellsort won't beat quicksort for random data on modern hardware, it remains a valuable tool for embedded systems, educational contexts, and situations where you need a simple, in-place, non-recursive sort with consistent O(n log n) performance.

Next time you're working with sorting in PHP, take a moment to appreciate the algorithm behind `sort()`. And if you ever need to implement sorting from scratch, Shellsort is an excellent balance of simplicity and performance.
