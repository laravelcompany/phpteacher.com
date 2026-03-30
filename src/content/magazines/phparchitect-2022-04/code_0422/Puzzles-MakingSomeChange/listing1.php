$amount = 267.51;

echo "Starting amount: {$amount}" . PHP_EOL;
if ($amount >= 100.00) {
    $hundreds = $amount / 100.00;
    echo floor($hundreds) . " $100 bill(s)" . PHP_EOL;
    $amount = $amount - (floor($hundreds) * 100.00);
}

if ($amount >= 50.00) {
    $fifties = $amount / 50.00;
    echo floor($fifties) . " $50 bill(s)" . PHP_EOL;
    $amount = $amount - (floor($fifties) * 50.00);
}

if ($amount >= 20.00) {
    $twenties = $amount / 20.00;
    echo floor($twenties) . " $20 bill(s)" . PHP_EOL;
    $amount = $amount - (floor($twenties) * 20.00);
}

if ($amount >= 10.00) {
    $tens = $amount / 10.00;
    echo floor($tens) . " $10 bill(s)" . PHP_EOL;
    $amount = $amount - (floor($tens) * 10.00);
}

if ($amount >= 5.00) {
    $fives = $amount / 5.0;
    echo floor($fives) . " $5 bill(s)" . PHP_EOL;
    $amount = $amount - (floor($fives) * 5.00);
}

if ($amount >= 1.0) {
    $ones = $amount / 1.0;
    echo floor($ones) . " $1 bill(s)" . PHP_EOL;
    $amount = $amount - (floor($ones) * 1.00);
}

if ($amount >= 0.25) {
    $quarters = $amount / 0.25;
    echo floor($quarters) . " quarter(s)" . PHP_EOL;
    $amount = $amount - (floor($quarters) * 0.25);
}

if ($amount >= 0.10) {
    $nickels = $amount / 0.10;
    echo floor($nickels) . " nickels(s)" . PHP_EOL;
    $amount = $amount - (floor($nickels) * 0.10);
}

if ($amount >= 0.05) {
    $dimes = $amount / 0.05;
    echo floor($dimes) . " dimes(s)" . PHP_EOL;
    $amount = $amount - (floor($dimes) * 0.05);
}

if ($amount >= 0.01) {
    $pennies = $amount / 0.01;
    echo floor($pennies) . " pennies(s)" . PHP_EOL;
    $amount = $amount - (floor($pennies) * 0.01);
}

echo "Remaining amount: " . $amount;