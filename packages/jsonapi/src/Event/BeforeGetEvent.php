<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\GetableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class BeforeGetEvent
{
    /**
     * @param GetableTypeInterface<TCondition, TSorting, TEntity> $type
     */
    public function __construct(
        protected readonly GetableTypeInterface $type
    ) {}

    /**
     * @return GetableTypeInterface<TCondition, TSorting, TEntity>
     */
    public function getType(): GetableTypeInterface
    {
        return $this->type;
    }
}
