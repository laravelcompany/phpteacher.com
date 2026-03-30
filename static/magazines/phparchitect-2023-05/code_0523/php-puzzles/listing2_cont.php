function shuffleWalls($cell)
{
   $possible = [];
   // which of the four walls should we break?
   if ($cell & WEST) {
      $possible[] = WEST;
   }
   if ($cell & EAST) {
      $possible[] = EAST;
   }
   if ($cell & NORTH) {
      $possible[] = NORTH;
   }
   if ($cell & SOUTH) {
      $possible[] = SOUTH;
   }

   if (count($possible)) {
      // pick one of the remaining ones
      shuffle($possible);
   }

   return $possible;
}

function breakWall(
    array &$cells, int $row, int $col, int $direction
): void {
   global $maxCols, $maxRows;

   // figure out my neighbor
   $opp = BASH[$direction];
   $newRow = $row + $opp['yOffset'];
   $newCol = $col + $opp['xOffset'];
   if ($newRow < 0 || $newCol < 0
      || $newCol >= $maxCols || $newRow >= $maxRows) {
      return;
   }

   if ($cells[$newRow][$newCol] !== 15) {
      return;
   }
   // get rid of the indicated wall in our current cell
   $cells[$row][$col] -= $direction;

   // go to my neighbor in this direction
   // and bust opposite wall
   $cells[$newRow][$newCol] -= $opp['opposite'];

   // get my neightbor's possible walls
   $possible = shuffleWalls($cells[$newRow][$newCol]);
   if ($possible) {
      foreach ($possible as $newDir) {
         // haven't broken any walls/visited it yet
         breakWall($cells, $newRow, $newCol, $newDir);
      }
   }
}