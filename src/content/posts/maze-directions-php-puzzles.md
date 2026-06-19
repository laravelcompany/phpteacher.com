---
title: "Maze Directions: PHP Puzzle Pathfinding Solutions"
description: "Complete Oscar Merida's Maze Directions PHP puzzle with pathfinding direction output, turn-by-turn navigation, and maze solving with PHP 8.x."
pubDate: "2023-07-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Maze", "PHP Puzzles", "Pathfinding", "Directions", "Navigation", "Algorithms", "Grid Solving"]
---

Maze Directions is the final installment in Oscar Merida's maze rat series. The puzzle shifts from generating and solving mazes to something more practical: given a solved maze, output human-readable turn-by-turn directions from the entrance to the exit.

This puzzle combines path tracing with directional logic. You must walk the solution path and determine at each step whether to go straight, turn left, or turn right. The output is a numbered list of instructions that a human could follow.

## What You'll Learn

- Tracing the solution path through a solved maze
- Computing turn directions from path segments
- Handling direction changes at intersections
- Generating human-readable navigation instructions
- Testing direction output against known paths
- Extending the solver for different maze types

## Understanding the Direction Problem

A maze solver finds the path. But the path is a list of coordinates. To make it useful, you convert coordinates to directions.

```php
// Raw path from solver
$path = [
    [0, 0], [0, 1], [0, 2], [1, 2], [2, 2], [2, 3], ...
];

// Desired output
1: Move forward.
2: Turn right. Move forward.
3: Move forward.
4: Turn left. Move forward.
...
```

The key insight is that you need to track the current heading and compute turns based on the difference between consecutive path segments.

## Tracking Direction

First, define the cardinal directions and a helper to compute turns.

```php
<?php

declare(strict_types=1);

enum Direction: string
{
    case North = 'north';
    case South = 'south';
    case East = 'east';
    case West = 'west';
}

enum Turn: string
{
    case Left = 'Turn left';
    case Right = 'Turn right';
    case Straight = 'Move forward';
}

class DirectionManager
{
    public function computeTurn(Direction $current, Direction $next): Turn
    {
        $order = [
            Direction::North->value,
            Direction::East->value,
            Direction::South->value,
            Direction::West->value,
        ];

        $currentIndex = array_search($current->value, $order, true);
        $nextIndex = array_search($next->value, $order, true);

        $diff = $nextIndex - $currentIndex;

        return match ($diff) {
            1, -3 => Turn::Right,
            -1, 3 => Turn::Left,
            0 => Turn::Straight,
            2, -2 => Turn::Right, // U-turn (rare in solved mazes)
            default => throw new \RuntimeException(
                'Invalid direction change'
            ),
        };
    }
}
```

## Converting Path to Directions

Given the solved path as coordinate pairs, walk the path and generate direction steps.

```php
<?php

declare(strict_types=1);

class PathToDirections
{
    private DirectionManager $directionManager;

    public function __construct()
    {
        $this->directionManager = new DirectionManager();
    }

    public function convert(array $path): array
    {
        if (count($path) < 2) {
            return [];
        }

        $directions = [];
        $segments = $this->pathToSegments($path);

        for ($i = 0; $i < count($segments); $i++) {
            $currentHeading = $segments[$i]['heading'];

            if ($i === 0) {
                // First step: just move forward
                $directions[] = [
                    'step' => $i + 1,
                    'instruction' => Turn::Straight->value,
                ];
            } else {
                $previousHeading = $segments[$i - 1]['heading'];
                $turn = $this->directionManager->computeTurn(
                    $previousHeading,
                    $currentHeading,
                );

                $directions[] = [
                    'step' => $i + 1,
                    'instruction' => $turn->value . '. Move forward.',
                ];
            }
        }

        return $directions;
    }

    private function pathToSegments(array $path): array
    {
        $segments = [];

        for ($i = 0; $i < count($path) - 1; $i++) {
            [$x1, $y1] = $path[$i];
            [$x2, $y2] = $path[$i + 1];

            $dx = $x2 - $x1;
            $dy = $y2 - $y1;

            $heading = match ([$dx, $dy]) {
                [0, -1] => Direction::North,
                [0, 1] => Direction::South,
                [-1, 0] => Direction::West,
                [1, 0] => Direction::East,
                default => throw new \RuntimeException(
                    "Invalid move: ({$dx}, {$dy})"
                ),
            };

            $segments[] = [
                'from' => [$x1, $y1],
                'to' => [$x2, $y2],
                'heading' => $heading,
            ];
        }

        return $segments;
    }
}
```

