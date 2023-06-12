<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts\Types;

use EDT\Querying\Contracts\PathsBasedInterface;
use Exception;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 */
interface FilterableTypeInterface
{
    /**
     * Removes items not matching the given conditions from the given list and sort the remaining
     * items by the given sort methods and by an internal default sorting.
     *
     * Implementations are also responsible to not return instances with restricted accessibility.
     *
     * @param list<TEntity> $entities
     * @param list<TCondition> $conditions
     *
     * @throws Exception
     */
    public function assertMatchingEntities(array $entities, array $conditions): void;

    /**
     * @param TEntity $entity
     * @param list<TCondition> $conditions
     *
     * @throws Exception
     */
    public function assertMatchingEntity(object $entity, array $conditions): void;

    /**
     * @param TEntity $entity
     * @param list<TCondition> $conditions
     */
    public function isMatchingEntity(object $entity, array $conditions): bool;
}
