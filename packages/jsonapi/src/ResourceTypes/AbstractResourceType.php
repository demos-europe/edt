<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\InputHandling\RepositoryInterface;
use EDT\JsonApi\RequestHandling\ExpectedPropertyCollectionInterface;
use EDT\JsonApi\RequestHandling\ModifiedEntity;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Pagination\PagePagination;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\Contracts\Types\FetchableTypeInterface;
use EDT\Wrapping\CreationDataInterface;
use EDT\Wrapping\EntityDataInterface;
use EDT\Wrapping\ResourceBehavior\ResourceInstantiability;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Pagerfanta\Pagerfanta;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements ResourceTypeInterface<TCondition, TSorting, TEntity>
 * @template-implements FetchableTypeInterface<TCondition, TSorting, TEntity>
 * @template-implements GetableTypeInterface<TCondition, TSorting, TEntity>
 * @template-implements CreatableTypeInterface<TCondition, TSorting, TEntity>
 */
abstract class AbstractResourceType implements ResourceTypeInterface, FetchableTypeInterface, GetableTypeInterface, DeletableTypeInterface, CreatableTypeInterface
{
    /**
     * @return RepositoryInterface<TCondition, TSorting, TEntity>
     */
    abstract protected function getRepository(): RepositoryInterface;

    public function getExpectedUpdateProperties(): ExpectedPropertyCollectionInterface
    {
        return $this->getUpdatability()->getExpectedProperties();
    }

    public function updateEntity(string $entityId, EntityDataInterface $entityData): ModifiedEntity
    {
        $updatability = $this->getUpdatability();

        $entityConditions = array_merge(
            $updatability->getEntityConditions($entityData),
            $this->getAccessConditions()
        );
        $identifierPropertyPath = $this->getIdentifierPropertyPath();

        $entity = $this->getRepository()->getEntityByIdentifier($entityId, $entityConditions, $identifierPropertyPath);

        $requestDeviations = $updatability->updateProperties($entity, $entityData);

        return new ModifiedEntity($entity, $requestDeviations);
    }

    public function getExpectedInitializationProperties(): ExpectedPropertyCollectionInterface
    {
        return $this->getInstantiability()->getExpectedProperties();
    }

    public function deleteEntity(string $entityIdentifier): void
    {
        $identifierPropertyPath = $this->getIdentifierPropertyPath();
        $conditions = $this->getAccessConditions();
        $this->getRepository()->deleteEntityByIdentifier($entityIdentifier, $conditions, $identifierPropertyPath);
    }

    public function createEntity(CreationDataInterface $entityData): ModifiedEntity
    {
        $instantiability = $this->getInstantiability();

        [$entity, $requestDeviations] = $instantiability->initializeEntity($entityData);
        $idBasedDeviations = $instantiability->setIdentifier($entity, $entityData);
        // FIXME: check entity conditions, even though entity may not be persisted; responsibility to set them (or not) lies with the using dev
        $fillRequestDeviations = $instantiability->fillProperties($entity, $entityData);
        if (null === $idBasedDeviations) {
            // if no ID was provided in the request, we can expect that one will be created by the backend at some point,
            // which needs to be provided to the client
            $idBasedDeviations = [ContentField::ID];
        }
        $requestDeviations = array_merge($requestDeviations, $fillRequestDeviations, $idBasedDeviations);

        return new ModifiedEntity($entity, $requestDeviations);
    }

    /**
     * Returns the condition limiting the access to {@link EntityBasedInterface::getEntityClass() entities}
     * corresponding to this type.
     *
     * The returned condition is applied to the schema of the
     * {@link EntityBasedInterface::getEntityClass() backing entity}.
     *
     * Beside limiting the access depending on the authorization of the accessing user, the returned
     * condition can also be used to filter out invalid instances of the backing entity class:
     * E.g. even though a database may store different animals as a single `Animal` entity/table
     * there may be different types for different kinds of animals (`CatType`, `DogType`, ...).
     * For a list query on a `CatType` the condition returned by this method must define
     * limits to only get `Animal` instances that are a `Cat`.
     *
     * @return list<TCondition>
     */
    abstract protected function getAccessConditions(): array;

    abstract protected function getSchemaPathProcessor(): SchemaPathProcessor;

