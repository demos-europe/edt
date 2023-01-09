<?php

declare(strict_types=1);

namespace EDT\Querying\EntityProviders;

use EDT\Querying\Contracts\PaginationException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\SortException;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TPagination of object
 * @template TEntity of object
 */
interface EntityProviderInterface
{
    /**
     * @param list<TCondition> $conditions  the conditions to apply, the used paths are already mapped to the backing entity
     * @param list<TSorting> $sortMethods the sorting to apply, the used paths are already mapped to the backing entity
     * @param TPagination|null  $pagination
     *
     * @return iterable<TEntity>
     *
     * @throws SortException
     * @throws PaginationException
     */
    public function getEntities(array $conditions, array $sortMethods, ?object $pagination): iterable;
}
