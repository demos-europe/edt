<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface SortMethodFactoryInterface
{
    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return TSorting
     *
     * @throws PathException
     */
    public function propertyAscending(array $properties): PathsBasedInterface;

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return TSorting
     *
     * @throws PathException
     */
    public function propertyDescending(array $properties): PathsBasedInterface;
}
