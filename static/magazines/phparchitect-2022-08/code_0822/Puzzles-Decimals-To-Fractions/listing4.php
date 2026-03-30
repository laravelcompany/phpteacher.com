<?php

namespace Puzzles;

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

    public function __toString() : string {
        return $this->numerator . '/' . $this->denominator;
    }

    public function simplify() : self
    {
        $numeratorFactors = findFactors($this->numerator);
        $denominatorFactors = findFactors($this->denominator);

        $commonFactors = array_intersect($numeratorFactors, $denominatorFactors);
        $GCF = array_pop($commonFactors);

        if ($GCF > 1) {
            return new self(
                $this->numerator / $GCF,
                $this->denominator / $GCF,
            );
        }

        return $this;
    }

    /**
     * @return int[]
     */
    private function findFactors(int $product) : array {
        $factors = range(1, (int) sqrt($product));
        $flat = [];

        array_walk($factors,
            function($factor) use ($product, &$flat) {
                if ($product % $factor === 0) {
                    $flat[] = $factor;
                    $flat[] = $product / $factor;
                }
            }
        );

        sort($flat);
        return array_unique($flat);
    }
}