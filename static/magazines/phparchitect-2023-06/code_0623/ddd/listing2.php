<?php

declare(strict_types=1);

/* namespace according to need */

use function implode;
use function microtime;
use function sprintf;

class Timing
{
    /** @var float */
    private $start;
    /** @var float */
    private $intervalStart;
    /** @var array */
    private $timings = [];

    public function __construct(?float $start = null)
    {
        if (null === $start) {
            $start = microtime(true) * 1000.0;
        }
        $this->start = $start;
        $this->intervalStart = $start;
    }

    public function measure(string $description): void
    {
        $current = microtime(true) * 1000.0;
        $total = $current - $this->start;
        $interval = $current - $this->intervalStart;
        $this->timings[] = "$description: " .
        sprintf(
            '%.3f ms, %.3f ms total',
            $interval, $total
        );
        $this->intervalStart = $current;
    }

    public function show(
        string $separator = '<br>'
    ): string {
        return implode($separator, $this->timings);
    }
}