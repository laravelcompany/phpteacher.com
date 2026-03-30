class OrderService
{
    public function placeOrder(array $data): void
    {
        $relatedEbooks = [];
        foreach ($data["ebookIds"] as $ebookId) {
            $relatedEbooks[] = $this->ebookRepository->getById($ebookId);
        }

        $price = Price::zero();
        foreach ($relatedEbooks as $ebook) {
            $price = $price->add($ebook->getPrice());
        }

        $promotion = $this->promotionRepository->getById($data['email']);
        $data["price"] = $promotion->isGrantedToPromotion() ? ($price->multiply(0.9)) : $price;

        $order = new \Ecotone\App\Model\Order\Order($data);
        $this->orderRepository->save($order);

// 1
        $this->eventBus->publish(new OrderWasPlaced($order->getOrderId()));
    }

// 2
    #[EventHandler]
    public function performPayment(OrderWasPlaced $event, PaymentGateway $paymentGateway): void
    {
        $order = $this->orderRepository->getById($event->orderId);
        $creditCard = $order->getCreditCard();

        $paymentGateway->performPayment($creditCard, $order->getPrice());

        $this->eventBus->publish(new OrderPaymentWasSuccessful($event->orderId));
    }

// 3
    #[EventHandler]
    public function sendTo(OrderPaymentWasSuccessful $event, EmailService $emailService): void
    {
        $order = $this->orderRepository->getById($event->orderId);
        $ebooks = array_map(fn(int $ebookId) => $this->ebookRepository->getById($ebookId), $order->getRelatedEbookIds());

        $emailService->sendTo($order->getEmail(), $ebooks);
    }

// 3
    #[EventHandler]
    public function increasePromotion(OrderPaymentWasSuccessful $event): void
    {
        $order = $this->orderRepository->getById($event->orderId);

        $promotion = $this->promotionRepository->getById($order->getEmail());
        $promotion->increaseOrderAmount();
        $this->promotionRepository->save($promotion);
    }
}