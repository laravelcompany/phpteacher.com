  public function removeDeadEnds(): void
  {
    foreach ($this->cells as $x => $row) {
      foreach ($row as $y => $cell) {
        // is it a dead end
        // 1+2+4 or 1+2+8 or 2+4+8 or 1+4+8
        if (in_array($cell, [7, 11, 13, 14])) {
          $possible = $this->shuffleWalls($x, $y);
          $direction = $possible[0];

          $coords = $this->getNeighborCoords($x, $y,
                        $direction);
          if (!$coords) {
            continue;
          }

          [$newX, $newY] = $coords;

          // get rid of the wall in our current cell
          $this->cells[$x][$y] -= $direction;

          // go to my neighbor in this direction
          // and bust the opposite wall
          $opp = self::BASH[$direction];
          $this->cells[$newX][$newY] -=
                                    $opp['opposite'];
        }
      }
    }
  }

  public function addEntranceExit()
  {
    // make an entrance
    [$entX, $entY] = $this->pickFromSide(self::NORTH);
    $this->cells[$entX][$entY] -= self::NORTH;

    // make an exit but on another side
    [$exitX, $exitY] =$this->pickFromSide(self::SOUTH);
    $this->cells[$exitX][$exitY] -= self::SOUTH;
  }

  private function pickFromSide(int $side): array
  {
    // we avoid picking corners for any case
    if ($side === self::EAST) {
      $row = rand(1, $this->maxRows - 2);
      $col = $this->maxCols - 1;
    }
    if ($side=== self::WEST) {
      $row = rand(1, $this->maxRows - 2);
      $col = 0;
    }
    if ($side === self::NORTH) {
      $row = 0;
      $col = rand(1, $this->maxCols - 2);
    }
    if ($side === self::SOUTH) {
      $row = $this->maxRows - 1;
      $col = rand(1, $this->maxCols - 2);
    }

    return [$row, $col];
  }
}