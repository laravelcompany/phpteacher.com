  private function drawCell(
    int $cell, int $x, int $y
  ): void {
    // draw north wall?
    $lineOff = floor($this->lineWidth * 0.5);
    if (($cell & self::NORTH) === self::NORTH) {
      imageline($this->image,
        x1: $x - 2 * $lineOff,
        y1: $y - $lineOff,
        x2: $x + $this->cellSize,
        y2: $y - $lineOff,
        color: $this->color2
      );
    }

    // draw south wall?
    if (($cell & self::SOUTH) === self::SOUTH) {
      imageline($this->image,
        x1: $x,
        y1: $y + $this->cellSize + $lineOff,
        x2: $x + $this->cellSize + $lineOff,
        y2: $y + $this->cellSize + $lineOff,
        color: $this->color1
      );
    }

    // draw west wall?
    if (($cell & self::WEST) === self::WEST) {
      imageline($this->image,
        x1: $x - $lineOff, y1: $y,
        x2: $x - $lineOff, y2: $y + $this->cellSize,
        color: $this->color2
      );
    }

    // draw east wall?
    if (($cell & self::EAST) === self::EAST) {
      imageline($this->image,
        x1: $x + $this->cellSize + $lineOff,
        y1: $y + $lineOff,
        x2: $x + $this->cellSize + $lineOff,
        y2: $y + $this->cellSize + $lineOff,
        color: $this->color1
      );
    }
  }

  public function save(string $file)
  {
    imagepng($this->image, $file);
  }
}