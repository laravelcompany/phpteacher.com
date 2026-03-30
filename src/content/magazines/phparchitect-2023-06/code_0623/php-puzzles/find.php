public function findDeadEnd(): bool|array
{
    // scan the maze for the first dead-end, return it
    foreach ($this->cells as $y => $row) {
        foreach ($row as $x => $cell) {
            if (in_array($cell, [0x7, 0xB,0xD, 0xE])) {
                return [$x, $y];
            }
        }
    }
    return false;
}
