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

        $this->connection->beginTransaction();
        try {
            $this->orderRepository->save(new Order($data));
            $promotion->increaseOrderAmount();
            $this->promotionRepository->save($promotion);
            $this->paymentGateway->performPayment($data["creditCard"], $price);
            $this->emailService->sendTo($data["email"], $relatedEbooks);

            $this->connection->commit();
        }catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }
}