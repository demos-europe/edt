<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use EDT\Querying\Pagination\OffsetPagination;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
interface OffsetEntityProviderInterface
{
    /**
     * Applies the parameters to an array of entities that was given on instantiation and returns the result.
     *
     * @param list<TCondition> $conditions
     * @param list<TSorting> $sortMethods
     * @param OffsetPagination|null $pagination
     *
     * @return list<TEntity>
     *
     * @throws PaginationException
     * @throws SortException
     */
    public function getEntities(array $conditions, array $sortMethods, ?OffsetPagination $pagination): array;
}
