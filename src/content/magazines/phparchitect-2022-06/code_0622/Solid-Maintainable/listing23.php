public function placeOrder(string $requestAsJson): void
{
    $data = json_decode($requestAsJson, true, flags: JSON_THROW_ON_ERROR);
    $data['email'] = new Email($data['email']);
    $data['creditCard'] = new CreditCard(
        $data['creditCard']['number'],
        $data['creditCard']['cvc'],
        $data['creditCard']['validTillYear'],
        $data['creditCard']['validTillMonth']
    );

    $this->orderService->placeOrder($data);
}