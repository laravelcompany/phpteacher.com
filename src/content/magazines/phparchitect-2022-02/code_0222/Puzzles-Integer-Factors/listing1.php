$product  = 24;

// every integer can be multiplied by 1
echo "1, $product" . PHP_EOL;

for ($i = 2; $i <= $product; $i++) {
    if ($product % $i === 0) {
        $factor = (int) $product / $i;
        echo "$i, $factor" . PHP_EOL;
    }
}