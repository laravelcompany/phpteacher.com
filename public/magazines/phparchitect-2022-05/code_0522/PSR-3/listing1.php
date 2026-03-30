<?php

namespace App;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        // TODO: Implement log() method.
    }

}