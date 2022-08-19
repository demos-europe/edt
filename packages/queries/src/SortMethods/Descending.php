<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethods;

class Descending extends AbstractSortMethod
{
    public function evaluate(array $propertyValuesA, array $propertyValuesB): int
    {
        return -parent::evaluate($propertyValuesA, $propertyValuesB);
    }

    public function __toString(): string
    {
        return "Descending with base function: $this->target";
    }
}
