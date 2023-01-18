<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\AliasableTypeInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends TransferableTypeInterface<TCondition, TSorting, TEntity>
 * @template-extends IdentifiableTypeInterface<TCondition, TSorting, TEntity>
 * @template-extends FilterableTypeInterface<TCondition, TSorting, TEntity>
 * @template-extends SortableTypeInterface<TCondition, TSorting, TEntity>
 */
interface ResourceTypeInterface extends
    TransferableTypeInterface,
    FilterableTypeInterface,
    SortableTypeInterface,
    IdentifiableTypeInterface,
    ExposablePrimaryResourceTypeInterface,
    AliasableTypeInterface
{
}
