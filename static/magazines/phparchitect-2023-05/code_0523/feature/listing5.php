class HalResponse implements ResponseInterface
{
    use ResponseWrapper;

    private array $hal;

    public function __construct(
        array $hal,
        int $status = HttpStatus::OK,
        array $headers = [],
        int $options = self::DEFAULT_JSON_FLAGS
    ) {
        $json = json_seralize($hal, $options);
        $type = 'application/hal+json';
        $response = (new Response($json))
          ->withStatus($status)
          ->withHeader('Content-Type', $type);

        foreach($headers as $header => $value) {
            $response = $response
                        ->withHeader($header, $value);
        }

        $this->hal = $hal;

        $this->setWrapped($response);
    }

    public function getHal(): array
    {
        return $this->hal;
    }
}