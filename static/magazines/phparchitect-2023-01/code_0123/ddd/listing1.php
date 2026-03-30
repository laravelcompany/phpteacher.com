<?php
final class CtgoResponse
{
    private int $statusCode;
    private string $statusText;
    private string $errorSummary;
    private string $errorDescription;
    private array $responseBody;

    public function __construct(
        int $statusCode = 200,
        string $statusText = 'OK',
        string $errorSummary = '',
        string $errorDescription = '',
        array $responseBody = []
    ) {
        $this->statusCode = $statusCode;
        $this->statusText = $statusText;
        $this->errorSummary = $errorSummary;
        $this->errorDescription = $errorDescription;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorResponseBody(): array
    {
        return [
            'status_code' => $this->statusCode,
            'status_text' => $this->statusText,
            'error_summary' => $this->errorSummary,
            'error_description' =>
                $this->errorDescription,
        ];
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }

    public function getStatusText(): string
    {
        return $this->statusText;
    }
}