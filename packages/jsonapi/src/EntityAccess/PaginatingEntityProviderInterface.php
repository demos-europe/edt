<?php

declare(strict_types=1);

namespace EDT\JsonApi\EntityAccess;

use Pagerfanta\Pagerfanta;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 * @template P of object
 * @template E of object
 */
interface PaginatingEntityProviderInterface
{
    /**
     * @param list<C> $conditions  the conditions to apply, the used paths are already mapped to the backing entity
     * @param list<S> $sortMethods the sorting to apply, the used paths are already mapped to the backing entity
     * @param P       $pagination
     *
     * @return Pagerfanta<E>
     */
    public function getEntityPaginator(array $conditions, array $sortMethods, object $pagination): Pagerfanta;
}
