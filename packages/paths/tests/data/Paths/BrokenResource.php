<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\End;
use EDT\PathBuilding\PropertyAutoPathTrait;

/**
 * @property-read End id
 */
class BrokenResource
{
    use PropertyAutoPathTrait;
}
