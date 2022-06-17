<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use Exception;
use function is_object;
use function get_class;
use function gettype;

class FunctionException extends Exception
{
    /**
     * @param mixed $functionReturn
     */
    public static function invalidReturnType(string $expected, $functionReturn): self
    {
        $actualType = is_object($functionReturn) ? get_class($functionReturn) : gettype($functionReturn);
        return new self("A function called another function and expected a return of type '$expected' but got '$actualType' instead.");
    }
}
