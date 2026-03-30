trait ServerRequestWrapper
{
    private $wrapped;
    private $factory;

    // ...

    protected function setFactory(
        callable $factory
    ): void {
        $this->factory = $factory;
    }

    private function viaFactory(
        MessageInterface $message
    ): MessageInterface {
       if (!$this->factory) {
           return $message;
       }

       return call_user_func($this->factory, $message);
    }

    public function withCookieParams(
        array $cookies
    ): ServerRequestInterface {
      return $this->viaFactory(
        $this->getWrapped()->withCookieParams($cookies)
      );
    }

    // ...
}