<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use Exception;

class FluentQueryException extends Exception
{
    public static function createNonUnique(): self
    {
        return new self('Expected at most one result, found multiple.');
    }

    public static function null(): self
    {
        return new self('Expected objects only as query result, got null.');
    }
}
