class OrderController
{
    public function __construct(
        private CommandBus $commandBus, 
        private OrderRepository $orderRepository, 
        private Serializer $serializer
    ) { }

    public function placeOrder(string $requestAsJson): void
    {
        $this->commandBus->sendWithRouting("placeOrder", $requestAsJson, "application/json");
    }

    (...)
}