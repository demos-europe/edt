<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\SortMethodFactories;

use EDT\DqlQuerying\Functions\Property;
use EDT\DqlQuerying\SortMethods\Ascending;
use EDT\DqlQuerying\SortMethods\Descending;
use EDT\DqlQuerying\Contracts\OrderBySortMethodInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\PropertyPaths\PropertyPath;

/**
 * @template-implements SortMethodFactoryInterface<OrderBySortMethodInterface>
 */
class SortMethodFactory implements SortMethodFactoryInterface
{
    public function propertyAscending(string $property, string ...$properties): PathsBasedInterface
    {
        array_unshift($properties, $property);
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK_RECURSIVE,  array_values($properties));
        return new Ascending(new Property($propertyPath));
    }

    public function propertyDescending(string $property, string ...$properties): PathsBasedInterface
    {
        array_unshift($properties, $property);
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK_RECURSIVE,  array_values($properties));
        return new Descending(new Property($propertyPath));
    }
}
