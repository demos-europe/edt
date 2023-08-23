<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\Event\AfterCreationEvent;
use EDT\JsonApi\Event\AfterDeletionEvent;
use EDT\JsonApi\Event\AfterGetEvent;
use EDT\JsonApi\Event\AfterListEvent;
use EDT\JsonApi\Event\AfterUpdateEvent;
use EDT\JsonApi\Event\BeforeCreationEvent;
use EDT\JsonApi\Event\BeforeDeletionEvent;
use EDT\JsonApi\Event\BeforeGetEvent;
use EDT\JsonApi\Event\BeforeListEvent;
use EDT\JsonApi\Event\BeforeUpdateEvent;
use EDT\JsonApi\InputHandling\RepositoryInterface;
use EDT\JsonApi\OutputHandling\DynamicTransformer;
use EDT\JsonApi\Properties\EntityInitializability;
use EDT\JsonApi\RequestHandling\ExpectedPropertyCollection;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\Requests\PropertyUpdaterTrait;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Querying\Pagination\PagePagination;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\FetchableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\EntityDataInterface;
use EDT\Wrapping\Properties\EntityReadability;
use EDT\Wrapping\Properties\RelationshipReadabilityInterface;
use EDT\Wrapping\Properties\EntityUpdatability;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use League\Fractal\TransformerAbstract;
use Pagerfanta\Pagerfanta;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

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
    use PropertyUpdaterTrait;

    public function getReadableProperties(): EntityReadability
    {
        return $this->getInitializedProperties()->getReadability();
    }

    public function getFilteringProperties(): array
    {
        return $this->getInitializedProperties()->getFilteringProperties();
    }

    public function getSortingProperties(): array
    {
        return $this->getInitializedProperties()->getSortingProperties();
    }

    public function getUpdatability(): EntityUpdatability
    {
        return $this->getInitializedProperties()->getUpdatability();
    }

    /**
     * @return RepositoryInterface<TCondition, TSorting, TEntity>
     */
    abstract protected function getRepository(): RepositoryInterface;

    public function getExpectedUpdateProperties(): ExpectedPropertyCollection
    {
        return $this->getUpdatability()->getExpectedProperties();
    }

    public function updateEntity(string $entityId, EntityDataInterface $entityData): ?object
    {
        $updatability = $this->getUpdatability();

        $entityConditions = array_merge(
            $updatability->getEntityConditions($entityData->getPropertyNames()),
            $this->getAccessConditions()
        );
        $identifierPropertyPath = $this->getIdentifierPropertyPath();

        $entity = $this->getRepository()->getEntityByIdentifier($entityId, $entityConditions, $identifierPropertyPath);

        $beforeUpdateEvent = new BeforeUpdateEvent($this, $entity);
        $this->getEventDispatcher()->dispatch($beforeUpdateEvent);

        $updateSideEffect = $updatability->updateEntity($entity, $entityData);

        $afterUpdateEvent = new AfterUpdateEvent($this, $entity);
        $this->getEventDispatcher()->dispatch($afterUpdateEvent);

        return !$beforeUpdateEvent->hasSideEffects()
            && !$afterUpdateEvent->hasSideEffects()
            && $updateSideEffect
            ? $entity
            : null;
    }

    public function getExpectedInitializationProperties(): ExpectedPropertyCollection
    {
        return $this->getInitializability()->getExpectedProperties();
    }

    public function deleteEntity(string $entityIdentifier): void
    {
        $beforeDeletionEvent = new BeforeDeletionEvent($this, $entityIdentifier);
        $this->getEventDispatcher()->dispatch($beforeDeletionEvent);

        $identifierPropertyPath = $this->getIdentifierPropertyPath();
        $conditions = $this->getAccessConditions();
        $this->getRepository()->deleteEntityByIdentifier($entityIdentifier, $conditions, $identifierPropertyPath);

        $afterDeletionEvent = new AfterDeletionEvent($this, $entityIdentifier);
        $this->getEventDispatcher()->dispatch($afterDeletionEvent);
    }

    public function createEntity(?string $entityId, EntityDataInterface $entityData): ?object
    {
        $initializability = $this->getInitializability();

        $beforeCreationEvent = new BeforeCreationEvent($this);
        $this->getEventDispatcher()->dispatch($beforeCreationEvent);

        $constructorArguments = $initializability->getConstructorArguments($entityId, $entityData);
        $entity = $initializability->initializeEntity($constructorArguments);
        $fillSideEffect = $initializability->fillEntity($entityId, $entity, $entityData);
        // FIXME: how to verify entity matches access conditions, when it is probably not flushed into the database yet?

        $afterCreationEvent = new AfterCreationEvent($this, $entity);
        $this->getEventDispatcher()->dispatch($afterCreationEvent);

        return !$beforeCreationEvent->hasSideEffects()
            && !$afterCreationEvent->hasSideEffects()
            && $fillSideEffect
            && null === $entityId
            ? $entity
            : null;
    }

    /**
     * @return EntityInitializability<TEntity, TCondition, TSorting>
     *
     * @see CreatableTypeInterface::getInitializableProperties()
     */
    protected function getInitializability(): EntityInitializability
    {
        return $this->getInitializedProperties()->getInitializability();
    }

    public function getTransformer(): TransformerAbstract
    {
        return new DynamicTransformer(
            $this,
            $this->getMessageFormatter(),
            $this->getLogger()
        );
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

    abstract protected function getMessageFormatter(): MessageFormatter;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function getEventDispatcher(): EventDispatcherInterface;

    /**
     * Array order: Even though the order of the properties returned within the array may have an
     * effect (e.g. determining the order of properties in JSON:API responses) you can not rely on
     * these effects; they may be changed in the future.
     *
     * @return list<PropertyConfig<TEntity, mixed, TCondition, TSorting>>
     */
    abstract protected function getProperties(): array;

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
     * @param list<PropertyConfig<TEntity, mixed, TCondition, TSorting>> $properties
     *
     * @return list<PropertyConfig<TEntity, mixed, TCondition, TSorting>>
     */
    protected function processProperties(array $properties): array
    {
        // do nothing by default
        return $properties;
    }

    /**
     * @return PropertyConfig<TEntity, mixed, TCondition, TSorting>
     */
    protected function createAttribute(PropertyPathInterface $path): PropertyConfig
    {
        return $this->getPropertyBuilderFactory()->createAttribute(
            $this->getEntityClass(),
            $path
        );
    }

    /**
     * @template TRelationship of object
     *
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface<TCondition, TSorting, object> $path
     *
     * @return PropertyConfig<TEntity, TRelationship, TCondition, TSorting>
     */
    protected function createToOneRelationship(
        PropertyPathInterface&ResourceTypeInterface $path,
        bool $defaultInclude = false
    ): PropertyConfig {
        return $this->getPropertyBuilderFactory()->createToOne(
            $this->getEntityClass(),
            $path,
            $defaultInclude
        );
    }

    /**
     * @template TRelationship of object
     *
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface<TCondition, TSorting, object> $path
     *
     * @return PropertyConfig<TEntity, TRelationship, TCondition, TSorting>
     */
    protected function createToManyRelationship(
        PropertyPathInterface&ResourceTypeInterface $path,
        bool $defaultInclude = false
    ): PropertyConfig {
        return $this->getPropertyBuilderFactory()->createToMany(
            $this->getEntityClass(),
            $path,
            $defaultInclude
        );
    }

    /**
     * @return PropertyBuilderFactory<TCondition, TSorting>
     */
    abstract protected function getPropertyBuilderFactory(): PropertyBuilderFactory;

    /**
     * @return EntityConfig<TCondition, TSorting, TEntity>
     */
    protected function getInitializedProperties(): EntityConfig
    {
        $properties = [];
        foreach ($this->processProperties($this->getProperties()) as $property) {
            $name = $property->getName();
            Assert::keyNotExists($properties, $name);
            $properties[$name] = $property;
        }

        return new EntityConfig($this->getEntityClass(), $properties);
    }

    /**
     * @param RelationshipReadabilityInterface<TransferableTypeInterface<PathsBasedInterface, PathsBasedInterface, object>> $readability
     */
    protected function isExposedReadability(RelationshipReadabilityInterface $readability): bool
    {
        $relationshipType = $readability->getRelationshipType();

        return $relationshipType instanceof ExposableRelationshipTypeInterface
            && $relationshipType->isExposedAsRelationship();
    }

    /**
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
        $beforeGetEvent = new BeforeGetEvent($this);
        $this->getEventDispatcher()->dispatch($beforeGetEvent);

        $conditions = $this->getAccessConditions();
        $identifierPropertyPath = $this->getIdentifierPropertyPath();

        $entity = $this->getRepository()->getEntityByIdentifier($identifier, $conditions, $identifierPropertyPath);

        $afterGetEvent = new AfterGetEvent($this, $entity);
        $this->getEventDispatcher()->dispatch($afterGetEvent);

        return $entity;
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
        $beforeListEvent = new BeforeListEvent($this);
        $this->getEventDispatcher()->dispatch($beforeListEvent);

        $this->mapPaths($conditions, $sortMethods);
        $conditions = array_merge($conditions, $this->getAccessConditions());
        $sortMethods = array_merge($sortMethods, $this->getDefaultSortMethods());

        $entities = $this->getRepository()->getEntities($conditions, $sortMethods);

        $afterListEvent = new AfterListEvent($this, $entities);
        $this->getEventDispatcher()->dispatch($afterListEvent);

        return $entities;
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

        return $this->getRepository()->reindexEntities($entities, $conditions, $sortMethods);
    }

    public function assertMatchingEntities(array $entities, array $conditions): void
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());

        $this->getRepository()->assertMatchingEntities($entities, $conditions);
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
