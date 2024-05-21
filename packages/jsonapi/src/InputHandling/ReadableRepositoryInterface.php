<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\ConditionFactory\DrupalFilterInterface;
use EDT\Querying\Pagination\PagePagination;
use EDT\Querying\SortMethodFactories\SortMethodInterface;
use Pagerfanta\Pagerfanta;

/**
 * @template TEntity of object
 */
interface ReadableRepositoryInterface
{
    /**
     * @param list<DrupalFilterInterface> $conditions
     * @param list<SortMethodInterface> $sortMethods
     *
     * @return list<TEntity>
     */
    public function getEntities(array $conditions, array $sortMethods): array;

    /**
     * @param list<DrupalFilterInterface> $conditions
     * @param list<SortMethodInterface> $sortMethods
     *
     * @return Pagerfanta<TEntity>
     */
    public function getEntitiesForPage(array $conditions, array $sortMethods, PagePagination $pagination): Pagerfanta;
}
