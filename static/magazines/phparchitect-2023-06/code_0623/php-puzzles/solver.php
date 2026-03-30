<?php

namespace OMerida\Maze;

class Solver
{
  // constants for the walls of a cell
  private const WEST = 0x1;
  private const EAST = 0x2;
  private const SOUTH = 0x4;
  private const NORTH = 0x8;
  private const CLOSED = 15;

  // fill direction helpers
  private const FILL = [
    self::WEST => [
      'opposite' => self:: EAST,
      'xOffset' => -1,
      'yOffset' => 0
    ],
    self::EAST => [
      'opposite' => self:: WEST,
      'xOffset' => 1,
      'yOffset' => 0
    ],
    self::NORTH => [
      'opposite' => self:: SOUTH,
      'xOffset' => 0,
      'yOffset' => -1
    ],
    self::SOUTH => [
      'opposite' => self:: NORTH,
      'xOffset' => 0,
      'yOffset' => +1
    ],
  ];

  public function __construct(
    private array $cells
  )
  {
  }

  /**
   * @return \int[][]
   */
  public function getCells(): array
  {
    return $this->cells;
  }

  private function isDeadEnd(int $cell): bool
  {
    return in_array($cell, [0x7, 0xB, 0xD, 0xE]);
  }

  public function findDeadEnd(): bool|array
  {
    // scan the maze for the first dead-end
    // and return it
    foreach ($this->cells as $y => $row) {
      foreach ($row as $x => $cell) {
        if ($this->isDeadEnd($cell)) {
          return [$x, $y];
        }
      }
    }
    return false;
  }

  public function fillDeadEnd(int $x, int $y): void
  {
    // assume the cell given is a dead end,
    // we could be safe and check first.
    $current = $this->cells[$y][$x];
    // fill it
    $this->cells[$y][$x] = self::CLOSED;

    // do any neighboring cell also need to update
    if (0 === ($current & self::SOUTH)) {
      $fill = self::FILL[self::SOUTH];
    } else if (0 === ($current & self::NORTH)) {
      $fill = self::FILL[self::NORTH];
    } else if (0 === ($current & self::EAST)) {
      $fill = self::FILL[self::EAST];
    } else {
      $fill = self::FILL[self::WEST];
    }

    $newX = $x + $fill['xOffset'];
    $newY = $y + $fill['yOffset'];

    // ensure we're still in the maze
    if ($newX >= 0 && $newX <= count($this->cells[0])
      && $newY >= 0 && $newY <= count($this->cells)
    ) {
      $this->cells[$newY][$newX] += $fill['opposite'];

      // if we make a new dead-end,
      // go ahead and fill it
      $cell = $this->cells[$newY][$newX];
      if ($this->isDeadEnd($cell)) {
        $this->fillDeadEnd($newX, $newY);
      }
    }
  }
}