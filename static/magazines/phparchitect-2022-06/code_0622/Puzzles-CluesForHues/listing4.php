<?php

function getAnswer(int $seed) {
    mt_srand($seed);

    $colors = ['R', 'G', 'B', 'Y', 'P', 'K'];
    $max = count($colors) - 1;
    $sequence = [];

    for ($i = 0; $i < 6; $i++) {
        $sequence[] = $colors[rand(0, $max)];
    }

    return $sequence;
}

function getFeedback(array $guess, array $answer) : array {
    // count chars
    $raw = count_chars(implode('', $answer), 1);
    $freq = [];
    foreach ($raw as $chr => $count) {
        $freq[chr($chr)] = $count;
    }

    // assume it's wrong
    $feedback = array_fill(0, 6, '-');

    // we're checking if the letter is in the right spot
    foreach ($feedback as $index => $state) {
        if ($guess[$index] === $answer[$index]) {
            // decrement frequency for second pass
            $feedback[$index] = '+';
            $freq[$guess[$index]]--;
        }
    }

    // now check again and indicate "in the solution"
    foreach ($feedback as $index => $state) {
        // only look at letters we haven't examined
        if ($state !== '-') continue;
        $letter = $guess[$index];

        if (in_array($letter, $answer) && $freq[$letter] > 0) {
            // only mark as in solution if we have uses left
            $feedback[$index] = '?';
            $freq[$letter]--;
        }
    }
    return $feedback;
}

function getGuess() : array
{
    $input = readline("Enter your guess: ");

    // minor cleanup, users can enter any character but
    // that just wastes guesses
    $input = substr(strtoupper($input), 0, 6);
    return str_split($input);
}

function outputGuessFeedback(int $num, array $g) {
    echo $num . ": ";

    foreach ($g['guess'] as $idx => $letter) {
        switch ($g['feedback'][$idx]) {
            case '+'; // correct, mark green
                echo "\033[;92m" . $letter;
                break;
            case '?'; // in the solution, mark yellow
                echo "\033[;93m" . $letter;
                break;

            default: // not in solution, red
                echo "\033[;91m" . $letter;
                break;
        }
    }
    // reset color
    echo "\033[0m\n";
}

$now = new \DateTime("now", new DateTimeZone("UTC"));
$answer = getAnswer($now->format('YmdH'));
$maxGuesses = 6;
$guesses = [];
$solved = false;

do {
    $guessesLeft = $maxGuesses - count($guesses);
    echo "Guess the correct six-color sequence. \n";
    echo "R=Red, G=Green, B=Blue, Y=Yellow, P=Purple, K=Black\n";
    echo "\n";
    echo "You have $guessesLeft guesses left.\n";
    $guess = getGuess();
    $guesses[] = ['guess' => $guess, 'feedback' => getFeedback($guess, $answer)];
    foreach ($guesses as $i => $oldGuess) {
        outputGuessFeedback($i, $oldGuess);
    }
    // did they win?
    if ($guess === $answer) {
        $solved = true;
    }
} while (!$solved && count($guesses) <= $maxGuesses);

if ($solved) {
    echo "CORRECT! You found the solution!\n";
} else {
    echo "You ran out of guesses.";
}