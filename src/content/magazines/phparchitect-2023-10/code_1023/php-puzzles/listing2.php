<?php

function swap_elements(
    array &$list, int $i, int $j
): void {
    // safety check
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

/**
 * We're assuming sequential integer keys
 * @param array<int, scalar> $list
 */
function insertion_sort(array &$list): void
{
    $i = 1;
    while ($i < count($list)) {
        $j = $i;
        while ($j > 0 and $list[$j - 1] > $list[$j]) {
            swap_elements($list, $j, $j - 1);
            --$j;
        }
        ++$i;
    }
}