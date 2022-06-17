<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use Exception;

/**
 * Indicates a problem when slicing a list of items to a requested subset.
 */
class SliceException extends Exception
{
    public static function negativeOffset(int $offset): self
    {
        return new self("Negative offset ($offset) is not supported.");
    }

    public static function negativeLimit(int $limit): self
    {
        return new self("Negative limit ($limit) is not supported.");
    }
}
