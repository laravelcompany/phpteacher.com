class Product {
    private $id;

    private $title;

    private $brandName;

    private $catalogPrice;

    public function __construct(
	   $id, 
	   $title, 
	   $brandName, 
	   $catalogPrice
	) {
        $this->id = $id;
        $this->title = $title;
        $this->brandName = $brandName;
        $this->catalogPrice = $catalogPrice;
    }
}