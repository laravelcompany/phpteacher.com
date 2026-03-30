
namespace OMerida\Maze;

class RecursiveGenerator
{
  // constants for the walls of a cell
  private const WEST = 0x1;
  private const EAST = 0x2;
  private const SOUTH = 0x4;
  private const NORTH = 0x8;
  private const CLOSED = 15;

  // break direction helpers
  private const BASH = [
    self::WEST  => [
      'opposite' => self:: EAST,
      'xOffset' => -1,
      'yOffset' => 0
    ],
    self::EAST  => [
      'opposite' => self:: WEST,
      'xOffset' => 1,
      'yOffset' => 0
    ],
    self::NORTH => [
      'opposite' => self:: SOUTH,
      'xOffset' => 0,
      'yOffset' => -1
    ],
    self::SOUTH => [
      'opposite' => self:: NORTH,
      'xOffset' => 0,
      'yOffset' => +1
    ],
  ];

  private array $cells;

  public function __construct(
    private int $maxRows,
    private int $maxCols
  ) {
    // initialize them to all closed
    $this->cells = range(0, $maxRows - 1);
    $this->cells = array_map(
      function() use ($maxCols) {
        $row = range(0, $maxCols - 1);
        return array_map(fn() => self::CLOSED, $row);
    }, $this->cells);
  }