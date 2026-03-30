class Color
{
    private int $id;

    public function __construct(
        public readonly int $red,
        public readonly int $green,
        public readonly int $blue,
        public readonly float $alpha,
    ) {
    }
}