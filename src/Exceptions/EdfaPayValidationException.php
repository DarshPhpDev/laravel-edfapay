<?php

namespace DarshPhpDev\EdfaPay\Exceptions;

class EdfaPayValidationException extends EdfaPayException
{
    /** @var array */
    protected $errors;

    public function __construct(array $errors)
    {
        parent::__construct('EdfaPay payload validation failed: ' . implode(', ', $errors));
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
