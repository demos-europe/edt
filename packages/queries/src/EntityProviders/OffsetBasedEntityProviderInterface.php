<?php

declare(strict_types=1);

namespace EDT\Querying\EntityProviders;

use EDT\Querying\Pagination\OffsetBasedPagination;
use EDT\Querying\Contracts\SortException;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 * @template E of object
 *
 * @template-extends EntityProviderInterface<C, S, OffsetBasedPagination, E>
 */
interface OffsetBasedEntityProviderInterface extends EntityProviderInterface
{

    /**
     * @param list<C>                    $conditions
     * @param list<S>                    $sortMethods
     * @param OffsetBasedPagination|null $pagination
     *
     * @return iterable<E>
     *
     * @throws SortException
     */
    public function getEntities(array $conditions, array $sortMethods, ?object $pagination): iterable;
}
