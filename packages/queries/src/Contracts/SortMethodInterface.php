<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

interface SortMethodInterface extends PathsBasedInterface
{
    /**
     * @param list<mixed> $propertyValuesA
     * @param list<mixed> $propertyValuesB
     */
    public function evaluate(array $propertyValuesA, array $propertyValuesB): int;

    public function __toString(): string;
}
