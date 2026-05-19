<?php

namespace DarshPhpDev\EdfaPay\Exceptions;

class EdfaPayApiException extends EdfaPayException
{
    /** @var array */
    protected $responseBody;

    public function __construct(string $message, int $statusCode = 0, array $responseBody = [])
    {
        parent::__construct($message, $statusCode);
        $this->responseBody = $responseBody;
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}
