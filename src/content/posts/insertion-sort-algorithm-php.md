---
title: "Insertion Sort Algorithm in PHP - Simple and Efficient for Small Arrays"
description: "Master the insertion sort algorithm in PHP. Learn how it builds sorted arrays incrementally, compare performance with bubble and comb sorts, and understand when to use each."
pubDate: "2023-10-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Algorithms", "Sorting", "Insertion Sort", "Bubble Sort", "Comb Sort", "Data Structures", "Performance"]
selected: false
---

Sorting algorithms are more than academic exercises. They teach you about algorithmic thinking, performance analysis, and the trade-offs inherent in software design. The insertion sort is a natural next step after learning bubble sort and comb sort.

Unlike bubble sort, which blindly swaps adjacent elements until the array is sorted, insertion sort builds a sorted array incrementally. It takes one element at a time and inserts it into the correct position among the already-sorted elements. This is exactly how most people sort a hand of playing cards.

## What You'll Learn

- How insertion sort builds a sorted array incrementally
- PHP implementation of insertion sort with detailed walkthrough
- Performance benchmarking against bubble sort and comb sort
- When insertion sort is the right choice despite its O(N^2) complexity
- How insertion sort compares with three sorting algorithms

## How Insertion Sort Works

The algorithm maintains two sections of the array: a sorted section at the beginning and an unsorted section at the end. On each pass, it takes the first element from the unsorted section and inserts it into the correct position in the sorted section.

For an array `[6, 12, 8, 3, 10, 5, 11, 9]`, the state after each pass looks like this:

```
Pass 1: [6, 12, 8, 3, 10, 5, 11, 9]
Pass 2: [6, 8, 12, 3, 10, 5, 11, 9]
Pass 3: [3, 6, 8, 12, 10, 5, 11, 9]
Pass 4: [3, 6, 8, 10, 12, 5, 11, 9]
Pass 5: [3, 5, 6, 8, 10, 12, 11, 9]
Pass 6: [3, 5, 6, 8, 10, 11, 12, 9]
Pass 7: [3, 5, 6, 8, 9, 10, 11, 12]
```

Notice how the front of the array is always sorted. With each pass, the sorted section grows by one element. By the seventh pass, the entire array is sorted.

The key difference from bubble sort is that insertion sort does not swap blindly. It shifts elements to make room for the inserted value, which reduces the number of comparisons for nearly-sorted data.

## PHP Implementation

```php
function swap_elements(
    array &$list, int $i, int $j
): void {
    if (
        array_key_exists($i, $list)
        && array_key_exists($j, $list)
    ) {
        $tmp = $list[$i];
        $list[$i] = $list[$j];
        $list[$j] = $tmp;
        return;
    }

    throw new \InvalidArgumentException(
        "Invalid index specified"
    );
}

function insertion_sort(array &$list): void
{
    $i = 1;

    while ($i < count($list)) {
        $j = $i;

        while (
            $j > 0
            && $list[$j - 1] > $list[$j]
        ) {
            swap_elements($list, $j, $j - 1);
            $j--;
        }

        $i++;
    }
}
```

### Walking Through the Code

The outer `while` loop tracks the boundary between the sorted and unsorted sections. `$i` starts at 1 because a single-element array (index 0) is trivially sorted.

The inner `while` loop does the actual insertion. It starts at position `$i` and walks backward through the sorted section, swapping each element that is larger than the current value. When it finds an element that is smaller or equal, or when it reaches the beginning of the array, it stops. The current value is now in its correct position.

The `swap_elements` function includes safety checks for valid indices. This prevents subtle bugs when the algorithm is modified to work with non-standard array keys.

## Performance Analysis

The worst-case scenario for insertion sort is a reverse-sorted array. Every element must be compared with every element before it, requiring N(N-1)/2 comparisons — O(N^2) complexity.

The best-case scenario is an already-sorted array. The inner loop never executes because `$list[$j - 1] > $list[$j]` is always false. Only N-1 comparisons are needed — O(N) complexity.

Average case is O(N^2). For random data, insertion sort performs about half as many comparisons as bubble sort.

### Benchmarks

Testing with arrays of 1,000, 5,000, and 10,000 random elements:

| Array Size | Fastest | Slowest | Mean |
|---|---|---|---|
| 1,000 | 0.0234 s | 0.0304 s | 0.0249 s |
| 5,000 | 0.6264 s | 0.6799 s | 0.6525 s |
| 10,000 | 2.6110 s | 3.0507 s | 2.7604 s |

### Comparison with Bubble Sort and Comb Sort

| Sort Algorithm | 1,000 | 5,000 | 10,000 |
|---|---|---|---|
| Bubble | 0.0321 s | 0.8414 s | 3.3420 s |
| Insertion | 0.0249 s | 0.6525 s | 2.7604 s |
| Comb (k=1.3) | 0.0087 s | 0.0628 s | 0.1286 s |

Insertion sort is faster than bubble sort but still nearly 20 times slower than comb sort for 10,000 elements. The reason is that insertion sort, like bubble sort, moves elements only one position at a time. Comb sort's gap-based approach makes it dramatically faster for large arrays.

But insertion sort has one advantage: it is stable. Equal elements retain their relative order after sorting. Comb sort and quicksort are not stable by default.

## When Insertion Sort Is the Right Choice

Insertion sort excels in specific scenarios despite its O(N^2) average complexity:

**Small arrays.** For arrays under 50 elements, insertion sort often outperforms quicksort because it has lower overhead. No recursion. No pivot selection. No partitioning.

