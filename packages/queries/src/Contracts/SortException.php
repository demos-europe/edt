<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use Exception;
use Throwable;
use function gettype;
use function implode;

/**
 * Indicates a problem during the sorting of items.
 */
class SortException extends Exception
{
    public static function forCountAndMethods(Throwable $throwable, int $count, SortMethodInterface ...$sortMethods): self
    {
        $sortMethodsString = implode(', ', $sortMethods);
        return new self("Sorting of $count items with the following methods failed: $sortMethodsString", 0, $throwable);
    }

    /**
     * @param mixed $propertyValueA
     * @param mixed $propertyValueB
     */
    public static function unsupportedTypeCombination($propertyValueA, $propertyValueB): self
    {
        $typeA = gettype($propertyValueA);
        $typeB = gettype($propertyValueB);
        return new self("Unsupported type combination: '$typeA' and '$typeB'");
    }
}
