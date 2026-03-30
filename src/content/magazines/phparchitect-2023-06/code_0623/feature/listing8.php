use Sushi\ValueObject;
use Sushi\ValueObject\Exceptions\InvariantException;
use Sushi\ValueObject\Invariant;

class Person extends ValueObject
{
	private const MIN_AGE_IN_YEARS = 18;

    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {
        parent::__construct();
    }

    #[Invariant]
    protected function onlyAdults(): void
    {
        if ($this->age < self::MIN_AGE_IN_YEARS) {
            throw InvariantException::violation(
			    'The age is below ' .
                self::MIN_AGE_IN_YEARS
			);
        }
    }
}