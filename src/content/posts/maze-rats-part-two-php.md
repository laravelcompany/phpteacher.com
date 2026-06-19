---
title: "Maze Rats Part Two: PHP Bitwise Maze Solvers"
description: "Continue building maze-solving PHP algorithms. Learn dead-end filling, bitwise cell state management, and pathfinding techniques for grid mazes."
pubDate: "2023-05-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Maze", "Algorithms", "PHP Puzzles", "Bitwise", "Pathfinding", "Dead-End Filling", "Grid"]
---

Maze generation and solving are classic programming puzzles that teach algorithms, data structures, and bitwise manipulation. In Oscar Merida's PHP Puzzles series, Maze Rats Part Two focuses on the dead-end filling algorithm, a surprisingly effective method for solving any maze with a single solution.

The algorithm is simple: find a dead end, fill it in, and repeat until all paths are either part of the solution or eliminated. It works because any path that ends in a dead end cannot be the correct path through the maze.

## What You'll Learn

- Dead-end filling algorithm for maze solving
- Bitwise representation of maze cell walls
- Detecting dead ends using wall patterns
- Recursive and iterative dead-end removal
- Rendering the solved maze with GD library
- Performance considerations for large mazes or recursion-averse environments

## Bitwise Cell Representation

Each cell in the maze stores its wall configuration as a single integer. Four bits represent the four walls.

```php
<?php

declare(strict_types=1);

class MazeSolver
{
    private const int WEST = 0x1;
    private const int EAST = 0x2;
    private const int SOUTH = 0x4;
    private const int NORTH = 0x8;
    private const int CLOSED = 15; // All walls present
}
```

A cell with value 15 (all bits set) has all four walls. Opening a wall clears the corresponding bit.

```
Bit layout:
  WEST  = 0001
  EAST  = 0010
  SOUTH = 0100
  NORTH = 1000
  CLOSED = 1111 (all walls)
```

When a cell has three walls and one opening, it is a dead end. The possible dead-end values are 7 (0111), 11 (1011), 13 (1101), and 14 (1110).

## Detecting Dead Ends

A dead end cell has exactly three walls and one passage. Mathematically, it has one of four specific values.

```php
<?php

declare(strict_types=1);

class MazeSolver
{
    private array $cells;

    public function __construct(array $cells)
    {
        $this->cells = $cells;
    }

    private function isDeadEnd(int $cell): bool
    {
        return in_array($cell, [0x7, 0xB, 0xD, 0xE]);
    }

    public function findDeadEnd(): array|false
    {
        foreach ($this->cells as $y => $row) {
            foreach ($row as $x => $cell) {
                if ($this->isDeadEnd($cell)) {
                    return [$x, $y];
                }
            }
        }
        return false;
    }
}
```

The four dead-end values correspond to which wall is missing:

- `0x7` (0111): Missing WEST wall, passage to the left
- `0xB` (1011): Missing EAST wall, passage to the right
- `0xD` (1101): Missing SOUTH wall, passage downward
- `0xE` (1110): Missing NORTH wall, passage upward

## Filling Dead Ends

When you find a dead end, you fill it (set it to CLOSED) and then traverse to the neighboring cell. If that neighbor becomes a dead end, you fill it too.

```php
<?php

declare(strict_types=1);

class MazeSolver
{
    private const array FILL = [
        self::WEST => ['opposite' => self::EAST, 'xOffset' => -1, 'yOffset' => 0],
        self::EAST => ['opposite' => self::WEST, 'xOffset' => 1, 'yOffset' => 0],
        self::NORTH => ['opposite' => self::SOUTH, 'xOffset' => 0, 'yOffset' => -1],
        self::SOUTH => ['opposite' => self::NORTH, 'xOffset' => 0, 'yOffset' => 1],
    ];

    public function fillDeadEnd(int $x, int $y): void
    {
        $current = $this->cells[$y][$x];
        $this->cells[$y][$x] = self::CLOSED;

        // Determine which direction the passage goes
        if (0 === ($current & self::SOUTH)) {
            $fill = self::FILL[self::SOUTH];
        } elseif (0 === ($current & self::NORTH)) {
            $fill = self::FILL[self::NORTH];
        } elseif (0 === ($current & self::EAST)) {
            $fill = self::FILL[self::EAST];
        } else {
            $fill = self::FILL[self::WEST];
        }

        $newX = $x + $fill['xOffset'];
        $newY = $y + $fill['yOffset'];

        if (!$this->isOutOfBounds($newX, $newY)) {
            $this->cells[$newY][$newX] += $fill['opposite'];

            if ($this->isDeadEnd($this->cells[$newY][$newX])) {
                $this->fillDeadEnd($newX, $newY);
            }
        }
    }

    private function isOutOfBounds(int $x, int $y): bool
    {
        return $x < 0 || $y < 0
            || $y >= count($this->cells)
            || $x >= count($this->cells[0]);
    }
}
```

