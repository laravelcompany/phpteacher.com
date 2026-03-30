class PromotionalGiftListener implements Queueable
{
  public function __construct(
    protected TicketPurchasedEvent $event
  ) {
  }

  public function handle(): TicketPurchasedEvent
  {
    //hit the vendor API and register
    //the customer for their gift
  }
}

//Register the new Listener in our ListenerProvider
protected array $listeners = [
  TicketPurchasedEvent::class => [
    TicketPurchasedListener::class,
    PromotionalGiftListener::class, // <-- new listener
  ],
];

//In the EventDispatcher we add a new method
//that will enqueue the listener
public dispatch(object $event) {
  ...

  $listener = new $listenerClass($event);
  $implements = class_implements($event);
  in_array(Queueable::class, $implements)
    ? $this->queueListener($listener)
    : $listener->handle();
}

protected function queueListener(object $listener):void
{
  //queue the listener
}