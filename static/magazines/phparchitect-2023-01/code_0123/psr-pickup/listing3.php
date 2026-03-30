class EventDispatcher
                  implements EventDispatcherInterface
{
  public function dispatch(object $event)
  {
    $provider = $this->getListenerProvider();
    $listeners=$provider->getListenersForEvent($event);
    foreach ($listeners as $listenerClass) {
      if (
        method_exists(
        $event,
        'isPropagationStopped'
        )
        && $event->isPropagationStopped()
      ) {
        break;
      }

      $listener = new $listenerClass($event);
      $listener->handle();
    }
  }

  protected function getListenerProvider(
  ): ListenerProviderInterface {
    return new ListenerProvider();
  }
}