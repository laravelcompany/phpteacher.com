// integer to factor
$product  = 96;

// initialize an array with all possible integer factors
$factors = range(1, (int) sqrt($product));

// keep the ones that divide by zero
$factors = array_map(
    function($factor) use ($product) {
        if ($product % $factor === 0) {
            return [$factor, $product / $factor];
        }
    }, $factors
);

$factors = array_filter($factors);

// now output the pairs
echo "Factors of $product\n";
foreach ($factors as $pair) {
    echo $pair[0] . ' x ' . $pair[1] . "\n";
}