<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\PathBuilding\PropertyAutoPathTrait;

/**
 * @property-read \EDT\PathBuilding\End $nonNested
 */
class NonNestedOnlyResource implements PropertyAutoPathInterface
{
    use PropertyAutoPathTrait;
}