## Complete Direction Solver

Combine the maze solver with the direction converter.

```php
<?php

declare(strict_types=1);

class MazeDirectionSolver
{
    public function __construct(
        private Solver $solver,
        private PathTracer $tracer,
        private PathToDirections $converter,
    ) {}

    public function solve(
        array $cells,
        int $startX,
        int $startY,
        int $endX,
        int $endY,
    ): array {
        // Solve the maze (fill dead ends)
        $this->solver->setCells($cells);
        $this->solver->solve();
        $solvedCells = $this->solver->getCells();

        // Trace the solution path
        $path = $this->tracer->trace(
            $solvedCells,
            $startX,
            $startY,
            $endX,
            $endY,
        );

        // Convert path to directions
        return $this->converter->convert($path);
    }
}
```

## Path Tracing After Dead-End Filling

The tracer walks through the solved maze following open passages.

```php
<?php

declare(strict_types=1);

class PathTracer
{
    private const int WEST = 0x1;
    private const int EAST = 0x2;
    private const int SOUTH = 0x4;
    private const int NORTH = 0x8;

    public function trace(
        array $cells,
        int $startX,
        int $startY,
        int $endX,
        int $endY,
    ): array {
        $path = [];
        $visited = [];
        $currentX = $startX;
        $currentY = $startY;

        while ($currentX !== $endX || $currentY !== $endY) {
            $path[] = [$currentX, $currentY];
            $visited[$currentY][$currentX] = true;

            $cell = $cells[$currentY][$currentX];
            $next = null;

            // Check each direction for an open passage (no wall)
            if (!($cell & self::NORTH)
                && !($visited[$currentY - 1][$currentX] ?? false)
            ) {
                $next = [$currentX, $currentY - 1];
            } elseif (!($cell & self::SOUTH)
                && !($visited[$currentY + 1][$currentX] ?? false)
            ) {
                $next = [$currentX, $currentY + 1];
            } elseif (!($cell & self::WEST)
                && !($visited[$currentY][$currentX - 1] ?? false)
            ) {
                $next = [$currentX - 1, $currentY];
            } elseif (!($cell & self::EAST)
                && !($visited[$currentY][$currentX + 1] ?? false)
            ) {
                $next = [$currentX + 1, $currentY];
            }

            if ($next === null) {
                throw new \RuntimeException(
                    "Stuck at ({$currentX}, {$currentY})"
                );
            }

            [$currentX, $currentY] = $next;
        }

        $path[] = [$endX, $endY];
        return $path;
    }
}
```

### Merging Consecutive Same-Direction Steps

For cleaner output, merge consecutive "Move forward" instructions into a single step with a distance count:

```php
<?php

declare(strict_types=1);

class DirectionFormatter
{
    public function mergeConsecutive(array $directions): array
    {
        $merged = [];
        $straightCount = 0;

        foreach ($directions as $entry) {
            if ($entry['instruction'] === 'Move forward') {
                $straightCount++;
            } else {
                if ($straightCount > 0) {
                    $merged[] = [
                        'step' => count($merged) + 1,
                        'instruction' => "Move forward {$straightCount} steps.",
                    ];
                    $straightCount = 0;
                }
                $merged[] = $entry;
            }
        }

        if ($straightCount > 0) {
            $merged[] = [
                'step' => count($merged) + 1,
                'instruction' => "Move forward {$straightCount} steps.",
            ];
        }

        return $merged;
    }
}
```

This merges "Move forward" repeated entries into concise "Move forward 5 steps" for a cleaner user experience.

## Sample Output

For a simple 5x5 maze, the directions might look like:

```
1: Move forward.
2: Turn right. Move forward.
3: Move forward.
4: Move forward.
5: Turn left. Move forward.
6: Turn right. Move forward.
7: Move forward.
8: Move forward.
9: Move forward.
10: Move forward.
11: Turn left. Move forward.
12: Turn right. Move forward.
13: Move forward.
14: Move forward.
15: Move forward.
16: Move forward.
17: Move forward.
18: Move forward.
19: Turn left. Move forward.
20: Turn right. Move forward.
21: Move forward.
22: Turn right. Move forward.
23: Turn left. Move forward.
24: Move forward.
25: Turn left. Move forward.
26: Turn left. Move forward.
27: Turn right. Move forward.
28: Turn right. Move forward.
29: Move forward.
30: Move forward.
31: Move forward.
32: Turn right. Move forward.
33: Turn left. Move forward.
```

