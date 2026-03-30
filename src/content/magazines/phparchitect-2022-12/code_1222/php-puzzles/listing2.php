<?php

function buildArray(string $input): array
{
  preg_match_all('/([A-Z]+)\s([0-9 ]+)/', $input, $match);
  $result = [];
  foreach ($match[0] as $i => $m) {
    $country = $match[1][$i];
    $numbers = explode(' ', trim($match[2][$i]));
    foreach ($numbers as $number) {
      $result[] = $country . ' ' . $number;
    }
  }
  sort($result, SORT_NATURAL);
  return $result;
}