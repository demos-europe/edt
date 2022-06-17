<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

interface SortMethodInterface extends PathsBasedInterface
{
    /**
     * @param mixed[] $propertyValuesA
     * @param mixed[] $propertyValuesB
     */
    public function evaluate(array $propertyValuesA, array $propertyValuesB): int;
}
