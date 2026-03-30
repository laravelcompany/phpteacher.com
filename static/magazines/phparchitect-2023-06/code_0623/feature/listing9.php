use function PHPUnit\Framework\assertGreaterThanOrEqual

	...

	private const NAME_MIN_LENGTH = 3;

	...

	#[Invariant]
  public function checkName(): void
  {
      assertGreaterThanOrEqual(
					self::NAME_MIN_LENGTH,
					mb_strlen($this->name)
			);
  }