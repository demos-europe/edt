<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * @template T of object
 */
interface ObjectProviderInterface
{
    /**
     * @param array<int,FunctionInterface<bool>> $conditions
     * @param array<int,SortMethodInterface> $sortMethods
     *
     * @return iterable<T>
     *
     * @throws PathException
     * @throws SliceException
     * @throws SortException
     */
    public function getObjects(array $conditions, array $sortMethods = [], int $offset = 0, int $limit = null): iterable;
}
