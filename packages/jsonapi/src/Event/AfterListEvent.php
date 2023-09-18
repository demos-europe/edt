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
class AfterListEvent
{
    /**
     * @param ListableTypeInterface<TCondition, TSorting, TEntity> $type
     * @param list<TEntity> $entities
     */
    public function __construct(
        protected readonly ListableTypeInterface $type,
        protected readonly array $entities
    ) {}

    /**
     * @return ListableTypeInterface<TCondition, TSorting, TEntity>
     */
    public function getType(): ListableTypeInterface
    {
        return $this->type;
    }

    /**
     * @return list<TEntity>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }
}
