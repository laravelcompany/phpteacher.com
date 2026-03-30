class Product {

	public function __construct(
		private ?string $name = null,
		private ?int $price = null
	): void {}

	public function setName(string $name): void {
    $this->name = $name;
	}

	public function setPrice(int $price): void {
		$this->price = $price;
	}

  public function getName(): ?string {
		return $this->name;
	}

	public function getPrice(): ?int {
		return $this->price;
	}

	public function getDisplayInfo(): string {
		return "Product: {$this->getName()}, " .
		       "Price: {$this->getPrice()}";
	}
}