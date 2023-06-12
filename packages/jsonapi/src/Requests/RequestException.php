<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use Exception;

class RequestException extends Exception
{
    public static function noRequest(): self
    {
        return new self('Resource creation failed. No request in request stack.');
    }

    public static function requestBody(string $content, \JsonException $exception): self
    {
        return new self("Failed to parse JSON request body: $content", 0, $exception);
    }
}
