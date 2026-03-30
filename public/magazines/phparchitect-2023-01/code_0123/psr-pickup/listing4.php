class ListenerProvider
             implements ListenerProviderInterface
{
  protected array $listeners = [
    TicketPurchasedEvent::class => [
      TicketPurchasedListener::class
    ],
  ];

  public function getListenersForEvent(
    object $event
  ): iterable {
    if ( ! array_key_exists(
              $event::class,
              $this->listeners
            )
    ) {
      return [];
    }

    return $this->listeners[$event::class];
  }
}