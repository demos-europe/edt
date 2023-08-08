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
class BeforeDeletionEvent implements TypeCarryEventInterface
{
    /**
     * @param AbstractResourceType<TCondition, TSorting, TEntity> $type
     * @param non-empty-string $entityIdentifier
     */
    public function __construct(
        protected readonly AbstractResourceType $type,
        protected readonly string $entityIdentifier
    ) {}

    public function getType(): AbstractResourceType
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
