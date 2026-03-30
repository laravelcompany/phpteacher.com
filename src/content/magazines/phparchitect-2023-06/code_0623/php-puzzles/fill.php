public function fillDeadEnd(int $x, int $y): void
{
    // assume the cell given is a dead end, we could be
    // safe and check first.
    $current = $this->cells[$y][$x];
    // fill it
    $this->cells[$y][$x] = self::CLOSED;

    // find any neighboring cell also need to update
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
        // recall we can add the value of the wall we
        // want to set to the current cell state.
        $this->cells[$newY][$newX]+=$fill['opposite'];
    }
}
