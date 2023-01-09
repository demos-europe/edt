<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * Creates a wrapper around an instance of a {@link TypeInterface::getEntityClass() backing object}.
 * Implementations can be tailored for a specific object class (specified via the template parameter `O`)
 * or be more generic and create wrappers suitable for different kinds of objects.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 *
 * TODO: check if usages can be replaced with {@link WrapperObjectFactory} and remove interface
 */
interface WrapperFactoryInterface
{
    /**
     * @template TEntity of object
     *
     * @param TEntity                                                  $entity
     * @param TransferableTypeInterface<TCondition, TSorting, TEntity> $type
     *
     * @return array<non-empty-string, mixed>|object|null
     *
     * @throws AccessException
     */
    public function createWrapper(object $entity, TransferableTypeInterface $type);
}
