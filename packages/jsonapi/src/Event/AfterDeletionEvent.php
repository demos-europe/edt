<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\DeletableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class AfterDeletionEvent
{
    use ModifyEventTrait;

    /**
     * @param non-empty-string $entityIdentifier
     */
    public function __construct(
        protected readonly DeletableTypeInterface $type,
        protected readonly string $entityIdentifier
    ) {}

    public function getType(): DeletableTypeInterface
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
