<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

interface SortMethodFactoryInterface
{
    /**
     * @throws PathException
     */
    public function propertyAscending(string $property, string ...$properties): SortMethodInterface;

    /**
     * @throws PathException
     */
    public function propertyDescending(string $property, string ...$properties): SortMethodInterface;
}
