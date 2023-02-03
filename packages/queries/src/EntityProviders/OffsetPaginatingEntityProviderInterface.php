<?php

declare(strict_types=1);

namespace EDT\Querying\EntityProviders;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\Contracts\SortException;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends EntityProviderInterface<TCondition, TSorting, OffsetPagination, TEntity>
 */
interface OffsetPaginatingEntityProviderInterface extends EntityProviderInterface
{
    /**
     * @param list<TCondition>      $conditions
     * @param list<TSorting>        $sortMethods
     * @param OffsetPagination|null $pagination
     *
     * @return iterable<TEntity>
     *
     * @throws SortException
     */
    public function getEntities(array $conditions, array $sortMethods, ?object $pagination): iterable;
}
