<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethods;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\SortException;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\PropertyPaths\PathInfo;
use InvalidArgumentException;
use function is_string;

abstract class AbstractSortMethod implements SortMethodInterface
{
    /**
     * @param FunctionInterface<mixed> $target
     */
    public function __construct(
        protected FunctionInterface $target
    ) {}

    public function getPropertyPaths(): array
    {
        return array_map(
            // forbid to-many paths because this doesn't work with sorting logically
            static fn (PathInfo $pathInfo): PathInfo => PathInfo::maybeCopy($pathInfo, false),
            $this->target->getPropertyPaths()
        );
    }

    /**
     * @throws SortException
     * @throws InvalidArgumentException
     */
    public function evaluate(array $propertyValuesA, array $propertyValuesB): int
    {
        $valueA = $this->target->apply($propertyValuesA);
        $valueB = $this->target->apply($propertyValuesB);

        return $this->evaluateSinglePath($valueA, $valueB);
    }

    /**
     * @param numeric|string|null $propertyValueA
     * @param numeric|string|null $propertyValueB
     *
     * @return int
     *
     * @throws SortException
     */
    protected function evaluateSinglePath(string|int|float|null $propertyValueA, string|int|float|null $propertyValueB): int
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
