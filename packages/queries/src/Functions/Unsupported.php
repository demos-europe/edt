<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

use EDT\Querying\Contracts\FunctionInterface;
use RuntimeException;

/**
 * @template-implements FunctionInterface<mixed>
 */
class Unsupported implements FunctionInterface
{
    private const MESSAGE = 'An instance of this class was used to allow type compatibility of a non-function with functions but was not supposed to be used as function.';

    public function apply(array $propertyValues)
    {
        throw new RuntimeException(self::MESSAGE);
    }

    public function __toString(): string
    {
        throw new RuntimeException(self::MESSAGE);
    }

    public function getPropertyPaths(): array
    {
        throw new RuntimeException(self::MESSAGE);
    }
}
