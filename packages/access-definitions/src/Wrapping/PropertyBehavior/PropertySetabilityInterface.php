<?php

declare(strict_types=1);

namespace EDT\Wrapping\PropertyBehavior;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\EntityDataInterface;

/**
 * Provides general accessibility information for a specific property.
 *
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 */
interface PropertySetabilityInterface extends PropertyConstrainingInterface
{
    /**
     * The entity to access a property of must match these conditions to be accessible by this instance.
     *
     * The conditions are allowed to access any properties of the entity without restrictions.
     *
     * @return list<TCondition>
     */
    public function getEntityConditions(): array;

    /**
     * @param TEntity $entity must match all conditions in {@link self::getEntityConditions()}
     *
     * @return bool `true` if the update had side effects, i.e. it changed properties other than
     *               the one defined in the given entity data; `false` otherwise
     */
    public function updateProperty(object $entity, EntityDataInterface $entityData): bool;
}
