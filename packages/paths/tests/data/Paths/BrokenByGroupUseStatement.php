<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use Tests\data\Paths\nestedNamespace\{AmbiguouslyNamedResource as NestedResource2};
use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\PathBuilding\PropertyAutoPathTrait;

/**
 * @property-read NestedResource2 $groupUse
 */
class BrokenByGroupUseStatement implements PropertyAutoPathInterface
{
    use PropertyAutoPathTrait;
}
