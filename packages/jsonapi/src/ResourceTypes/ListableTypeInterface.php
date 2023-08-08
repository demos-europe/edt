<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\FetchableTypeInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends EntityBasedInterface<TEntity>
 * @template-extends FetchableTypeInterface<TCondition, TSorting, TEntity>
 */
interface ListableTypeInterface extends EntityBasedInterface, NamedTypeInterface, ReadableTypeInterface, FetchableTypeInterface
{

}
