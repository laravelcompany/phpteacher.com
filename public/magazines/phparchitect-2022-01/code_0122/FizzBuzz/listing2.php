function FizzBuzzResult(int $num) : string {
    if ($num % 15 === 0) {
        return "FizzBuzz";
    }    
    if ($num % 5 === 0) {
        return "Buzz";
    }    
    if ($num % 3 === 0) {
        return "Fizz";
    }
    return $num;
}

$maximum = 50;
for ($i = 1; $i <= 50; $i++) {
    echo FizzBuzzResult($i) . " ";
}