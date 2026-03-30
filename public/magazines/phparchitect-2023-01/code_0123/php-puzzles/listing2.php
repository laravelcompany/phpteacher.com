echo "Jose was born on ".$jose->format('l').PHP_EOL;
echo "Sandra was born on ".$sandra->format('l').PHP_EOL;

if ($jose->format('D') === $sandra->format('D')) {
    echo "They were born on the same day!" . PHP_EOL;
} else {
    echo "They were not born on the same day.".PHP_EOL;
}