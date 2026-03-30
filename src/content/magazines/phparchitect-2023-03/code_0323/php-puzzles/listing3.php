<?php

// Calculate the standard deviation
$meanGPA = array_sum($gpas) / count($gpas);

// Difference of each from mean
$devs = array_map(function($gpa) use ($meanGPA) {
    return $gpa - $meanGPA;
}, $gpas);
// Square all the differences
$devs = array_map(function($gpa) {
    return $gpa * $gpa;
}, $devs);
// Sum all the difference
$sumDevs = array_sum($devs);
// Divide by number of items
$variance = $sumDevs / count($gpas);

$stdev = sqrt($variance);

echo "Standard deviation is: " . $stdev . PHP_EOL;