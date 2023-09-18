<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\CreatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class BeforeCreationEvent
{
    use ModifyEventTrait;

    /**
     * @param CreatableTypeInterface<TCondition, TSorting, TEntity> $type
     */
    public function __construct(
        protected readonly CreatableTypeInterface $type
    ) {}

    /**
     * @return CreatableTypeInterface<TCondition, TSorting, TEntity>
     */
    public function getType(): CreatableTypeInterface
    {
        return $this->type;
    }
}
