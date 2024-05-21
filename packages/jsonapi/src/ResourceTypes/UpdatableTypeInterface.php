<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use EDT\Wrapping\Contracts\Types\UpdatableInterface;

/**
 * @template TEntity of object
 *
 * @template-extends UpdatableInterface<TEntity>
 */
interface UpdatableTypeInterface extends NamedTypeInterface, UpdatableInterface, ReadableTypeInterface
{

}
