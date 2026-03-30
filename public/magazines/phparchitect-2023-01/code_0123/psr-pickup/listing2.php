class TicketPurchasedListener
{
  public function __construct(
    protected TicketPurchasedEvent $event
  )
  {
  }

  public function handle(): TicketPurchasedEvent
  {
    $purchase = $this->event->getPurchaseData();
    $this->ticketBookkeeping($purchase);

    $movie_data = $this->event->getMovieData();
    $this->notifyTheater($movie_data);
  }

  protected function ticketBookKeeping(
    array $purchaseData
  ): void {
    //Does some bookkeeping
    //...
  }

  protected function notifyTheater(
    array $movieData
  ): void {
    //Notifies the theater of the ticket purchase
    //...
  }

  protected function notifyCustomer(
    array $purchaseData,
    array $movieData
  ) {
    //Send purchase confirmation and QR code
  }
}