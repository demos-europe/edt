<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\UpdatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class BeforeUpdateEvent
{
    use ModifyEventTrait;

    /**
     * @param UpdatableTypeInterface<TCondition, TSorting, TEntity> $type
     * @param non-empty-string $entityIdentifier
     */
    public function __construct(
        protected readonly UpdatableTypeInterface $type,
        protected readonly string $entityIdentifier
    ) {}

    /**
     * @return UpdatableTypeInterface<TCondition, TSorting, TEntity>
     */
    public function getType(): UpdatableTypeInterface
    {
        return $this->type;
    }

    /**
     * @return non-empty-string
     */
    public function getEntityIdentifier(): string
    {
        return $this->entityIdentifier;
    }
}
