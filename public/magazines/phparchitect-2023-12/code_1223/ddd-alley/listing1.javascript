<?php

declare(strict_types=1);

$counter = 100;
do {
    `/usr/bin/say $counter`;
    $counter -= 7;
} while ($counter >= 0);