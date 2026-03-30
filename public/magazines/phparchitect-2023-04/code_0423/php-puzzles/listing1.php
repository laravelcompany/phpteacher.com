<?php

$maze = [
    [0x9, 0xC, 0xA, 0xD, 0xA],
    [0x7, 0x9, 0x0, 0xA, 0x3],
    [0xB, 0x7, 0x7, 0x1, 0x2],
    [0x5, 0xC, 0xA, 0x3, 0x7],
    [0xD, 0xC, 0x4, 0x6, 0xF]
];

// draw the top line
const EAST = 0x2;
const SOUTH = 0x4;

$repeat = count($maze[1]);
echo '┰' . str_repeat('───┰', $repeat) . PHP_EOL;
foreach ($maze as $row) {
  $east = '┃';
  $south = '╋';
  foreach ($row as $cell) {
    // draw the east walls
    $east .= (($cell & EAST) == EAST ? '   ┃' : '    ');
    // draw the southern walls
    $south .= (($cell & SOUTH) == SOUTH ? '───' : '   ')
           . (($cell & EAST) == EAST ? '╋' : '┰');
  }
  echo $east . PHP_EOL . $south . PHP_EOL;
}