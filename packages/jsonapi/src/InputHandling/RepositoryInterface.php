<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\ConditionFactory\DrupalFilterInterface;
use EDT\Querying\SortMethodFactories\SortMethodInterface;
use Exception;

/**
 * Allows to fetch and manipulate entities, as well as handle given ones regarding matching conditions and sort methods.
 *
 * @template TEntity of object
 *
 * @template-extends ReadableRepositoryInterface<TEntity>
 */
interface RepositoryInterface extends ReadableRepositoryInterface
{
    /**
     * @param non-empty-string $id
     * @param list<DrupalFilterInterface> $conditions
     * @param non-empty-list<non-empty-string> $identifierPropertyPath
     *
     * @return TEntity
     */
    public function getEntityByIdentifier(string $id, array $conditions, array $identifierPropertyPath): object;

    /**
     * @param non-empty-list<non-empty-string> $identifiers
     * @param list<DrupalFilterInterface> $conditions
     * @param list<SortMethodInterface> $sortMethods
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
     * Deletes an entity identified by the given identifier and conditions.
     *
     * @param non-empty-string $entityIdentifier The entity's identifier.
     * @param list<DrupalFilterInterface> $conditions Additional conditions for delete operation, all conditions must match.
     * @param non-empty-list<non-empty-string> $identifierPropertyPath Property path used for entity identification.
     *
     * @throws Exception If deletion or its side effects fail.
     */
    public function deleteEntityByIdentifier(string $entityIdentifier, array $conditions, array $identifierPropertyPath): void;

    /**
     * @param non-empty-list<TEntity> $entities
     * @param list<DrupalFilterInterface> $conditions
     * @param list<SortMethodInterface> $sortMethods
     *
     * @return list<TEntity>
     */
    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array;

    /**
     * @param TEntity $entity
     * @param non-empty-list<DrupalFilterInterface> $conditions
     */
    public function isMatchingEntity(object $entity, array $conditions): bool;

    /**
     * @param TEntity $entity
     *
     * @param list<DrupalFilterInterface> $conditions
     *
     * @throws Exception
     */
    public function assertMatchingEntity(object $entity, array $conditions): void;
}
