<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethods;

use EDT\Querying\Contracts\SortException;
use function is_string;
use function strcmp;
use function is_numeric;

trait SortMethodTrait
{
    /**
     * @param numeric|string|null $propertyValueA
     * @param numeric|string|null $propertyValueB
     *
     * @return int
     */
    protected function evaluateSinglePath($propertyValueA, $propertyValueB): int
    {
        if (null === $propertyValueA && null === $propertyValueB) {
            return 0;
        }
        if (null === $propertyValueA) {
            return -1;
        }
        if (null === $propertyValueB) {
            return 1;
        }
        if (is_numeric($propertyValueA) && is_numeric($propertyValueB)) {
            if ($propertyValueA < $propertyValueB) {
                return -1;
            }

            if ($propertyValueA > $propertyValueB) {
                return 1;
            }

            return 0;
        }
        if (is_string($propertyValueA) && is_string($propertyValueB)) {
            return strcmp($propertyValueA, $propertyValueB);
        }

        throw SortException::unsupportedTypeCombination($propertyValueA, $propertyValueB);
    }
}
