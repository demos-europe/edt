<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
interface EntityVerifierInterface
{
    /**
     * @template TEntity of object
     *
     * @param TEntity|null $entity
     * @param list<TCondition> $conditions
     * @param TypeInterface<TCondition, TSorting, TEntity> $type
     *
     * @return TEntity|null
     */
    public function filterEntity(?object $entity, array $conditions, TypeInterface $type): ?object;

    /**
     * @template TEntity of object
     *
     * @param list<TEntity> $entities
     * @param list<TCondition> $conditions
     * @param TypeInterface<TCondition, TSorting, TEntity> $type
     *
     * @return list<TEntity>
     */
    public function filterEntities(array $entities, array $conditions, TypeInterface $type): array;

    /**
     * @template TEntity of object
     *
     * @param list<TEntity> $entities
     * @param list<TSorting> $sortMethods
     * @param TypeInterface<TCondition, TSorting, TEntity> $type
     *
     * @return list<TEntity>
     */
    public function sortEntities(array $entities, array $sortMethods, TypeInterface $type): array;
}
