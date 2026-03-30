class MethodNotAllowedProblemResponse
        extends RuntimeException
        implements ResponseInterface
{
    use ResponseWrapper;

    private function __construct()
    {
        $status_code = HttpStatus::METHOD_NOT_ALLOWED;
        $title = 'Method Not Allowed';
        $detail = 'HTTP method used is not supported.';

        $this->setWrapped(
            ApiProblemFactory::make(
                $status_code,
                $title,
                ['detail' => $detail]
            )
        );
        parent::__construct(
            "{$title}: {$detail}", $status_code
        );
    }

    public static function make(): self
    {
        return new self();
    }
}