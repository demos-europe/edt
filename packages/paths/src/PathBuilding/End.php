<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Querying\Contracts\PropertyPathInterface;
use IteratorAggregate;

/**
 * @template T
 */
class End implements IteratorAggregate, PropertyPathInterface
{
    use PropertyAutoPathTrait;
}
