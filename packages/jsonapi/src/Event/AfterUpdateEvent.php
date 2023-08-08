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
 * @template-implements EntityCarryEventInterface<TEntity>
 * @template-implements TypeCarryEventInterface<TCondition, TSorting, TEntity>
 */
class AfterUpdateEvent implements EntityCarryEventInterface, TypeCarryEventInterface
{
    use ModifyEventTrait;

    /**
     * @param AbstractResourceType<TCondition, TSorting, TEntity> $type
     * @param TEntity $entity
     */
    public function __construct(
        protected readonly AbstractResourceType $type,
        protected readonly object $entity
    ) {}

    public function getType(): AbstractResourceType
    {
        return $this->type;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }
}
