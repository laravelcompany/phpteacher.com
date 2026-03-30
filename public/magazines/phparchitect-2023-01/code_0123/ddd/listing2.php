<?php
class CtgoException extends RuntimeException
{
    private CtgoResponse $ctgoResponse;

    public function __construct(
        CtgoResponse $ctgoResponse,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->ctgoResponse = $ctgoResponse;
    }

    public function getCtgoResponse(): CtgoResponse
    {
        return $this->ctgoResponse;
    }
}