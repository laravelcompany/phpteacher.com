<?php

function findFactors(int $product) : array {
    $factors = range(1, (int) sqrt($product));
    $factors = array_map(
        function($factor) use ($product) {
            if ($product % $factor === 0) {
                return [$factor, $product / $factor];
            }
        }, $factors
    );

    return array_filter($factors);
}