<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\PropertyAutoPathTrait;
use Tests\data\Paths\nestedNamespace as NestedNs;

/**
 * @property-read NestedNs\AmbiguouslyNamedResource $aliasedNsNested
 */
class BrokenByNestedNsAlias
{
    use PropertyAutoPathTrait;
}
