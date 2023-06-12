<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends TransferableTypeInterface<TCondition, TSorting, TEntity>
 * @template-extends FilteringTypeInterface<TCondition, TSorting>
 * @template-extends SortingTypeInterface<TCondition, TSorting>
 */
interface ResourceTypeInterface extends
    TransferableTypeInterface,
    FilteringTypeInterface,
    SortingTypeInterface,
    ReadableTypeInterface
{
}
