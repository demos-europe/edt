<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\GetableTypeInterface;

/**
 * @template TEntity of object
 */
class AfterGetEvent
{
    /**
     * @param GetableTypeInterface<TEntity> $type
     * @param TEntity $entity
     */
    public function __construct(
        protected readonly GetableTypeInterface $type,
        protected readonly object $entity
    ) {}

    /**
     * @return GetableTypeInterface<TEntity>
     */
    public function getType(): GetableTypeInterface
    {
        return $this->type;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }
}
