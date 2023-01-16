<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * @template TSorting of PathsBasedInterface
 */
interface SortMethodFactoryInterface
{
    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TSorting
     *
     * @throws PathException
     */
    public function propertyAscending(string|array|PropertyPathInterface $properties): PathsBasedInterface;

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $properties
     *
     * @return TSorting
     *
     * @throws PathException
     */
    public function propertyDescending(string|array|PropertyPathInterface $properties): PathsBasedInterface;
}
