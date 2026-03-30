<?php

function swap_elems(array &$list, int $i, int $j): void
{
    // safety check
    if (array_key_exists($i, $list) &&
        array_key_exists($j, $list)
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
function shell_sort(array &$list): void
{
    $gap = floor(count($list) / 2);

    while ($gap > 0) {
        $i = $gap;
        while ($i < count($list)) {
            for (
              $j = $i;
              ($j>=$gap && $list[$j - $gap]>$list[$j]);
              $j -= $gap
            ) {
                swap_elems($list, $j, $j - $gap);
            }
            ++$i;
        }
        $gap = floor($gap / 2);
    }
}