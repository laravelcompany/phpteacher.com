class ServerRequest implements ServerRequestInterface
{
  use ServerRequestWrapper;

  // a server request is usually constructed by
  // some factory, so we expected to be passed
  // the request to wrap
  public function __construct(
    ServerRequestInterface $request
  ) {
    // tell the trait what the wrapped request is
    $this->setWrapped($request);
  }

  public function getQueryParam(
    $name, $default = null
  ): ?string {
    return $this->getQueryParams()[$name] ?? $default;
  }
}