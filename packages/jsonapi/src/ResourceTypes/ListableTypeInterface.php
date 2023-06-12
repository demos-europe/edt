<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\FetchableTypeInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends TypeInterface<TCondition, TSorting, object>
 * @template-extends FetchableTypeInterface<TCondition, TSorting, object>
 */
interface ListableTypeInterface extends TypeInterface, NamedTypeInterface, ReadableTypeInterface, FetchableTypeInterface
{

}
