// Solver
$solver = new \OMerida\Maze\Solver($maze->getCells());
$solver->findPath();
$path = $solver->flattenPath();

// the last cell will be next to the exit
$cells = count($path) - 1;
// we enter form the north
$facing = \OMerida\Maze\Directions::SOUTH;

$finder = new \OMerida\Maze\DirectionFinder();
for ($i = 0; $i < $cells; $i++ ) {
  $go = $finder->get($path[$i], $path[$i+1], $facing);
  $facing = $go->direction;
  echo ($i+1) . ': ' . implode(' ', $go->instructions);
  echo PHP_EOL;
}