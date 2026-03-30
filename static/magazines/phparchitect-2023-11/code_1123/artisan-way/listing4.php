final class IncomindDeliveryService
                implements CommunicationService
{
	public function __construct(
		private readonly MessagingClient $message,
		private readonly DeliveryRepository $repository,
	) {}

	public function expected(string $delivery): bool
	{
		return $this->repository->expectingDeliveryId(
			id: $delivery,
		);
	}

	public function validateContents(
        array $items,
        string $delivery
    ): bool {
		$valid = $this->repository->deliveryContainsItems(
			items: $items,
			id: $delivery,
		);

		if (! $valid) {
			$this->message->distribute(
                new InvalidContents($items, $delivery)
            );

			return false;
		}

		return true;
	}
}