class Gender
{
	public const Female = 'female';
	public const Male = 'male';

	public function __construct(
		public string $value,
	) {
	}
}

class Person extends ValueObject
{
    public function __construct(
        public readonly string $givenName,
        public readonly string $familyName,
				public readonly Gender $gender,
    ) {
    }
}