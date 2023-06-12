<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\IdRetrievableTypeInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends IdRetrievableTypeInterface<TCondition, TSorting, TEntity>
 */
interface GetableTypeInterface extends ReadableTypeInterface, IdRetrievableTypeInterface, NamedTypeInterface
{

}
