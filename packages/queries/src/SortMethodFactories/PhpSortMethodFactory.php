<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethodFactories;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\Functions\Property;
use EDT\Querying\PropertyPaths\PropertyPath;
use EDT\Querying\SortMethods\Ascending;
use EDT\Querying\SortMethods\Descending;

/**
 * @template-implements SortMethodFactoryInterface<SortMethodInterface>
 */
class PhpSortMethodFactory implements SortMethodFactoryInterface
{
    public function propertyAscending(string $property, string ...$properties): PathsBasedInterface
    {
        array_unshift($properties, $property);
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK_RECURSIVE,  array_values($properties));
        return new Ascending(new Property($propertyPathInstance));
    }

    public function propertyDescending(string $property, string ...$properties): PathsBasedInterface
    {
        array_unshift($properties, $property);
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK_RECURSIVE,  array_values($properties));
        return new Descending(new Property($propertyPathInstance));
    }
}
