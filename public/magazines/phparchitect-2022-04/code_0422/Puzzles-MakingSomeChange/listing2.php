$amount = 267.51;
// convert to integer number of pennies
$amount = (int) floor($amount * 100);
// denominations
echo "Starting amount: {$amount}" . PHP_EOL;
if ($amount >= 10000) {
    $hundreds = (int) ($amount / 10000);
    echo (int) $hundreds . " $100 bill(s)" . PHP_EOL;
    $amount = $amount % 1000;
}

if ($amount >= 5000) {
    $fifties = (int) ($amount / 5000);
    echo (int) $fifties . " $50 bill(s)" . PHP_EOL;
    $amount = $amount  % 5000;
}

if ($amount >= 2000) {
    $twenties = (int) $amount / 2000;
    echo floor($twenties) . " $20 bill(s)" . PHP_EOL;
    $amount = $amount % 2000;
}

if ($amount >= 1000) {
    $tens = (int) ($amount / 1000);
    echo (int) $tens . " $10 bill(s)" . PHP_EOL;
    $amount = $amount % 1000;
}

if ($amount >= 500) {
    $fives = (int) ($amount / 500);
    echo $fives . " $5 bill(s)" . PHP_EOL;
    $amount = $amount % 500;
}

if ($amount >= 100) {
    $ones = (int) ($amount / 100);
    echo $ones . " $1 bill(s)" . PHP_EOL;
    $amount = $amount % 100;
}

if ($amount >= 25) {
    $quarters = (int) ($amount / 25);
    echo $quarters . " quarter(s)" . PHP_EOL;
    $amount = $amount % 25;
}

if ($amount >= 10) {
    $nickels = (int) ($amount / 10);
    echo $nickels . " nickels(s)" . PHP_EOL;
    $amount = $amount % 10;
}

if ($amount >= 5) {
    $dimes = (int) ($amount / 5);
    echo $dimes . " dimes(s)" . PHP_EOL;
    $amount = $amount % $dimes;
}

if ($amount >= 1) {
    echo $amount . " pennies(s)" . PHP_EOL;
    $amount = $amount - $amount; // 0
}
echo "Remaining amount: ". $amount;