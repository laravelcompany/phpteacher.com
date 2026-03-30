class Order
{
    private UuidInterface $orderId;
    private Email $email;
    private CreditCard $creditCard;
		/** @var int[] */
		private array $relatedEbookIds;
    private Price $price;
    private \DateTimeImmutable $occurredAt;

		/** To see full implementation check github repository */ 
}