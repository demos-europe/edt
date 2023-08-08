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
class BeforeCreationEvent implements TypeCarryEventInterface
{
    use ModifyEventTrait;

    /**
     * @param AbstractResourceType<TCondition, TSorting, TEntity> $type
     */
    public function __construct(
        protected readonly AbstractResourceType $type
    ) {}

    public function getType(): AbstractResourceType
    {
        return $this->type;
    }
}
