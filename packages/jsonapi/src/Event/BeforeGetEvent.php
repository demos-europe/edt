<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\GetableTypeInterface;

/**
 * @template TEntity of object
 */
class BeforeGetEvent
{
    /**
     * @param GetableTypeInterface<TEntity> $type
     */
    public function __construct(
        protected readonly GetableTypeInterface $type
    ) {}

    /**
     * @return GetableTypeInterface<TEntity>
     */
    public function getType(): GetableTypeInterface
    {
        return $this->type;
    }
}
