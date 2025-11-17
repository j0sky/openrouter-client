<?php

declare(strict_types=1);

namespace OpenRouter\Exceptions;

/**
 * Exception thrown when API returns an error response
 */
class ApiException extends OpenRouterException
{
    private ?array $errorDetails;

    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, ?array $errorDetails = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorDetails = $errorDetails;
    }

    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }
}

