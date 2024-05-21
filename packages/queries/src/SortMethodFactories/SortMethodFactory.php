<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethodFactories;

use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Querying\Contracts\SortMethodFactoryInterface;

/**
 * @template-implements SortMethodFactoryInterface<SortMethodInterface>
 */
class SortMethodFactory implements SortMethodFactoryInterface
{
    public function propertyAscending(array|string|PropertyPathInterface $properties)
    {
        return SortMethod::ascendingByPath($properties);
    }

    public function propertyDescending(array|string|PropertyPathInterface $properties)
    {
        return SortMethod::descendingByPath($properties);
    }
}
