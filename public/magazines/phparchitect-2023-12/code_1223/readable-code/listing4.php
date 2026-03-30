class Product {
	private $name;
	private $price;

	public function __construct($name, $price) {
		$this->name = $name;
		$this->price = $price;
	}

	public function displayInfo() {
		return "Product: {$this->name}, " .
		       "Price: {$this->price}";
	}
}