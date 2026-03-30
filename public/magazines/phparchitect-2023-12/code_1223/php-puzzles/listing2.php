<?php

/**
 * We're assuming sequential integer keys
 * @param array<int, scalar> $list
 */
function quicksort(
    array &$list,
    int $first = 0,
    int $last = null
): void {
    if ($last === null) {
        $last = count($list) - 1;
    }

    if ($first >= $last) {
        return;
    }

    $pivotIdx = partition($list, $first, $last);
    quicksort($list, $first, $pivotIdx - 1);
    quicksort($list, $pivotIdx + 1, $last);
}

/**
 * @param array<int, scalar> $list
 */
function partition(
    array &$list,
    int $first,
    int $last
): int {
    // select pivot
    $mid = floor(($first + $last) / 2);
    // move the pivot to the start of the array
    swap_elements($list, $first, $mid);

    $index = $first;
    $value = $list[$first];

    $scan = $first + 1;
    // loop through the partition and swap
    // if an element is less than the pivot
    while ($scan <= $last) {
        if ($list[$scan] < $value) {
            $index++;
            swap_elements($list, $index, $scan);
        }
        $scan++;
    }

    swap_elements($list, $first, $index);

    return $index;
}