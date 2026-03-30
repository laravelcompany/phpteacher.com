<?php

namespace OMerida\Maze;

class MoveStep
{
    /**
     * @param string[] $instructions
     */
    public function __construct(
        public int $direction,
        public array $instructions,
    ) {}
}