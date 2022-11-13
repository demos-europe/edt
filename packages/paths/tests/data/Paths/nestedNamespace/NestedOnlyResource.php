<?php

declare(strict_types=1);

namespace Tests\data\Paths\nestedNamespace;

use EDT\PathBuilding\End;
use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\PathBuilding\PropertyAutoPathTrait;

/**
 * @property-read End $nested
 */
class NestedOnlyResource implements PropertyAutoPathInterface
{
    use PropertyAutoPathTrait;
}
