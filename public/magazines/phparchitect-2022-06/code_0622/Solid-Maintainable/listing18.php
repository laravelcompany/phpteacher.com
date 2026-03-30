class PromotionRepository
{
    public function getById(Email $email): Promotion
    {
        $data = $this->connection->executeQuery(sprintf(<<<SQL
            SELECT * FROM %s WHERE %s = :email
        SQL, "promotions", "email"),
            ["email" => $email->address]
        )->fetchAssociative();

        if (!$data) {
            return new Promotion($email);
        }

        return $this->serializer->convertToPHP(
            $this->underscoresToCamelCase($data),
            MediaType::APPLICATION_X_PHP_ARRAY,
            Promotion::class
        );
    }

	 ( /** To see full implementation check github repository */ )
}