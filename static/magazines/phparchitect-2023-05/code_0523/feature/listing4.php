class ServerRequest implements ServerRequestInterface
{
    use ServerRequestWrapper;

    private function __construct(
        ServerRequestInterface $request
    ) {
        // tell the trait what the wrapped request is
        $this->setWrapped($request);

        // any with method will now take the response
        // of the wrapped object, re-wrap it in
        // this class, and return the wrapping object
        // preserving our `CustomServerRequest`
        // through calls to with() methods
        $this->setFactory([self::class, 'instance']);
    }

    // a server request is usually constructed by
    // some factory, so we expected to be passed
    // the request to wrap
    public static function instance(
        ServerRequestInterface $request
    ): self {
        // only wrap if we have to
        if (!($request instanceof self)) {
            $request = new self($request);
        }

        return $request;
    }

    public function getQueryParam(
        string $name, string $default = null
    ): ?string {
        return $this->getQueryParams()[$name]
                    ?? $default;
    }
}