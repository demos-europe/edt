<?php

declare(strict_types=1);

namespace EDT\Wrapping\PropertyBehavior;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * TODO: when TCondition is removed, move the child implementations into the `createFactory` method of the corresponding behavior implementation if possible, to reduce the amount of boilerplate classes
 *
 * @template TCondition of PathsBasedInterface
 */
interface PropertyUpdatabilityFactoryInterface
{
    /**
     * @template TEntity of object
     *
     * @param non-empty-string $name
     * @param non-empty-list<non-empty-string> $propertyPath
     * @param class-string<TEntity> $entityClass
     *
     * @return PropertyUpdatabilityInterface<TCondition, TEntity>
     */
    public function __invoke(string $name, array $propertyPath, string $entityClass): PropertyUpdatabilityInterface;
}