**Nearly-sorted data.** If the array is mostly in order with a few elements out of place, insertion sort approaches O(N). This is why hybrid sorting algorithms like Timsort use insertion sort for small partitions.

**Online sorting.** When elements arrive one at a time, insertion sort can maintain a sorted array incrementally. Each new element is inserted in O(N) time, whereas other algorithms would re-sort the entire array from scratch.

**Low-memory environments.** Insertion sort sorts in place with O(1) extra memory. No recursion stack, no auxiliary arrays.

## Visualizing Insertion Sort

Understanding insertion sort visually makes the algorithm intuitive. Consider sorting `[6, 12, 8, 3, 10]`:

```
Start:   [6 | 12, 8, 3, 10]
Pass 1:  [6, 12 | 8, 3, 10]   12 > 6, no swap needed
Pass 2:  [6, 8, 12 | 3, 10]   8 < 12, swap; 8 > 6, stop
Pass 3:  [3, 6, 8, 12 | 10]   3 < 12, swap; 3 < 8, swap; 3 < 6, swap; 3 at start, stop
Pass 4:  [3, 6, 8, 10, 12]    10 < 12, swap; 10 > 8, stop
```

The vertical bar separates the sorted section (left) from the unsorted section (right). With each pass, the bar moves one position to the right, and the new element is inserted into its correct position within the sorted section.

## Adaptive Behavior of Insertion Sort

Insertion sort is adaptive — its performance depends on how sorted the input already is. This is its superpower.

For an array that is already sorted, the inner loop never executes because no element is greater than its predecessor. Only N-1 comparisons are needed. The algorithm runs in O(N) time.

```php
// Already sorted: O(N) time
$sorted = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// Reverse sorted: O(N^2) time
$reversed = [10, 9, 8, 7, 6, 5, 4, 3, 2, 1];

// Nearly sorted: ~O(N) time
$nearlySorted = [2, 1, 3, 4, 6, 5, 7, 9, 8, 10];
```

This adaptive behavior is why hybrid sorting algorithms like Timsort (used in Python and Java) use insertion sort for small partitions. When a partition is nearly sorted — which happens naturally during the merge phases of Timsort — insertion sorts through it in near-linear time.

## Real-World Use Cases

**Database Query Results.** Small result sets from database queries (under 100 rows) can be sorted efficiently with insertion sort, especially when the data is nearly ordered by an index.

**Priority Queue Implementation.** When maintaining a small sorted list of priorities, insertion sort provides simple, efficient insertion of new items.

**Hybrid Sorting Libraries.** PHP's built-in sorting functions use hybrid algorithms that fall back to insertion sort for small partitions. Understanding how insertion sort works helps you predict when the built-in sort performs well.

**User Interface Lists.** Sorting a dropdown menu or a small table of user data is often faster with insertion sort than setting up a full quicksort infrastructure.

## Best Practices

**Use insertion sort for arrays under 50 elements.** Above that threshold, comb sort or PHP's built-in `sort()` function becomes more efficient.

**Prefer a stable sort when element order matters.** Insertion sort is stable. Bubble sort is stable (with the right implementation). Comb sort is not.

**Benchmark against your actual data.** Random benchmarks are useful for comparison, but your production data may have patterns that favor one algorithm over another.

**Implement swap_elements safely.** Always check that indices exist before swapping. This prevents silent corruption of the array.

## Common Mistakes to Avoid

**Using insertion sort for large datasets.** O(N^2) complexity means sorting 100,000 elements takes several minutes. Use quicksort or comb sort for large arrays.

**Forgetting that $i starts at 1.** Starting at 0 causes an unnecessary comparison of the first element with itself.

**Not resetting $j correctly.** Each pass must start $j at the current boundary $i. If $j persists from the previous pass, elements will be inserted in the wrong positions.

**Confusing stability with performance.** Stable sorting preserves original order of equal elements. This is a correctness property, not a performance property.

## Frequently Asked Questions

**Is insertion sort faster than bubble sort?**
For most datasets, yes. Insertion sort performs fewer comparisons on average because it only scans the sorted section until it finds the correct position. Bubble sort always scans the entire unsorted section.

**Why not always use PHP's built-in sort()?**
PHP's `sort()` uses a compiled quicksort variant and is faster for general use. Implement custom sorts to understand the performance characteristics of different algorithms, or when PHP's built-in sort is unavailable or inappropriate.

**Can insertion sort be optimized?**
Yes. Using binary search to find the insertion point reduces comparisons from O(N) to O(log N) per pass, though element shifting still requires O(N) time. This variant is called binary insertion sort.

**What is the space complexity?**
O(1). Insertion sort sorts in place using a single temporary variable for swapping.

**Does insertion sort work with strings?**
The implementation shown works with any scalar values. For strings, it sorts lexicographically by default. For custom comparison logic, pass a comparison function as a callback.

## Conclusion

Insertion sort is not the fastest sorting algorithm, but it is the most practical for small and nearly-sorted datasets. Its stability, low overhead, and online processing capability make it a valuable tool in any developer's algorithmic toolkit.

Understanding insertion sort also builds intuition for more advanced algorithms. The concept of maintaining a sorted partition and inserting elements incrementally appears in merge sort, Timsort, and binary search trees.

Next time you encounter a small array that needs sorting, consider implementing insertion sort. The implementation fits in ten lines, the behavior is predictable, and the satisfaction of understanding exactly how your data is being ordered is worth the effort.
