final class DeliveryAcceptedController
{
  public function __construct(
    private readonly DeliveryService $deliveryService,
  ) {}

  public function __invoke(
    Request $request,
    string $delivery
  ): JsonResponse {
    // We start by validating our delivery
    if (! $this->deliveryService->expecting($delivery)) {
      throw new UnexpectedDeliveryException(
        message: "Unexpected delivery found: {$delivery}",
      );
    }
    // Next we want to ensure that the delivery
    // has all the expected parts
    if (! $this->deliveryService->validateContents(
            $delivery, $request->get('items')
    )) {
      throw new DeliveryItemsMisalignmentException(
        message: "Failed to validate contents of delivery,
                    manual check is required.",
      );
    }

    // Next, send this over to the stock management team
    dispatch(new DeliveryProcessed($delivery));
    // Finally we want to ensure that we notify the
    // Logistics team that this delivery has been processed.
    return new JsonResponse(
      data: [
        'message' => 'Delivery accepted.',
        'status' => DeliveryStatus::ACCEPTED,
      ],
      status: Status::ACCEPTED,
    );
  }
}