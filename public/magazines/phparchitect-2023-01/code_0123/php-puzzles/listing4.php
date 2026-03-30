// get numeric day and add them together
$sum = (int) $jose->format('j') +
       (int) $sandra->format('j');

// if the sum is even, both were born on
// either odd or even days
$isEven = (0 === $sum % 2);
if ($isEven) {
    echo 'Both were born on an odd or even day';
} else {
    echo 'One was born on an odd day, ' .
         'the other on an even day.';
}