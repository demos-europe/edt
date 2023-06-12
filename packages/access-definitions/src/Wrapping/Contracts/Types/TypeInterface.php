<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts\Types;

use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends EntityBasedInterface<TEntity>
 */
interface TypeInterface extends EntityBasedInterface
{
    /**
     * Returns the condition limiting the access to {@link TypeInterface::getEntityClass() entities}
     * corresponding to this type.
     *
     * The returned condition is applied to the schema of the
     * {@link TypeInterface::getEntityClass() backing entity}.
     *
     * Beside limiting the access depending on the authorization of the accessing user, the returned
     * condition can also be used to filter out invalid instances of the backing entity class:
     * E.g. even though a database may store different animals as a single `Animal` entity/table
     * there may be different types for different kinds of animals (`CatType`, `DogType`, ...).
     * For a list query on a `CatType` the condition returned by this method must define
     * limits to only get `Animal` instances that are a `Cat`.
     *
     * @return TCondition
     */
    public function getAccessCondition(): PathsBasedInterface;

    /**
     * Get the sort methods to apply when a collection of this property is fetched and no sort methods were specified.
     *
     * The schema used in the sort methods must be the one of the {@link TypeInterface::getEntityClass() backing entity class}.
     *
     * Return an empty array to not define any default sorting.
     *
     * @return list<TSorting>
     */
    public function getDefaultSortMethods(): array;
}