## Handling Edge Cases

### U-Turns

In a properly formed simply-connected maze, U-turns should not occur. If the solver left dead ends unfilled, U-turns indicate a bug.

```php
<?php

private function handleUturn(Turn $turn): Turn
{
    // In a solved maze, U-turns should not happen
    // Log for debugging
    if ($turn === Turn::UTurn) {
        error_log('Warning: U-turn detected in solved path');
    }
    return $turn;
}
```

### Short Paths

A path with 0 or 1 segments is trivially solved.

```php
<?php

public function convert(array $path): array
{
    if (count($path) <= 1) {
        return [['step' => 1, 'instruction' => 'Already at destination.']];
    }

    // ... normal conversion
}
```

## Real-World Use Cases

### Robot Navigation

Convert grid paths to motor commands. Left and right turns correspond to wheel differentials.

### Indoor Navigation Apps

Generate turn-by-turn directions for navigating buildings. The maze solver finds the path; the direction converter makes it human-readable.

### Game AI

NPC pathfinding with step-by-step movement commands. The direction output drives animation state machines.

### Accessibility Tools

Generate audio navigation instructions for visually impaired users navigating physical spaces.

## Best Practices

- **Validate path continuity** - Ensure every consecutive coordinate pair is adjacent (no diagonal moves).
- **Handle start equals end** - If the entrance and exit are the same cell, output "Already at destination."
- **Log unexpected turns** - U-turns or diagonal moves indicate bugs in the solver or path tracer.
- **Test with known mazes** - Create tiny mazes (2x2, 3x3) with known correct directions to validate your code.
- **Separate concerns** - Keep the solver, tracer, and direction converter as independent classes.

## Common Mistakes to Avoid

- **Confusing x and y coordinates** - Remember: `$path[$i]` gives `[$x, $y]`. Map x to columns and y to rows.
- **Incorrect heading calculation** - A move from (0,0) to (0,1) is South, not East. Double-check your delta-to-direction mapping.
- **Starting direction wrong** - The first step has no previous heading. Always output "Move forward" for step 1.
- **Missing turns at intersections** - Every change in heading between consecutive segments requires a Turn entry.
- **Forgotten visited tracking** - Without visited tracking, the tracer loops between two adjacent cells.

## Frequently Asked Questions

### How do I know if the path requires a left or right turn?

Compare the previous heading to the current heading. If you were going North and now go East, that is a right turn. North to West is a left turn.

### Can this algorithm handle diagonal movement?

The current implementation assumes Manhattan movement (only cardinal directions). For diagonal movement, extend the heading calculation to include NE, NW, SE, SW.

### What if the maze has multiple solutions?

The dead-end filling solver finds the unique solution in a simply-connected maze. For mazes with multiple solutions, use a shortest-path algorithm like A* first.

### How can I visualize the directions on the maze image?

Overlay arrows on the maze image. At each turn point, draw an arrow showing the direction change.

### Does this work for 3D mazes?

The direction concept extends to 3D by adding up/down headings. The turn calculation becomes more complex with additional axes.

### How do I handle mazes with loops?

Loops create ambiguity in the path tracer. Use a visited set and avoid revisiting cells. The direction output will reflect the specific path taken.

### What is the minimum maze size for meaningful directions?

A 2x2 maze produces trivial output. Minimum 3x3 mazes generate interesting direction sequences.

## Conclusion

Maze Directions completes the maze rat trilogy by solving a practical problem: converting raw path coordinates into human-readable navigation instructions. The same algorithm that powers your solver can generate turn-by-turn directions for robots, game characters, or indoor navigation apps.

The techniques you have learned throughout this series bitwise cell encoding, dead-end solving, path tracing, and direction conversion are directly applicable to pathfinding problems in game development, robotics, and spatial computing.

Combine these with a maze generator to create endless puzzles, each with its own unique set of turn-by-turn directions to discover.
