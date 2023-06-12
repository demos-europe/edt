<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts\Types;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Pagination\PagePagination;
use Pagerfanta\Pagerfanta;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
interface FetchableTypeInterface
{
    /**
     * Get all entities of this type that match all the given conditions.
     *
     * Implementations are responsible to not return instances with restricted accessibility.
     *
     * @param list<TCondition> $conditions
     * @param list<TSorting> $sortMethods
     *
     * @return list<TEntity>
     */
    public function getEntities(array $conditions, array $sortMethods): array;

    /**
     * Get all entities of this type that match all the given conditions in a paginated manner.
     *
     * Implementations are responsible to not return instances with restricted accessibility.
     *
     * @param list<TCondition> $conditions
     * @param list<TSorting> $sortMethods
     *
     * @return Pagerfanta<TEntity>
     */
    public function getEntitiesForPage(array $conditions, array $sortMethods, PagePagination $pagination): Pagerfanta;
}
