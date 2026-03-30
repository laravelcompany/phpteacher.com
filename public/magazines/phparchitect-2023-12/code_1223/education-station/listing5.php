class UserLoaderGenerator {
    public function __construct(private \PDO $pdo) {
    }

    public function getUsers(): \Generator {
        $statement = $this->pdo->prepare(
            "SELECT * FROM Users"
        );
        $statement->execute();

        while (
            $row = $statement->fetch(PDO::FETCH_OBJ)
        ) {
            yield $row;
        }
    }
}

$userLoader = new UserLoaderGenerator(
    /** PDO connection **/
);
foreach ($userLoader->getUsers() as $user) {
    // Process each user
}