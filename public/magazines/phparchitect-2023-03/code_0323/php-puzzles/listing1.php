<?php

$file = new SplFileObject(
    'https://gist.githubusercontent.com/'
    . 'omerida/b7a7bbb4c137e189ffc7d448cfe0baaf/raw/'
    . '58914d8e1f1f8ecae6dd653e416235a6f05bb05a/'
    . 'Puzzles-2023-02.tsv'
);

$grades = [];
while (!$file->eof()) {
    $line = $file->fgetcsv("\t");
    // calculate the average for this person's grade
    $courses = array_slice($line, 1, 6);
    // use the first col as the key
    $grades[$line[0]] = array_sum($courses) / 6;
}

// throw away the first one w/column names
array_shift($grades);