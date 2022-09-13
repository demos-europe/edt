<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface SortMethodFactoryInterface
{
    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return S
     *
     * @throws PathException
     */
    public function propertyAscending(string $property, string ...$properties): PathsBasedInterface;

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return S
     *
     * @throws PathException
     */
    public function propertyDescending(string $property, string ...$properties): PathsBasedInterface;
}
