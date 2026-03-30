<?php

namespace OMerida\Maze;

class Directions
{
    // constants for the walls of a cell
    public const WEST = 0x1;
    public const EAST = 0x2;
    public const SOUTH = 0x4;
    public const NORTH = 0x8;

    public const TURN_LEFT = 'Turn left.';
    public const TURN_RIGHT = 'Turn right.';
    public const GO_FORWARD = 'Move forward.';
}