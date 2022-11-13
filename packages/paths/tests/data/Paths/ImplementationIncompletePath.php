<?php

declare(strict_types=1);

namespace Tests\data\Paths;

// must have no docblock for testing purposes
use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\PathBuilding\PropertyAutoPathTrait;

class ImplementationIncompletePath implements PropertyAutoPathInterface
{
    use PropertyAutoPathTrait;
}
