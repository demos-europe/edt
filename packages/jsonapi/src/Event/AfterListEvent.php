<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements TypeCarryEventInterface<TCondition, TSorting, TEntity>
 */
class AfterListEvent implements TypeCarryEventInterface
{
    /**
     * @param AbstractResourceType<TCondition, TSorting, TEntity> $type
     * @param list<TEntity> $entities
     */
    public function __construct(
        protected readonly AbstractResourceType $type,
        protected readonly array $entities
    ) {}

    public function getType(): AbstractResourceType
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
