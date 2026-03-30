class ProductMapper {
    public function create(
        $title,
        $brandName,
        $catalogPrice
    ): Product { }

    public function update(Product $product) { }

    public function delete(Product $product) { }

    public function findById($id): Product { }

    public function findByTitle(
        $title
    ): ProductCollection { }

    public function findExpensiveBrandedProducts(
        $costLimit
    ): ProductCollection { }

    public function updateOldCollectionsByPercent(
        int $percent
    ) { }
}