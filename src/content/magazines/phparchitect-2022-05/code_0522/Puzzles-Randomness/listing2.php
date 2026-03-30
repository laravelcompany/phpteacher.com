<?php
// set a seed
mt_srand(672985);

$colors = [
    'red', 'green', 'blue', 'yellow', 'purple', 'black'
];

$sequence = [];

// a functional approach
$sequence = range(0, 5);
$sequence = array_map(function() use ($colors) {
    $pick = rand(0, 6);
    return $colors[$pick];
}, $sequence);

echo join('-', $sequence);