<?php

namespace OMeridaTest;

use PHPUnit\Framework\TestCase;
use OMerida\Maze\DirectionFinder;
use OMerida\Maze\Dir as Dirs;

class DirectionFinderTests extends TestCase
{
    public function testMoveWestWhenFacingSouth()
    {
        $df = new DirectionFinder();
        $steps = $df->get(
            from: [0, 5],
            to:   [0, 4],
            facing: Dirs::SOUTH
        );

        $this->assertEquals(
          $steps, [Dirs::TURN_RIGHT, Dirs::GO_FORWARD]
        );
    }
}