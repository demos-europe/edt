<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use EDT\Querying\EntityProviders\EntityProviderInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @deprecated use {@link EntityProviderInterface} instead
 */
interface ObjectProviderInterface
{
    /**
     * @param list<TCondition> $conditions
     * @param list<TSorting>   $sortMethods
     * @param int<0, max>      $offset
     * @param int<0, max>|null $limit
     *
     * @return iterable<TEntity>
     *
     * @throws PathException
     * @throws PaginationException
     * @throws SortException
     *
     * @deprecated use {@link EntityProviderInterface} instead
     */
    public function getObjects(array $conditions, array $sortMethods = [], int $offset = 0, int $limit = null): iterable;
}
