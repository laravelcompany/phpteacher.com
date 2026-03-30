public function yieldRows(string $table): Generator {
    $statement = $this->pdo->query(sprintf('SELECT * FROM %s', $table));

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        // sneaky way to translate dates to DateTime
        // objects to reduce headaches later
        $row['created_at'] = new DateTime($row['created_at']);
        $row['updated_at'] = $row['updated_at'] === null ?
                              null :
                             new DateTime($row['updated_at']);

        yield $row;
    }
}