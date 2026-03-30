<?php

function getGPA(float $grade): float
{
    switch (true) {
        case ($grade >= 97): return 4.3;
        case ($grade >= 93): return 4.0;
        case ($grade >= 90): return 3.7;
        case ($grade >= 87): return 3.3;
        case ($grade >= 83): return 3.0;
        case ($grade >= 80): return 2.7;
        case ($grade >= 77): return 2.3;
        case ($grade >= 73): return 2.0;
        case ($grade >= 70): return 1.7;
        case ($grade >= 67): return 1.3;
        case ($grade >= 65): return 1.0;
        default: return 0.0;
    }
}

$gpas = array_map('getGPA', $grades);