<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\PropertyAutoPathTrait;
use Tests\data\Paths as NonNestedNs;
use Tests\data\Paths\nestedNamespace as NestedNs;

/**
 * @property-read NonNestedNs\AmbiguouslyNamedResource|NestedNs\AmbiguouslyNamedResource $unionType
 */
class BrokenByUnionTypeProperty
{
    use PropertyAutoPathTrait;
}
