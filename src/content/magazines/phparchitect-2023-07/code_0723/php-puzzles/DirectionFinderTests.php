namespace OMeridaTest;

use PHPUnit\Framework\TestCase;
use OMerida\Maze\DirectionFinder;
use OMerida\Maze\Dir as Dirs;
use OMerida\Maze\MoveStep;

class DirectionFinderTests extends TestCase
{
  /**
   * @dataProvider moveProvider
   */
  public function testMoveWhenFacing(
    array $from, array $to, int $facing,
    int $newFacing, array $newInstructions,
  ): void
  {
    $df = new DirectionFinder();
    $steps = $df->get($from, $to, $facing);

    $this->assertInstanceOf(MoveStep::class, $steps);
    $this->assertEquals($newFacing, $steps->direction);
    $this->assertEquals($newInstructions,
          $steps->instructions);
  }

  public function moveProvider(): array
  {
    return [
      [[1, 0], [0, 0], Dirs::NORTH, Dirs::NORTH,
        [Dirs::GO_FORWARD]],
      [[0, 0], [0, 1], Dirs::NORTH, Dirs::EAST,
        [Dirs::TURN_RIGHT, Dirs::GO_FORWARD]],
      [[0, 1], [0, 0], Dirs::NORTH, Dirs::WEST,
        [Dirs::TURN_LEFT, Dirs::GO_FORWARD]],

      [[0, 1], [0, 0], Dirs::SOUTH, Dirs::WEST,
        [Dirs::TURN_RIGHT, Dirs::GO_FORWARD]],
      [[0, 0], [0, 1], Dirs::SOUTH, Dirs::EAST,
        [Dirs::TURN_LEFT, Dirs::GO_FORWARD]],
      [[0, 0], [1, 0], Dirs::SOUTH, Dirs::SOUTH,
        [Dirs::GO_FORWARD]],

      [[0, 0], [0, 1], Dirs::EAST, Dirs::EAST,
        [Dirs::GO_FORWARD]],
      [[1, 0], [0, 0], Dirs::EAST, Dirs::NORTH,
        [Dirs::TURN_LEFT, Dirs::GO_FORWARD]],
      [[0, 0], [1, 0], Dirs::EAST, Dirs::SOUTH,
        [Dirs::TURN_RIGHT, Dirs::GO_FORWARD]],

      [[0, 1], [0, 0], Dirs::WEST, Dirs::WEST,
        [Dirs::GO_FORWARD]],
      [[1, 0], [0, 0], Dirs::WEST, Dirs::NORTH,
        [Dirs::TURN_RIGHT, Dirs::GO_FORWARD]],
      [[0, 0], [1, 0], Dirs::WEST, Dirs::SOUTH,
        [Dirs::TURN_LEFT, Dirs::GO_FORWARD]],
    ];
  }
}