The recursive approach naturally chains through connected dead ends. When you fill one dead end, the next cell might become a new dead end, and so on.

## Solving the Entire Maze

The full solving algorithm repeatedly finds and fills dead ends until none remain.

```php
<?php

declare(strict_types=1);

class MazeSolver
{
    public function solve(): void
    {
        while (($deadEnd = $this->findDeadEnd()) !== false) {
            [$x, $y] = $deadEnd;
            $this->fillDeadEnd($x, $y);
        }
    }
}
```

The solution path is whatever remains after all dead ends are filled. In a well-formed maze with a single solution, the remaining cells form the solution path from start to finish.

## Generating Mazes for Solving

Before solving, you need a maze to work with. The generation uses the same bitwise representation.

```php
<?php

declare(strict_types=1);

function generateMaze(int $rows, int $cols): array
{
    // Initialize all cells as closed
    $cells = array_fill(0, $rows, array_fill(0, $cols, 15));

    // Use recursive backtracking to carve paths
    carvePaths($cells, 0, 0, $rows, $cols);

    return $cells;
}

function carvePaths(array &$cells, int $row, int $col, int $maxRows, int $maxCols): void
{
    $directions = [
        [0x8, 0x4, -1, 0],  // NORTH, opposite SOUTH
        [0x4, 0x8, 1, 0],   // SOUTH, opposite NORTH
        [0x1, 0x2, 0, -1],  // WEST, opposite EAST
        [0x2, 0x1, 0, 1],   // EAST, opposite WEST
    ];

    shuffle($directions);

    foreach ($directions as [$wall, $opening, $dr, $dc]) {
        $newRow = $row + $dr;
        $newCol = $col + $dc;

        if ($newRow < 0 || $newRow >= $maxRows
            || $newCol < 0 || $newCol >= $maxCols) {
            continue;
        }

        if ($cells[$newRow][$newCol] !== 15) {
            continue;
        }

        // Carve passage: remove wall from current cell
        // and add opening to neighbor
        $cells[$row][$col] &= ~$wall;
        $cells[$newRow][$newCol] &= ~$opening;

        carvePaths($cells, $newRow, $newCol, $maxRows, $maxCols);
    }
}
```

### Understanding the Dead-End Cascade

When you fill a dead end cell, you add a wall to the neighboring cell along the open passage. That neighbor loses one of its open passages. If the neighbor had exactly two open passages before, it now has only one — becoming a dead end itself. This cascading chain reaction is what makes the recursive algorithm so efficient. A single fill operation can trigger a wave of dead-end resolutions that propagates through large sections of the maze without scanning every cell.

## Visualizing Solutions with GD

PHP's GD library renders the solved maze as an image. This is more useful than raw array output.

```php
<?php

declare(strict_types=1);

class MazeRenderer
{
    private \GdImage $image;
    private int $color1;
    private int $color2;
    private int $lineWidth;

    private const int WEST = 0x1;
    private const int EAST = 0x2;
    private const int SOUTH = 0x4;
    private const int NORTH = 0x8;

    public function __construct(
        private int $rows,
        private int $cols,
        private int $cellSize,
    ) {}

    public function render(array $cells, string $outputPath): void
    {
        $padding = (int) ceil($this->cellSize * 0.6);
        $width = $this->cols * $this->cellSize + $padding * 2;
        $height = $this->rows * $this->cellSize + $padding * 2;

        $this->image = imagecreatetruecolor($width, $height);
        $this->lineWidth = (int) ($this->cellSize * 0.1);

        $bg = imagecolorallocate($this->image, 0xFF, 0xFF, 0xFF);
        $this->color1 = imagecolorallocate($this->image, 0x33, 0x33, 0x33);
        $this->color2 = imagecolorallocate($this->image, 0x99, 0x99, 0x99);

        imagefill($this->image, 0, 0, $bg);
        imagesetthickness($this->image, $this->lineWidth);

        $x = $y = $padding;
        foreach ($cells as $row) {
            foreach ($row as $cell) {
                $this->drawCell($cell, $x, $y);
                $x += $this->cellSize;
            }
            $x = $padding;
            $y += $this->cellSize;
        }

        imagepng($this->image, $outputPath);
        imagedestroy($this->image);
    }

    private function drawCell(int $cell, int $x, int $y): void
    {
        if ($cell & self::WEST) {
            imageline($this->image, $x, $y, $x, $y + $this->cellSize, $this->color1);
        }
        if ($cell & self::EAST) {
            imageline($this->image, $x + $this->cellSize, $y, $x + $this->cellSize, $y + $this->cellSize, $this->color1);
        }
        if ($cell & self::NORTH) {
            imageline($this->image, $x, $y, $x + $this->cellSize, $y, $this->color1);
        }
        if ($cell & self::SOUTH) {
            imageline($this->image, $x, $y + $this->cellSize, $x + $this->cellSize, $y + $this->cellSize, $this->color1);
        }
    }
}
```

