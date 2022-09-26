<?php

declare(strict_types=1);

namespace EDT\Querying\EntityProviders;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 * @template P of object
 * @template E of object
 */
interface EntityProviderInterface
{
    /**
     * @param list<C> $conditions  the conditions to apply, the used paths are already mapped to the backing entity
     * @param list<S> $sortMethods the sorting to apply, the used paths are already mapped to the backing entity
     * @param P|null  $pagination
     *
     * @return iterable<E>
     */
    public function getEntities(array $conditions, array $sortMethods, ?object $pagination): iterable;
}
