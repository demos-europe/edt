<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
interface TypeCarryEventInterface
{
    /**
     * @return AbstractResourceType<TCondition, TSorting, TEntity>
     */
    public function getType(): AbstractResourceType;
}
