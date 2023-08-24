<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Pagination\PagePagination;
use Exception;
use Pagerfanta\Pagerfanta;

/**
 * Allows to fetch and manipulate entities, as well as handle given ones regarding matching conditions and sort methods.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
interface RepositoryInterface
{
    /**
     * @param non-empty-string $id
     * @param list<TCondition> $conditions
     * @param non-empty-list<non-empty-string> $identifierPropertyPath
     *
     * @return TEntity
     */
    public function getEntityByIdentifier(string $id, array $conditions, array $identifierPropertyPath): object;

    /**
     * @param list<non-empty-string> $identifiers
     * @param list<TCondition> $conditions
     * @param list<TSorting> $sortMethods
     * @param non-empty-list<non-empty-string> $identifierPropertyPath
     *
     * @return list<TEntity>
     */
    public function getEntitiesByIdentifiers(
        array $identifiers,
        array $conditions,
        array $sortMethods,
        array $identifierPropertyPath
    ): array;

    /**
     * @param list<TCondition> $conditions
     * @param list<TSorting> $sortMethods
     *
     * @return list<TEntity>
     */
    public function getEntities(array $conditions, array $sortMethods): array;

    /**
     * @param list<TCondition> $conditions
     * @param list<TSorting> $sortMethods
     *
     * @return Pagerfanta<TEntity>
     */
    public function getEntitiesForPage(array $conditions, array $sortMethods, PagePagination $pagination): Pagerfanta;

    /**
     * Deletes an entity identified by the given identifier and conditions.
     *
     * @param non-empty-string $entityIdentifier The entity's identifier.
     * @param list<TCondition> $conditions Additional conditions for delete operation, all conditions must match.
     * @param non-empty-list<non-empty-string> $identifierPropertyPath Property path used for entity identification.
     *
     * @throws Exception If deletion or its side effects fail.
     */
    public function deleteEntityByIdentifier(string $entityIdentifier, array $conditions, array $identifierPropertyPath): void;

    /**
     * @param list<TEntity> $entities
     * @param list<TCondition> $conditions
     * @param list<TSorting> $sortMethods
     *
     * @return list<TEntity>
     */
    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array;

    /**
     * @param TEntity $entity
     * @param non-empty-list<TCondition> $conditions
     */
    public function isMatchingEntity(object $entity, array $conditions): bool;

    /**
     * @param TEntity $entity
     *
     * @param list<TCondition> $conditions
     *
     * @throws Exception
     */
    public function assertMatchingEntity(object $entity, array $conditions): void;
}
