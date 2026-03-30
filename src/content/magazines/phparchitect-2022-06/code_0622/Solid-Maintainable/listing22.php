#[Asynchronous("order_channel")]
#[EventHandler(endpointId: "performPayment")]
public function performPayment(OrderWasPlaced $event, PaymentGateway $paymentGateway): void

(...)

#[Asynchronous("order_channel")]
#[EventHandler(endpointId: "sendTo")]
public function sendTo(OrderPaymentWasSuccessful $event, EmailService $emailService): void

(...)

#[Asynchronous("order_channel")]
#[EventHandler(endpointId: "increasePromotion")]
public function increasePromotion(OrderPaymentWasSuccessful $event): void