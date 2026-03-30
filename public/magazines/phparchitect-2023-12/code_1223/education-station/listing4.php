class LazyDatabaseUserIterator implements Iterator
{
    private mixed $row;
    private int $index = 0;
    private mixed $statement;

    public function __construct(private \\PDO $pdo) {
    }

    public function current(): mixed
    {
        return $this->row;
    }

    public function next(): void
    {
        $this->row = $this->statement->fetch(
            PDO::FETCH_OBJ
        );
        $this->index++;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return !empty($this->row);
    }

    public function rewind(): void
    {
        $this->statement = $this->pdo->prepare(
            "SELECT * FROM Users"
        );
        $this->statement->execute();
        $this->row = $this->statement->fetch(
            PDO::FETCH_OBJ
        );
    }
}

$users = new Users(/** PDO connection **/);
foreach ($users as $user) {
	// Do something with the user
}