## Putting It All Together

```php
<?php

declare(strict_types=1);

$rows = 40;
$cols = 60;

// Generate the maze
$cells = generateMaze($rows, $cols);

// Solve it
$solver = new MazeSolver($cells);
$solver->solve();
$solved = $solver->getCells();

// Render
$renderer = new MazeRenderer($rows, $cols, 100);
$renderer->render($solved, __DIR__ . '/solved-maze.png');
```

## Real-World Use Cases

### Game Development

Maze generation and solving algorithms power dungeon generation in RPGs, map creation in strategy games, and procedural content generation.

### Robotics Path Planning

Dead-end filling is analogous to SLAM (Simultaneous Localization and Mapping) algorithms where a robot maps an unknown environment and finds a path.

### Network Routing

The fill algorithm resembles network routing protocols that flood and prune to find optimal paths.

### Puzzle Game Design

Generate puzzles with guaranteed solutions. The solver validates that your generator creates solvable mazes.

## Best Practices

- **Use constants for bit masks** - Magic numbers like `0x7` and `0xB` are confusing. Name them or document their meaning.
- **Validate maze connectivity** - Ensure your generator creates a solvable maze by verifying all cells are reachable.
- **Limit recursion depth** - For very large mazes, consider an iterative approach with an explicit stack.
- **Separate generation from rendering** - Keep maze logic and image output in separate classes.
- **Test with known mazes** - Create small mazes with known solutions to verify your solver works.

## Common Mistakes to Avoid

- **Off-by-one in bounds checking** - Maze boundaries are easy to get wrong. Always test edge cells.
- **Confusing row/col ordering** - PHP arrays are `$array[row][col]`, but GD uses `imagecolorat(image, x, y)`. Keep the convention consistent.
- **Forgetting to initialize cells** - Uninitialized cells have undefined values. Fill them to CLOSED before carving.
- **Infinite recursion** - Without proper visited tracking, recursive carving can loop. Mark cells as visited before recursing.

## Frequently Asked Questions

### Does dead-end filling work for all mazes?

It works for any simply connected maze (no loops and a single solution). For mazes with multiple solutions or loops, dead-end filling removes only the trivial dead ends.

### How does dead-end filling compare to A*?

A* finds the shortest path using heuristics. Dead-end filling removes all non-solution paths, leaving the solution as the only remaining path. Both work, but dead-end filling is simpler.

### Can I solve mazes generated by other algorithms?

Yes. As long as the maze is represented as a 2D array of wall-bit integers, the solver works on mazes from any generator.

### How large a maze can PHP handle?

With the recursive approach, maze size is limited by PHP's recursion depth (default 256). For larger mazes, convert to an iterative algorithm. Memory is the other limit: a 1000x1000 maze uses about 4MB for the cell array.

### Why use bitwise operations instead of arrays?

Bitwise operations are faster and more memory-efficient. A cell's wall state fits in 4 bits instead of 4 booleans. The bitwise AND/OR operations compile to single CPU instructions.

## Conclusion

Maze Rats Part Two demonstrates the elegance of bitwise algorithms in PHP. The dead-end filling solver is conceptually simple, efficient, and broadly applicable beyond maze puzzles.

The same bitwise techniques apply to game development, pathfinding, network routing, and any domain where grid-based spatial reasoning is needed. By mastering cell representation, wall detection, and recursive filling, you gain tools that transfer directly to real-world applications.

Experiment with different maze generators, solver algorithms, and visualization techniques. Each variation teaches you something new about algorithmic thinking in PHP.
