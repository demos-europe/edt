<?php

declare(strict_types=1);

namespace EDT\Querying\SortMethodFactories;

use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\Functions\Property;
use EDT\Querying\PropertyPaths\PropertyPath;
use EDT\Querying\SortMethods\Ascending;
use EDT\Querying\SortMethods\Descending;

class PhpSortMethodFactory implements SortMethodFactoryInterface
{
    public function propertyAscending(string $property, string ...$properties): SortMethodInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK_RECURSIVE, $property, ...$properties);
        return new Ascending(new Property($propertyPathInstance));
    }

    public function propertyDescending(string $property, string ...$properties): SortMethodInterface
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK_RECURSIVE, $property, ...$properties);
        return new Descending(new Property($propertyPathInstance));
    }
}
