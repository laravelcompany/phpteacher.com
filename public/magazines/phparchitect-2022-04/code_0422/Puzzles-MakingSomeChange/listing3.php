$amount = 267.51;

// convert to integer number of pennies
$amount = (int) ($amount * 100);

/**
 * @return array{quotient: int, remainder: int}
 */
function div(int $dividend, int $divisor): array {
    return [
        (int) ($dividend / $divisor),
        $dividend % $divisor
    ];
}

$denominations = [
    // [amount in pennies, string name, singular, plural
    [10000, '$100', 'bill', 'bills'],
    [5000, '$50', 'bill', 'bills'],
    [2000, '$20', 'bill', 'bills'],
    [1000, '$10', 'bill', 'bills'],
    [500, '$5', 'bill', 'bills'],
    [100, '$1', 'bill', 'bills'],
    [25, '', 'quarter', 'quarters'],
    [10, '', 'nickel', 'nickels'],
    [5, '', 'dime', 'dimes'],
    [1, '', 'penny', 'pennies'],
];

// denominations
echo "Starting amount: {$amount}" . PHP_EOL;

foreach ($denominations as $d) {
    if ($amount >= $d[0]) {
        [$num, $amount] = div($amount, $d[0]);
        echo $num . ' ' . $d[1] . ' '
            . ($num > 1 ? $d[3] : $d[2] ) . PHP_EOL;
    }
}

echo "Remaining amount: ". $amount;