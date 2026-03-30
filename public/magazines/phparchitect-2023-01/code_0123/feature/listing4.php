class ProductMapper
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = // fetch the PDO object
    }

    public function create(
            $title,
            $brandName,
            $catalogPrice
    ): Product {
        $sql = 'INSERT INTO products
                (title, brand_name, catalog_price)
		        VALUES (?, ? ,?)';

        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(
                1,
                $title,
                PDO::PARAM_STR
        );
        $pdoStatement->bindValue(
                2,
                $brandName,
                PDO::PARAM_STR
        );
        $pdoStatement->bindValue(
                3,
                $catalogPrice,
                PDO::PARAM_INT
        );
        try {
            $this->pdo->beginTransaction();
            $pdoStatement->execute();
            $productId = $this->pdo->lastInsertId();
            $this->pdo->commit();
        } catch (PDOExecption $e) {
            $this->pdo->rollback();
            throw $e;
        }

        $product = new Product(
		   $productId, 
		   $title, 
		   $brandName, 
		   $catalogPrice
		);
        return $product;
    }
         ...
}