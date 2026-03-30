namespace OMerida\Maze;
use Directions as Dir;

class DirectionFinder {
  // first index is the way we're facing,
  // second is which way we want to move
  private $stepMap = [
    Dir::NORTH => [
      Dir::NORTH => [Dir::GO_FORWARD],
      Dir::EAST => [Dir::TURN_RIGHT, Dir::GO_FORWARD],
      Dir::WEST => [Dir::TURN_LEFT, Dir::GO_FORWARD],
    ],
    Dir::SOUTH => [
      Dir::SOUTH => [Dir::GO_FORWARD],
      Dir::EAST => [Dir::TURN_LEFT, Dir::GO_FORWARD],
      Dir::WEST => [Dir::TURN_RIGHT, Dir::GO_FORWARD],
    ],¡¡
    Dir::EAST => [
      Dir::NORTH => [Dir::TURN_LEFT, Dir::GO_FORWARD],
      Dir::SOUTH => [Dir::TURN_RIGHT, Dir::GO_FORWARD],
      Dir::EAST => [Dir::GO_FORWARD],
    ],
    Dir::WEST => [
      Dir::NORTH => [Dir::TURN_RIGHT, Dir::GO_FORWARD],
      Dir::SOUTH => [Dir::TURN_LEFT, Dir::GO_FORWARD],
      Dir::WEST => [Dir::GO_FORWARD],
    ],
  ];

  public function get(
    array $from, array $to, int $facing
  ): MoveStep {
    $direction = $this->getDirection($from, $to);
    return new MoveStep($direction,
      $this->stepMap[$facing][$direction]
    );
  }

  private function getDirection($from, $to): int {
    $rowDelta = $to[0] - $from[0];
    $colDelta = $to[1] - $from[1];
    // since we're moving orthogonally,
    // we don't consider diagonals
    switch (true) {
      case ($rowDelta > 0):
        return Dir::SOUTH;
      case ($rowDelta < 0):
        return Dir::NORTH;
      case ($colDelta < 0):
        return Dir::WEST;
      case ($colDelta > 0):
        return Dir::EAST;
      default:
        return 0;
    }
  }
}