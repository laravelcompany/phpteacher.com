trait ServerRequestWrapper
{
    private ServerRequestInterface $wrapped;

    protected function setWrapped(
        ServerRequestInterface $request
    ): void {
        $this->wrapped = $request;
    }

    private function getWrapped():
        ServerRequestInterface
    {
        if (!
    ($this->wrapped instanceof ServerRequestInterface)
        ) {
            throw new \UnexpectedValueException(
                'must `setMessage` before using it'
            );
        }

        return $this->wrapped;
    }

    public function getServerParams(): array
    {
        return $this->getWrapped()->getServerParams();
    }

    // ...
}