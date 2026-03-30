  private function getNeighborCoords($x, $y, $direction)
  {
    $opp = self::BASH[$direction];
    $newX = $x + $opp['yOffset'];
    $newY = $y + $opp['xOffset'];

    if ($newX < 0 || $newY < 0
      || $newX >= $this->maxRows
      || $newY >= $this->maxCols
    ) {
         return [];
    }

    return [$newX, $newY];
  }

  private function shuffleWalls(int $x, int $y)
  {
    $cell = $this->cells[$x][$y];

    $possible = [];
    // which of the four walls should we break?
    if ($cell & self::WEST) {
      $possible[] = self::WEST;
    }
    if ($cell & self::EAST) {
      $possible[] = self::EAST;
    }
    if ($cell & self::NORTH) {
      $possible[] = self::NORTH;
    }
    if ($cell & self::SOUTH) {
      $possible[] = self::SOUTH;
    }

    if (count($possible)) {
      // pick one of the remaining ones
      shuffle($possible);
    }

    return $possible;
  }