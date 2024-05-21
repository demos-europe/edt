<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;

/**
 * @template TEntity of object
 *
 * @template-extends TransferableTypeInterface<TEntity>
 */
interface ResourceTypeInterface extends
    TransferableTypeInterface,
    FilteringTypeInterface,
    SortingTypeInterface,
    ReadableTypeInterface
{
}
