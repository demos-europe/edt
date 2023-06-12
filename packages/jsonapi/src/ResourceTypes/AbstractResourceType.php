<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\OutputTransformation\DynamicTransformer;
use EDT\JsonApi\RequestHandling\Body\CreationRequestBody;
use EDT\JsonApi\RequestHandling\Body\UpdateRequestBody;
use EDT\JsonApi\RequestHandling\ExpectedPropertyCollection;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\RequestHandling\SideEffectHandleTrait;
use EDT\JsonApi\Requests\PropertyUpdaterTrait;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Querying\PropertyPaths\PropertyLink;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\ReindexableTypeInterface;
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
use Exception;
use League\Fractal\TransformerAbstract;
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
 */
abstract class AbstractResourceType implements ResourceTypeInterface
{
    use PropertyUpdaterTrait;
    use SideEffectHandleTrait;

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

    public function updateEntity(UpdateRequestBody $requestBody): ?object
    {
        $updatableProperties = $this->getUpdatableProperties();

        // remove irrelevant setability instances
        $attributeSetabilities = array_intersect_key(
            $updatableProperties->getAttributes(),
            $requestBody->getAttributes()
        );
        $toOneRelationshipSetabilities = array_intersect_key(
            $updatableProperties->getToOneRelationships(),
            $requestBody->getToOneRelationships()
        );
        $toManyRelationshipSetabilities = array_intersect_key(
            $updatableProperties->getToManyRelationships(),
            $requestBody->getToManyRelationships()
        );

        $entityConditions = array_merge(...array_map(
            static fn (PropertyAccessibilityInterface $accessibility): array => $accessibility->getEntityConditions(),
            array_merge(
                $attributeSetabilities,
                $toOneRelationshipSetabilities,
                $toManyRelationshipSetabilities
            )
        ));

        $entity = $this->getEntityByIdentifier($requestBody->getId(), $entityConditions);

        $sideEffects = [
            $this->updateAttributes($entity, $attributeSetabilities, $requestBody->getAttributes()),
            $this->updateToOneRelationships($entity, $toOneRelationshipSetabilities, $requestBody->getToOneRelationships()),
            $this->updateToManyRelationships($entity, $toManyRelationshipSetabilities, $requestBody->getToManyRelationships()),
        ];

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

        // remove irrelevant setability instances
        $attributeSetabilities = array_intersect_key(
            $initializableProperties->getNonConstructorAttributeSetabilities(),
            $requestBody->getAttributes()
        );
        $toOneRelationshipSetabilities = array_intersect_key(
            $initializableProperties->getNonConstructorToOneRelationshipSetabilities(),
            $requestBody->getToOneRelationships()
        );
        $toManyRelationshipSetabilities = array_intersect_key(
            $initializableProperties->getNonConstructorToManyRelationshipSetabilities(),
            $requestBody->getToManyRelationships()
        );

        $orderedConstructorArguments = $initializableProperties->getOrderedConstructorArguments();

        $constructorArguments = $this->getConstructorArguments($orderedConstructorArguments, $requestBody);

        $entity = new ($this->getEntityClass())(...$constructorArguments);

        // update entity
        $sideEffects = [
            $this->updateAttributes($entity, $attributeSetabilities, $requestBody->getAttributes()),
            $this->updateToOneRelationships($entity, $toOneRelationshipSetabilities, $requestBody->getToOneRelationships()),
            $this->updateToManyRelationships($entity, $toManyRelationshipSetabilities, $requestBody->getToManyRelationships()),
        ];

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
    public function getInitializableProperties(): InitializabilityCollection
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

    abstract protected function getMessageFormatter(): MessageFormatter;

    abstract protected function getLogger(): LoggerInterface;

    /**
     * Array order: Even though the order of the properties returned within the array may have an
     * effect (e.g. determining the order of properties in JSON:API responses) you can not rely on
     * these effects; they may be changed in the future.
     *
     * @return list<PropertyBuilder<TEntity, mixed, TCondition, TSorting>>
     */
    abstract protected function getProperties(): array;

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
     * @return ReindexableTypeInterface<TCondition, TSorting, TEntity>
     */
    abstract protected function getReindexableType(): ReindexableTypeInterface;

    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array
    {
        return $this->getReindexableType()->reindexEntities($entities, $conditions, $sortMethods);
    }

    public function assertMatchingEntities(array $entities, array $conditions): void
    {
        $this->getReindexableType()->assertMatchingEntities($entities, $conditions);
    }

    public function assertMatchingEntity(object $entity, array $conditions): void
    {
        $this->getReindexableType()->assertMatchingEntity($entity, $conditions);
    }

    public function isMatchingEntity(object $entity, array $conditions): bool
    {
        return $this->getReindexableType()->isMatchingEntity($entity, $conditions);
    }
}
