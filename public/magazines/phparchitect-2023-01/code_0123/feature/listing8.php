public function findExpensiveBrandedProducts(
	   $brandName
): ProductCollection {
    $sql = 'SELECT * FROM products
            WHERE brand_name = :brandName
	        AND catalog_price >= :priceLimit';

    $pdoStatement = $this->pdo->prepare($sql);
    $pdoStatement->bindValue(
        ':brandName', $brandName, PDO::PARAM_STR
    );
    $pdoStatement->bindValue(
        ':priceLimit', 1000, PDO::PARAM_INT
    );
    $pdoStatement->execute();

    $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    $products = $this->convertRowsToProducts($rows);

    return $products;
}