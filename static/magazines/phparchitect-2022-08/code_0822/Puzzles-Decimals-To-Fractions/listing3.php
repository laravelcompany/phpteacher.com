<?php

/**
 * @return int[]
 */
function findFactors(int $product) : array {
    $factors = range(1, (int) sqrt($product));
    $flat = [];

    array_walk($factors,
        function($factor) use ($product, &$flat) {
            if ($product % $factor === 0) {
                $flat[] = $factor;
                $flat[] = $product / $factor;
            }
        }
    );

    sort($flat);
    return array_unique($flat);
}