final class DeliveryAcceptedController
{
  public function __construct(
    private readonly DeliveryService $deliveryService,
    private readonly DeliveryFailedResponder $failed,
    private readonly DeliverySuccessfulResponder $responder,
  ) {}

  public function __invoke(
    Request $request,
    string $delivery
  ): JsonResponse {
    // We want to validate the delivery is expected.
    if (! $this->deliveryService->expecting($delivery)) {
      return $this->failed->respondWithUnexpectedDelivery(
        $request,
        $delivery,
      );
    }

    //We still want to validate the contents of the delivery
    if (! $this->deliveryService->validateContents(
        $delivery, $request->get('items')
    )) {
      return $this->failed->respondWithInvalidContents(
        $request,
        $delivery,
      );
    }

    return $this->responder->deliverySuccessful($delivery);
  }
}