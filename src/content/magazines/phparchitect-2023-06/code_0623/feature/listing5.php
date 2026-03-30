use Sushi\ValueObject;

class Person extends ValueObject
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {
    }
}