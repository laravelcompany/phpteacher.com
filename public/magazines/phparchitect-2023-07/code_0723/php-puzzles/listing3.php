<?php

namespace OMerida\Maze;

class DirectionFinder
{
    public function get($from, $to, $facing)
    {
        return [Dir::TURN_RIGHT, Dir::GO_FORWARD];
    }
}