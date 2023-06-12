<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 *
 * @template-implements EntityVerifierInterface<TCondition, TSorting>
 */
abstract class AbstractEntityVerifier implements EntityVerifierInterface
{
    public function filterEntity(?object $entity, array $conditions, TypeInterface $type): ?object
    {
        if (null === $entity) {
            return null;
        }

        if (!$type instanceof FilterableTypeInterface) {
            throw AccessException::typeNotFilterable($type);
        }

        return $this->applyFilterToEntity($entity, $conditions, $type);
    }

    public function filterEntities(array $entities, array $conditions, TypeInterface $type): array
    {
        if ([] === $entities) {
            return [];
        }

        if (!$type instanceof FilterableTypeInterface) {
            throw AccessException::typeNotFilterable($type);
        }

        return $this->applyFilterToEntities($entities, $conditions, $type);
    }

    public function sortEntities(array $entities, array $sortMethods, TypeInterface $type): array
    {
        if ([] === $entities) {
            return [];
        }

        return $this->applySortingToEntities($entities, $sortMethods, $type);
    }

    /**
     * @template TEntity of object
     *
     * @param TEntity $entity
     * @param list<TCondition> $conditions
     * @param FilterableTypeInterface<TCondition, TSorting, object> $type
     *
     * @return TEntity|null
     */
    abstract protected function applyFilterToEntity(object $entity, array $conditions, FilterableTypeInterface $type): ?object;

    /**
     * @template TEntity of object
     *
     * @param non-empty-list<TEntity> $entities
     * @param list<TCondition> $conditions
     * @param FilterableTypeInterface<TCondition, TSorting, object> $type
     *
     * @return list<TEntity>
     */
    abstract protected function applyFilterToEntities(array $entities, array $conditions, FilterableTypeInterface $type): array;

    /**
     * @template TEntity of object
     *
     * @param non-empty-list<TEntity> $entities
     * @param list<TSorting> $sortMethods
     * @param TypeInterface<TCondition, TSorting, TEntity> $type
     *
     * @return list<TEntity>
     */
    abstract protected function applySortingToEntities(array $entities, array $sortMethods, TypeInterface $type): array;
}