    /**
     * Get the sort methods to apply when a collection of this property is fetched and no sort methods were specified.
     *
     * The schema used in the sort methods must be the one of the {@link EntityBasedInterface::getEntityClass() backing entity class}.
     * I.e. no aliases are available.
     *
     * Return an empty array to not define any default sorting.
     *
     * @return list<TSorting>
     */
    abstract protected function getDefaultSortMethods(): array;

    /**
     * @return ResourceInstantiability<TEntity>
     */
    abstract protected function getInstantiability(): ResourceInstantiability;

    /**
     * Will change the paths of the given conditions and sort methods.
     *
     * The paths given items are expected to denote a resource property. For properties that are
     * an alias only the path will be adjusted to direct to the actual property in a backing entity.
     *
     * Note that this method must not be used for all conditions and sort methods. It is only
     * to be used for those directed at the resource, not the ones directed to an entity. E.g.
     * {@link self::getAccessConditions()} and {@link self::getDefaultSortMethods()} must not
     * be passed into this method, as their paths are expected to already denote actual properties in a
     * backing entity.
     *
     * @param list<TCondition> $conditions
     * @param list<TSorting> $sortMethods
     *
     * @throws PathException
     */
    protected function mapPaths(array $conditions, array $sortMethods): void
    {
        $schemaPathProcessor = $this->getSchemaPathProcessor();

        if ([] !== $conditions) {
            $schemaPathProcessor->mapFilterConditions($this, $conditions);
        }

        if ([] !== $sortMethods) {
            $schemaPathProcessor->mapSorting($this, $sortMethods);
        }
    }

    public function getEntitiesForRelationship(array $identifiers, array $conditions, array $sortMethods): array
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());
        $sortMethods = array_merge($sortMethods, $this->getDefaultSortMethods());

        return $this->getRepository()->getEntitiesByIdentifiers($identifiers, $conditions, $sortMethods, $this->getIdentifierPropertyPath());
    }

    public function getEntityForRelationship(string $identifier, array $conditions): object
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());

        return $this->getRepository()->getEntityByIdentifier($identifier, $conditions, $this->getIdentifierPropertyPath());
    }

    public function getEntity(string $identifier): object
    {
        $conditions = $this->getAccessConditions();
        $identifierPropertyPath = $this->getIdentifierPropertyPath();

        return $this->getRepository()->getEntityByIdentifier($identifier, $conditions, $identifierPropertyPath);
    }

    /**
     * The property path to the property uniquely identifying an entity instance corresponding to this type.
     *
     * Must return a path that directly corresponds to the backing entity. I.e. no aliases will be resolved by callers.
     *
     * @return non-empty-list<non-empty-string>
     */
    abstract protected function getIdentifierPropertyPath(): array;

    public function getEntities(array $conditions, array $sortMethods): array
    {
        $this->mapPaths($conditions, $sortMethods);
        $conditions = array_merge($conditions, $this->getAccessConditions());
        $sortMethods = array_merge($sortMethods, $this->getDefaultSortMethods());

        return $this->getRepository()->getEntities($conditions, $sortMethods);
    }

    public function getEntitiesForPage(array $conditions, array $sortMethods, PagePagination $pagination): Pagerfanta
    {
        $this->mapPaths($conditions, $sortMethods);
        $conditions = array_merge($conditions, $this->getAccessConditions());
        $sortMethods = array_merge($sortMethods, $this->getDefaultSortMethods());

        return $this->getRepository()->getEntitiesForPage($conditions, $sortMethods, $pagination);
    }

    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());
        $sortMethods = array_merge($sortMethods, $this->getDefaultSortMethods());

        if ([] === $entities) {
            return [];
        }

        if ([] === $conditions && [] === $sortMethods) {
            return $entities;
        }

        return $this->getRepository()->reindexEntities($entities, $conditions, $sortMethods);
    }

    public function assertMatchingEntity(object $entity, array $conditions): void
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());

        $this->getRepository()->assertMatchingEntity($entity, $conditions);
    }

    public function isMatchingEntity(object $entity, array $conditions): bool
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());

        if ([] === $conditions) {
            return true;
        }

        return $this->getRepository()->isMatchingEntity($entity, $conditions);
    }
}
