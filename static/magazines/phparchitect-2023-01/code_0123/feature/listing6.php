class ProductMapper {
         ...
    protected function convertRowsToProducts(
	   array $rows
	): ProductCollection {
        $products = new ProductCollection();

        foreach($rows as $row) {
            $product = $this->convertRowToObject($row);
            $products->add($product);
        }

        return $products;
    }

    private function convertRowToObject(
        array $row
    ): Product {
        $product = new Product(
            $row['id'],
            $row['title'],
            $row['brand_name'],
            $row['catalog_price']
        );

        return $product;
    }

    ...
}