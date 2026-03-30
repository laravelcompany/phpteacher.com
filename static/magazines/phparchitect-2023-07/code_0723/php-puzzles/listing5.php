public function flattenPath(): array
{
  $cells = $this->getCells();

  // get rid of closed cells
  $cells = array_map(function ($row) {
    return array_filter($row,
            fn($cell) => $cell !== self::CLOSED);
  }, $cells);

  // find our entrance cell
  $entrance = array_filter($cells[0],
    function (int $cell) {
      return ($cell & self::NORTH) === 0;
  });

  $path = [];
  foreach ($entrance as $col => $value) {
    $path[] = [0, $col];
  }
  $current = $path[0];

  // now, we visit each every cell
  while ( ! empty($cells)) {
    // where can we go from this cell?
    [$currentRow, $currentCol] = $current;

    $walls = $cells[$currentRow][$currentCol];
    // build a list of potential destinations, in most
    // cases this will be two and we should then
    // ignore the cell we we're coming from
    $maybe = [];
    if (($walls & self::NORTH) === 0 &&
        ($currentRow - 1 >= 0)) {
      // could go north
      $maybe[] = [$currentRow - 1, $currentCol];
    }
    if (($walls & self::SOUTH) === 0) {
      $maybe[] = [$currentRow + 1, $currentCol];
    }
    if (($walls & self::EAST) === 0) {
      $maybe[] = [$currentRow, $currentCol + 1];
    }
    if (($walls & self::WEST) === 0 &&
        ($currentCol - 1 >= 0)) {
      $maybe[] = [$currentRow, $currentCol - 1];
    }

    // don't include cells we've already visited
    $maybe = array_filter($maybe,
      function ($c) use ($path) {
        // keep if we don't find it
        return ! in_array($c, $path, true);
    });

    // we should ony have one cell to go to next
    $next = array_shift($maybe);

    // add it to our path
    $path[] = $next;
    // remove from map so this loop ends eventually
    unset($cells[$currentRow][$currentCol]);
    // remove empty rows to end the looping
    $cells = array_filter($cells);
    // now move to the next cell and repeat
    $current = $next;
  }

  return $path;
}