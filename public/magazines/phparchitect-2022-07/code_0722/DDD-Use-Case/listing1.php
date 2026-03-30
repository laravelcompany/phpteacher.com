<?php

declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class CountEventsCommand extends Command
{
    public function buildOptionParser(
        ConsoleOptionParser $parser
    ): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
    }
}