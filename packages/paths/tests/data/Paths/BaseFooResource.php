<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\PathBuilding\PropertyAutoPathTrait;

class BaseFooResource implements PropertyAutoPathInterface
{
    use PropertyAutoPathTrait;
}
