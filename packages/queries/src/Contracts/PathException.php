<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use Exception;
use function implode;

/**
 * To be used to notify of invalid paths. The cause may be an invalid part in the path or the path as a whole.
 */
class PathException extends Exception
{
    public static function emptyPart(string ...$fullPath): self
    {
        $pathString = implode('.', $fullPath);
        return new self("A path must not contain empty parts. Found in '$pathString'.");
    }
}
