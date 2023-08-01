<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\InputTransformation\EntityCreator;
use EDT\JsonApi\InputTransformation\EntityUpdater;
use EDT\JsonApi\InputTransformation\SetabilityCollection;
use EDT\JsonApi\OutputTransformation\DynamicTransformer;
use EDT\JsonApi\RequestHandling\Body\CreationRequestBody;
use EDT\JsonApi\RequestHandling\Body\UpdateRequestBody;
use EDT\JsonApi\RequestHandling\ExpectedPropertyCollection;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\RequestHandling\SideEffectHandleTrait;
use EDT\JsonApi\Requests\PropertyUpdaterTrait;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Querying\Pagination\PagePagination;
use EDT\Querying\PropertyPaths\PropertyLink;
use EDT\Wrapping\Contracts\EntityFetcherInterface;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\FetchableTypeInterface;
use EDT\Wrapping\Contracts\Types\IdRetrievableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\AttributeInitializabilityInterface;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;
use EDT\Wrapping\Properties\AttributeSetabilityInterface;
use EDT\Wrapping\Properties\ConstructorParameterInterface;
use EDT\Wrapping\Properties\IdReadabilityInterface;
use EDT\Wrapping\Properties\InitializabilityCollection;
use EDT\Wrapping\Properties\PropertyAccessibilityInterface;
use EDT\Wrapping\Properties\ReadabilityCollection;
use EDT\Wrapping\Properties\RelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipInitializabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipSetabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipInitializabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipSetabilityInterface;
use EDT\Wrapping\Properties\UpdatablePropertyCollection;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Exception;
use League\Fractal\TransformerAbstract;
use Pagerfanta\Pagerfanta;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements ResourceTypeInterface<TCondition, TSorting, TEntity>
 * @template-implements FetchableTypeInterface<TCondition, TSorting, TEntity>
 * @template-implements IdRetrievableTypeInterface<TCondition, TSorting, TEntity>
 */
abstract class AbstractResourceType implements ResourceTypeInterface, FetchableTypeInterface, IdRetrievableTypeInterface
{
    use PropertyUpdaterTrait;
    use SideEffectHandleTrait;

    public function __construct(
        protected EntityCreator $entityCreator = new EntityCreator(),
        protected EntityUpdater $entityUpdater = new EntityUpdater()
    ) {}

