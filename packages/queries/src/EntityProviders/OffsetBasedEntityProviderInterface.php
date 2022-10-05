<?php

declare(strict_types=1);

namespace EDT\Querying\EntityProviders;

use EDT\Querying\Pagination\OffsetBasedPagination;
use EDT\Querying\Contracts\SortException;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends EntityProviderInterface<TCondition, TSorting, OffsetBasedPagination, TEntity>
 */
interface OffsetBasedEntityProviderInterface extends EntityProviderInterface
{

    /**
     * @param list<TCondition>                    $conditions
     * @param list<TSorting>                    $sortMethods
     * @param OffsetBasedPagination|null $pagination
     *
     * @return iterable<TEntity>
     *
     * @throws SortException
     */
    public function getEntities(array $conditions, array $sortMethods, ?object $pagination): iterable;
}
