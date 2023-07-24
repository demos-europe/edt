<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use EDT\Wrapping\Contracts\Types\UpdatableInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends UpdatableInterface<TCondition, TEntity>
 */
interface UpdatableTypeInterface extends NamedTypeInterface, UpdatableInterface, ReadableTypeInterface
{

}
