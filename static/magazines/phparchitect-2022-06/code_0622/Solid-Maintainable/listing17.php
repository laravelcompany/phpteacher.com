class OrderRepository
{
    public function __construct(private Connection $connection, private Serializer $serializer)
    {
    }

    public function save(Order $order): void
    {
        $data = $this->serializer->convertFromPHP($order, MediaType::APPLICATION_X_PHP_ARRAY);
        $data["relatedEbookIds"] = \json_encode($data["relatedEbookIds"]);
        $data["creditCard"] = \json_encode($data['creditCard']);

        $this->connection->insert("orders", $this->convertCamelCaseToUnderscores($data));
    }

		( /** To see full implementation check github repository */ )
}