<?php

use \OMerida\Maze\Renderer;
// now make a big random map
$maxRows = 40; $maxCols = 60;

// initialize them to all closed
$cells = range(0, $maxRows - 1);
$cells = array_map(function() use ($maxCols) {
   $row = range(0, $maxCols - 1);
   return array_map(fn() => 15, $row);
}, $cells);

// start at top left
$row = $col = 0;

// constants for the walls of a cell
const WEST = 0x1;
const EAST = 0x2;
const SOUTH = 0x4;
const NORTH = 0x8;

// break direction helpers
const BASH = [
   WEST =>
['opposite' => EAST, 'xOffset' => -1, 'yOffset' => 0],
   EAST =>
['opposite' => WEST, 'xOffset' => 1, 'yOffset' => 0],
   NORTH =>
['opposite' => SOUTH, 'xOffset' => 0, 'yOffset' => -1],
   SOUTH =>
['opposite' => NORTH, 'xOffset' => 0, 'yOffset' => +1],
];

// get Valid Directions from current cell
$possible = shuffleWalls($cells[$row][$col]);
if ($possible) {
   foreach ($possible as $direction) {
      $opp = BASH[$direction];
      $newX = $col + $opp['xOffset'];
      $newY = $row + $opp['yOffset'];
      if ($newX < 0 || $newY < 0
         || $newX >= $maxRows || $newY >= $maxCols) {
         continue;
      }

      $neighbor = $cells[$newX][$newY];
      if ($neighbor === 15) {
         // haven't broken any walls/visited it yet
         breakWall($cells, $row, $col, $direction);
      }
   }
}

$maze2 = new Renderer($maxRows, $maxCols, 100);
$maze2->setCells($cells);
$maze2->draw();
$maze2->save(__DIR__ . '/maze2.png');