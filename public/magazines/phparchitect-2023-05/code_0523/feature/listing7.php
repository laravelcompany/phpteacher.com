class RouteDefinition
{
    use UriWrapper;

    public function __construct(
        public readonly string $path, 
        public readonly array $params
    )
    {
        // render a templated Uri that
        // matches our definition
        $this->setWrapped(new Uri(
            (string)
                (new UriTemplate($this->path))
                ->render($this->params)
        ));
    }
}