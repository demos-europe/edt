<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\ListableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class BeforeListEvent
{
    /**
     * @param ListableTypeInterface<TCondition, TSorting, TEntity> $type
     */
    public function __construct(
        protected readonly ListableTypeInterface $type
    ) {}

    /**
     * @return ListableTypeInterface<TCondition, TSorting, TEntity>
     */
    public function getType(): ListableTypeInterface
    {
        return $this->type;
    }
}
