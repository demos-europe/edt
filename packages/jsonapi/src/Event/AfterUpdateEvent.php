<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\RequestHandling\Body\UpdateRequestBody;
use EDT\JsonApi\ResourceTypes\UpdatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class AfterUpdateEvent
{
    use ModifyEventTrait;

    /**
     * @param UpdatableTypeInterface<TCondition, TSorting, TEntity> $type
     * @param TEntity $entity
     */
    public function __construct(
        protected readonly UpdatableTypeInterface $type,
        protected readonly object $entity,
        protected readonly UpdateRequestBody $requestBody
    ) {}

    /**
     * @return UpdatableTypeInterface<TCondition, TSorting, TEntity>
     */
    public function getType(): UpdatableTypeInterface
    {
        return $this->type;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getRequestBody(): UpdateRequestBody
    {
        return $this->requestBody;
    }
}
