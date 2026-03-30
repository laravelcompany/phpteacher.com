class LazyResponse implements ResponseInterface
{
    use ResponseWrapper;

    protected ResponseInterface $wrapped;

    public function __construct(
        public readonly object $resource,
        public readonly Transformer $transformer,
        public readonly int $status = HttpStatus::OK
    ) { }

    public function withResource(
        object $resource
    ): self {
        return new self(
            $resource,
            $this->transformer,
            $this->status
        );
    }

    public function withTransformer(
        Transformer $transformer
    ): self {
        return new self(
            $this->resource,
            $transformer,
            $this->status
        );
    }

    /**
     * Replace the default behavior of the trait so
     * any call to a method not defined by this class
     * will return a transformed response using the
     * object the transformer
     */
    private function getWrapped(): ResponseInterface
    {
        return $this->wrapped ??= (
          fn() => new HalResponse(
            $this->transformer->make($this->resource),
            $this->status
          )
        )();
    }
}
