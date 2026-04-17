<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\RequestHandling\Body\CreationRequestBody;
use EDT\JsonApi\ResourceTypes\CreatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class AfterCreationEvent
{
    use ModifyEventTrait;

    /**
     * @param CreatableTypeInterface<TCondition, TSorting, TEntity> $type
     * @param TEntity $entity
     */
    public function __construct(
        protected readonly CreatableTypeInterface $type,
        protected readonly object $entity,
        protected readonly CreationRequestBody $requestBody
    ) {}

    /**
     * @return CreatableTypeInterface<TCondition, TSorting, TEntity>
     */
    public function getType(): CreatableTypeInterface
    {
        return $this->type;
    }

    /**
     * @return TEntity
     */
    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getRequestBody(): CreationRequestBody
    {
        return $this->requestBody;
    }
}
