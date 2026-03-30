  /**
   * @return array|\int[][]
   */
  public function getCells(): array
  {
    return $this->cells;
  }

  public function generate(): void
  {
    $startX = rand(0, $this->maxRows - 1);
    $startY = rand(0, $this->maxCols - 1);

    $this->visitCell($startX, $startY);
  }

  private function visitCell(int $x, int $y)
  {
    if ($possible = $this->shuffleWalls($x, $y)) {
      foreach ($possible as $direction) {
        $coords = $this->getNeighborCoords($x, $y,
                        $direction);
        if (!$coords) {
          continue;
        }

        [$newX, $newY] = $coords;
        $neighbor = $this->cells[$newX][$newY];

        if ($neighbor !== self::CLOSED) {
          continue;
        }

        // get rid of the wall in our current cell
        $this->cells[$x][$y] -= $direction;

        // go to my neighbor in this direction and
        // bust the opposite wall
        $opp = self::BASH[$direction];
        $this->cells[$newX][$newY] -= $opp['opposite'];

        // visit neighbor to continue maze generation
        $this->visitCell($newX, $newY);
      }
    }
  }
