<?php

class Fraction
{
    public function __construct(
        public int $numerator,
        public int $denominator,
    ) {}

    public static function fromFloat(float $input) : self
    {
        $parts = explode('.', $input);
        $count = strlen($parts[1]);

        return new self(
            numerator: (int) $parts[1],
            denominator: pow(10, $count)
        );
    }
}