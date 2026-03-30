// integer to factor
$product  = 96;

// initialize an array with all possible integer factorss
$factors = range(1, (int) sqrt($product));

// keep the ones that divide by zero
$factors = array_filter($factors,
    function($factor) use ($product) {
        return ($product % $factor === 0);
    }
);

// now output the pairs
echo "Factors of $product\n";
foreach ($factors as $factor) {
    echo $factor . ' x ' . ($product / $factor) . "\n";
}

die();

// every integer can be multiplied by 1
echo "1, $product" . PHP_EOL;

for ($i = 2; $i <= $product; $i++) {
    if ($product % $i === 0) {
        $factor = (int) $product / $i;
        echo "$i, $factor" . PHP_EOL;
    }
}