class ProductMapper {
  ...
  public function findById($id): Product {
    $sql = 'SELECT * FROM products WHERE id = :id';

    $pdoStatement = $this->pdo->prepare($sql);
    $pdoStatement->bindValue(
      ':id',
      $id,
      PDO::PARAM_INT
    );
    $pdoStatement->execute();

    $row = $pdoStatement->fetch(PDO::FETCH_ASSOC);
    $product = $this->convertRowToObject($row);

    return $product;
  }

  public function findByTitle(
    $title
  ): ProductCollection {
    $sql = 'SELECT * FROM products ' .
           'WHERE title = :title';

    $pdoStatement = $this->pdo->prepare($sql);
    $pdoStatement->bindValue(
      ':title',
      $title,
      PDO::PARAM_STR
    );
    $pdoStatement->execute();

    $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    $products = $this->convertRowsToProducts($rows);

    return $products;
  }
...
}