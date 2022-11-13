<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\PathBuilding\PropertyAutoPathTrait;
use Tests\data\Paths as NonNestedNs;

/**
 * @property-read NonNestedNs\AmbiguouslyNamedResource $aliasedNsNonNested
 */
class BrokenByNonNestedNsAlias implements PropertyAutoPathInterface
{
    use PropertyAutoPathTrait;
}
