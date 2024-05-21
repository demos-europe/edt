<?php

declare(strict_types=1);

namespace EDT\JsonApi\Event;

use EDT\JsonApi\ResourceTypes\ListableTypeInterface;

/**
 * @template TEntity of object
 */
class BeforeListEvent
{
    /**
     * @param ListableTypeInterface<TEntity> $type
     */
    public function __construct(
        protected readonly ListableTypeInterface $type
    ) {}

    /**
     * @return ListableTypeInterface<TEntity>
     */
    public function getType(): ListableTypeInterface
    {
        return $this->type;
    }
}
