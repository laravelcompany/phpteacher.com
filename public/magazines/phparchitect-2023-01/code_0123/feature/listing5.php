class ProductMapper {
         ...

  public function update(Product $product) {
    $publicProduct = new ReflectionWrapper($product);

    $sql = 'UPDATE products SET title = :title,
        brand_name = :brandName,
		catalog_price = :catalogPrice WHERE id = :id';

    $pdoStatement = $this->pdo->prepare($sql);
    $pdoStatement->bindValue(':id',
          $publicProduct->get('id'),
		  PDO::PARAM_INT);
    $pdoStatement->bindValue(':title',
		  $publicProduct->get('title'),
          PDO::PARAM_STR);
    $pdoStatement->bindValue(':brandName',
		  $publicProduct->get('brandName'),
          PDO::PARAM_STR);
    $pdoStatement->bindValue(':catalogPrice',
          $publicProduct->get('catalogPrice'),
          PDO::PARAM_INT);
    $pdoStatement->execute();
  }

  public function delete(Product $product) {
    $publicProduct = new ReflectionWrapper($product);

    $sql = 'DELETE FROM products WHERE id = :id';

    $pdoStatement = $this->pdo->prepare($sql);
    $pdoStatement->bindValue(':id',
          $publicProduct->get('id'),
		  PDO::PARAM_INT);
    $pdoStatement->execute();
  }

     ...
}