    public function getReadableProperties(): ReadabilityCollection
    {
        $configCollection = $this->getInitializedProperties();
        $idReadabilities = array_filter(
            array_map(
                static fn (PropertyBuilder $property): ?IdReadabilityInterface => $property->getIdentifierReadability(),
                $configCollection
            ),
            static fn (?IdReadabilityInterface $readability): bool => null !== $readability
        );
        Assert::count($idReadabilities, 1);

        return new ReadabilityCollection(
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?AttributeReadabilityInterface => $property->getAttributeReadability(),
                    $configCollection
                ),
                static fn (?AttributeReadabilityInterface $readability): bool => null !== $readability
            ),
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?ToOneRelationshipReadabilityInterface => $property->getToOneRelationshipReadability(),
                    $configCollection
                ),
                fn (?ToOneRelationshipReadabilityInterface $readability): bool => null !== $readability && $this->isExposedReadability($readability)
            ),
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?ToManyRelationshipReadabilityInterface => $property->getToManyRelationshipReadability(),
                    $configCollection
                ),
                fn (?ToManyRelationshipReadabilityInterface $readability): bool => null !== $readability && $this->isExposedReadability($readability)
            ),
            array_pop($idReadabilities)
        );
    }

    public function getFilteringProperties(): array
    {
        $propertyArray = $this->getInitializedProperties();
        $propertyArray = array_map(
            static fn (PropertyBuilder $property): PropertyLink => new PropertyLink(
                $property->getPropertyPath(),
                $property->getFilterableRelationshipType()
            ),
            array_filter(
                $propertyArray,
                static fn (PropertyBuilder $property): bool => $property->isFilterable()
            )
        );

        return $this->keepExposedTypes($propertyArray);
    }

    public function getSortingProperties(): array
    {
        $propertyArray = array_map(
            static fn (PropertyBuilder $property): PropertyLink => new PropertyLink(
                $property->getPropertyPath(),
                $property->getSortableRelationshipType()
            ),
            array_filter(
                $this->getInitializedProperties(),
                static fn (PropertyBuilder $property): bool => $property->isSortable()
            )
        );

        return $this->keepExposedTypes($propertyArray);
    }

    public function getUpdatableProperties(): UpdatablePropertyCollection
    {
        $properties = $this->getInitializedProperties();

        return new UpdatablePropertyCollection(
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?AttributeSetabilityInterface => $property->getAttributeUpdatability(),
                    $properties
                ),
                static fn (?AttributeSetabilityInterface $updatability): bool => null !== $updatability
            ),
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?ToOneRelationshipSetabilityInterface => $property->getToOneRelationshipUpdatability(),
                    $properties
                ),
                static fn (?ToOneRelationshipSetabilityInterface $updatability): bool => null !== $updatability
            ),
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?ToManyRelationshipSetabilityInterface => $property->getToManyRelationshipUpdatability(),
                    $properties
                ),
                static fn (?ToManyRelationshipSetabilityInterface $updatability): bool => null !== $updatability
            )
        );
    }

    /**
     * @return EntityFetcherInterface<TCondition, TSorting, TEntity>
     */
    abstract protected function getEntityFetcher(): EntityFetcherInterface;

    public function getExpectedUpdateProperties(): ExpectedPropertyCollection
    {
        $updatabilityCollection = $this->getUpdatableProperties();

        $attributeSetabilities = $updatabilityCollection->getAttributes();
        $toOneRelationshipSetabilities = $updatabilityCollection->getToOneRelationships();
        $toManyRelationshipSetabilities = $updatabilityCollection->getToManyRelationships();

        return new ExpectedPropertyCollection(
            [],
            [],
            [],
            $attributeSetabilities,
            $this->mapToRelationshipIdentifiers($toOneRelationshipSetabilities),
            $this->mapToRelationshipIdentifiers($toManyRelationshipSetabilities)
        );
    }

    /**
     * Simply merges all entity conditions given in the setabilities with the {@link self::getAccessConditions()} of
     * this instance and returns the result.
     *
     * Does not process any paths, as both the access conditions and the setability entity conditions are expected to
     * be hardcoded and not supplied via request.
     *
     * @param SetabilityCollection<TCondition, TSorting, TEntity> $setabilityCollection
     *
     * @return list<TCondition>
     */
    protected function aggregateUpdateEntityConditions(SetabilityCollection $setabilityCollection): array {
        return array_merge(
            $this->getAccessConditions(),
            ...array_map(
                static fn (PropertyAccessibilityInterface $accessibility): array => $accessibility->getEntityConditions(),
                array_merge(
                    $setabilityCollection->getAttributeSetabilities(),
                    $setabilityCollection->getToOneRelationshipSetabilities(),
                    $setabilityCollection->getToManyRelationshipSetabilities()
                )
            )
        );
    }

    public function updateEntity(UpdateRequestBody $requestBody): ?object
    {
        $updatableProperties = $this->getUpdatableProperties();
        $setabilities = SetabilityCollection::createForUpdate($requestBody, $updatableProperties);

        $id = $requestBody->getId();
        $entityConditions = $this->aggregateUpdateEntityConditions($setabilities);
        $identifierPropertyPath = $this->getIdentifierPropertyPath();

        $entity = $this->getEntityFetcher()->getEntityByIdentifier($id, $entityConditions, $identifierPropertyPath);
        $sideEffects = $this->entityUpdater->updateEntity($entity, $requestBody, $setabilities);

        return $this->mergeSideEffects($sideEffects)
            ? $entity
            : null;
    }

    /**
     * @see CreatableTypeInterface::getExpectedInitializationProperties()
     */
    public function getExpectedInitializableProperties(): ExpectedPropertyCollection
    {
        $initializableProperties = $this->getInitializableProperties();

        return new ExpectedPropertyCollection(
            $initializableProperties->getRequiredAttributes(),
            $initializableProperties->getRequiredToOneRelationshipIdentifiers(),
            $initializableProperties->getRequiredToManyRelationshipIdentifiers(),
            $initializableProperties->getOptionalAttributes(),
            $initializableProperties->getOptionalToOneRelationshipIdentifiers(),
            $initializableProperties->getOptionalToManyRelationshipIdentifiers()
        );
    }

    /**
     * @return TEntity|null
     *
     * @see CreatableTypeInterface::createEntity()
     */
    public function createEntity(CreationRequestBody $requestBody): ?object
    {
        $initializableProperties = $this->getInitializableProperties();
        $setabilities = SetabilityCollection::createForCreation($requestBody, $initializableProperties);
        $orderedConstructorArguments = $initializableProperties->getOrderedConstructorArguments();
        $constructorArguments = $this->getConstructorArguments($orderedConstructorArguments, $requestBody);

        $entity = $this->entityCreator->createEntity($this->getEntityClass(), $constructorArguments);
        $sideEffects = $this->entityUpdater->updateEntity($entity, $requestBody, $setabilities);

        return $this->mergeSideEffects($sideEffects) && null !== $requestBody->getId()
            ? $entity
            : null;
    }

    /**
     * @param array<non-empty-string, ConstructorParameterInterface<TCondition, TSorting>> $constructorParameters
     *
     * @return list<mixed>
     *
     * @throws Exception
     */
    protected function getConstructorArguments(array $constructorParameters, CreationRequestBody $requestBody): array
    {
        $constructorArguments = [];
        foreach ($constructorParameters as $propertyName => $constructorParameter) {
            if (!$requestBody->hasProperty($propertyName)) {
                break;
            }

            if ($constructorParameter->isAttribute()) {
                $constructorArguments[] = $requestBody->getAttributeValue($propertyName);
            } elseif ($constructorParameter->isToOneRelationship()) {
                $relationshipRef = $requestBody->getToOneRelationshipReference($propertyName);
                $constructorArguments[] = $this->determineToOneRelationshipValue(
                    $constructorParameter->getRelationshipType(),
                    $constructorParameter->getRelationshipConditions(),
                    $relationshipRef
                );
            } else {
                $relationshipRefs = $requestBody->getToManyRelationshipReferences($propertyName);
                $constructorArguments[] = $this->determineToManyRelationshipValues(
                    $constructorParameter->getRelationshipType(),
                    $constructorParameter->getRelationshipConditions(),
                    $relationshipRefs
                );
            }
        }

        return $constructorArguments;
    }

    /**
     * @return InitializabilityCollection<TEntity, TCondition, TSorting>
     *
     * @see CreatableTypeInterface::getInitializableProperties()
     */
    protected function getInitializableProperties(): InitializabilityCollection
    {
        $properties = $this->getInitializedProperties();

        $requiredConstructorParameters = array_filter(
            array_map(
                static fn (PropertyBuilder $property): ?ConstructorParameterInterface => $property->getRequiredConstructorParameter(),
                $properties
            ),
            static fn (?ConstructorParameterInterface $constructorParameter): bool => null !== $constructorParameter
        );
        $optionalConstructorParameters = array_filter(
            array_map(
                static fn (PropertyBuilder $property): ?ConstructorParameterInterface => $property->getOptionalConstructorParameter(),
                $properties
            ),
            static fn (?ConstructorParameterInterface $constructorParameter): bool => null !== $constructorParameter
        );

        $sortedRequiredConstructorParameters = [];
        $sortedOptionalConstructorParameters = [];
        $constructor = $this->getConstructor(new ReflectionClass($this->getEntityClass()));
        foreach (null === $constructor ? [] : $constructor->getParameters() as $reflectionParameter) {
            $parameterName = $reflectionParameter->getName();
            Assert::stringNotEmpty($parameterName);
            // TODO: verify correct types
            // TODO: throw exception on missing required parameters
            if ($reflectionParameter->isOptional()) {
                $constructorParameter = $optionalConstructorParameters[$parameterName];
                $sortedOptionalConstructorParameters[$parameterName] = $constructorParameter;
            } else {
                $constructorParameter = $requiredConstructorParameters[$parameterName];
                $sortedRequiredConstructorParameters[$parameterName] = $constructorParameter;
            }
        }

        return new InitializabilityCollection(
            $sortedRequiredConstructorParameters,
            $sortedOptionalConstructorParameters,
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?AttributeSetabilityInterface => $property->getAttributeInitializability(),
                    $properties
                ),
                static fn (?AttributeInitializabilityInterface $initializability): bool => null !== $initializability
            ),
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?ToOneRelationshipSetabilityInterface => $property->getToOneRelationshipInitializability(),
                    $properties
                ),
                static fn (?ToOneRelationshipInitializabilityInterface $initializability): bool => null !== $initializability
            ),
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?ToManyRelationshipSetabilityInterface => $property->getToManyRelationshipInitializability(),
                    $properties
                ),
                static fn (?ToManyRelationshipInitializabilityInterface $initializability): bool => null !== $initializability
            )
        );
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
     * @return list<PropertyBuilder<TEntity, mixed, TCondition, TSorting>>
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
     * @param list<PropertyBuilder<TEntity, mixed, TCondition, TSorting>> $properties
     *
     * @return list<PropertyBuilder<TEntity, mixed, TCondition, TSorting>>
     */
    protected function processProperties(array $properties): array
    {
        // do nothing by default
        return $properties;
    }

    /**
     * @return PropertyBuilder<TEntity, mixed, TCondition, TSorting>
     */
    protected function createAttribute(PropertyPathInterface $path): PropertyBuilder
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
     * @return PropertyBuilder<TEntity, TRelationship, TCondition, TSorting>
     */
    protected function createToOneRelationship(
        PropertyPathInterface&ResourceTypeInterface $path,
        bool $defaultInclude = false
    ): PropertyBuilder {
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
     * @return PropertyBuilder<TEntity, TRelationship, TCondition, TSorting>
     */
    protected function createToManyRelationship(
        PropertyPathInterface&ResourceTypeInterface $path,
        bool $defaultInclude = false
    ): PropertyBuilder {
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
     * @return array<non-empty-string, PropertyBuilder<TEntity, mixed, TCondition, TSorting>>
     */
    protected function getInitializedProperties(): array
    {
        $properties = $this->getProperties();

        return $this->propertiesToArray($this->processProperties($properties));
    }

    /**
     * Even if a relationship property was defined in this type, we do not allow its usage if the
     * target type of the relationship is not set as exposed.
     *
     * @template TType of object
     *
     * @param array<non-empty-string, PropertyLink<TType>> $types
     *
     * @return array<non-empty-string, PropertyLink<TType>>
     */
    protected function keepExposedTypes(array $types): array
    {
        return array_filter(
            $types,
            static function (PropertyLink $property): bool {
                $type = $property->getTargetType();
                return null === $type
                    || ($type instanceof ExposableRelationshipTypeInterface
                    && $type->isExposedAsRelationship());
            }
        );
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
     * @param list<PropertyBuilder<TEntity, mixed, TCondition, TSorting>> $propertyList
     *
     * @return array<non-empty-string, PropertyBuilder<TEntity, mixed, TCondition, TSorting>>
     */
    protected function propertiesToArray(array $propertyList): array
    {
        $propertyArray = [];
        foreach ($propertyList as $property) {
            $propertyArray[$property->getName()] = $property;
        }

        return $propertyArray;
    }

    /**
     * @param ReflectionClass<object> $class
     */
    protected function getConstructor(ReflectionClass $class): ?ReflectionMethod
    {
        $constructor = $class->getConstructor();
        if (null === $constructor) {
            $parent = $class->getParentClass();
            if (false === $parent) {
                return null;
            }
            return $this->getConstructor($parent);
        }

        return $constructor;
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

        return $this->getEntityFetcher()->getEntitiesByIdentifiers($identifiers, $conditions, $sortMethods, $this->getIdentifierPropertyPath());
    }

    public function getEntityForRelationship(string $identifier, array $conditions): object
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());

        return $this->getEntityFetcher()->getEntityByIdentifier($identifier, $conditions, $this->getIdentifierPropertyPath());
    }

    public function getEntityByIdentifier(string $identifier, array $conditions): object
    {
        $this->mapPaths($conditions, []);
        $conditions = array_merge($conditions, $this->getAccessConditions());

        return $this->getEntityFetcher()->getEntityByIdentifier($identifier, $conditions, $this->getIdentifierPropertyPath());
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

        return $this->getEntityFetcher()->getEntities($conditions, $sortMethods);
    }

    public function getEntitiesForPage(array $conditions, array $sortMethods, PagePagination $pagination): Pagerfanta
    {
        $this->mapPaths($conditions, $sortMethods);
        $conditions = array_merge($conditions, $this->getAccessConditions());
        $sortMethods = array_merge($sortMethods, $this->getDefaultSortMethods());

        return $this->getEntityFetcher()->getEntitiesForPage($conditions, $sortMethods, $pagination);
    }

    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());
        $sortMethods = array_merge($sortMethods, $this->getDefaultSortMethods());

        return $this->getEntityFetcher()->reindexEntities($entities, $conditions, $sortMethods);
    }

    public function assertMatchingEntities(array $entities, array $conditions): void
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());

        $this->getEntityFetcher()->assertMatchingEntities($entities, $conditions);
    }

    public function assertMatchingEntity(object $entity, array $conditions): void
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());

        $this->getEntityFetcher()->assertMatchingEntity($entity, $conditions);
    }

    public function isMatchingEntity(object $entity, array $conditions): bool
    {
        $conditions = array_merge($conditions, $this->getAccessConditions());

        if ([] === $conditions) {
            return true;
        }

        return $this->getEntityFetcher()->isMatchingEntity($entity, $conditions);
    }
}
