<?php
// set a seed
mt_srand(672985);

$colors = [
    'red', 'green', 'blue', 'yellow', 'purple', 'black'
];

$sequence = [];

for ($i = 0; $i < 6; $i++) {
    $pick = rand(0, 6);
    $sequence[] = $colors[$pick];
}

echo join('-', $sequence);