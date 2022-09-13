<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

interface SortMethodFactoryInterface
{
    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @throws PathException
     */
    public function propertyAscending(string $property, string ...$properties): SortMethodInterface;

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @throws PathException
     */
    public function propertyDescending(string $property, string ...$properties): SortMethodInterface;
}
