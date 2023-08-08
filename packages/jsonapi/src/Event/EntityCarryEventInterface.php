<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

/**
 * @template TEntity of object
 */
interface EntityCarryEventInterface
{
    /**
     * @return TEntity
     */
    public function getEntity(): object;
}
