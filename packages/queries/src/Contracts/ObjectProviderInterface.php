<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 * @template T of object
 */
interface ObjectProviderInterface
{
    /**
     * @param list<C> $conditions
     * @param list<S> $sortMethods
     *
     * @return iterable<T>
     *
     * @throws PathException
     * @throws SliceException
     * @throws SortException
     */
    public function getObjects(array $conditions, array $sortMethods = [], int $offset = 0, int $limit = null): iterable;
}
