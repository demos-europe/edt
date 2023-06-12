<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use demosplan\DemosPlanCoreBundle\Exception\InvalidArgumentException;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\Types\CreatableTypeInterface;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;
use EDT\Wrapping\Properties\AttributeUpdatabilityInterface;
use EDT\Wrapping\Properties\PropertyInitializabilityInterface;
use EDT\Wrapping\Properties\RelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipUpdatabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipUpdatabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements ResourceTypeInterface<TCondition, TSorting, TEntity>
 */
abstract class AbstractResourceType implements ResourceTypeInterface
{
    /**
     * A restricted view on the properties of the {@link TypeInterface::getEntityClass() backing object}. Potentially
     * mapped via {@link ResourceTypeInterface::getAliases() aliases}.
     */
    public function getReadableProperties(): array
    {
        $configCollection = $this->getInitializedProperties();

        return [
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
        ];
    }

    public function getFilterableProperties(): array
    {
        $propertyArray = $this->getInitializedProperties();
        $propertyArray = array_map(
            static fn (PropertyBuilder $property): ?ResourceTypeInterface => $property->getRelationshipType(),
            array_filter(
                $propertyArray,
                static fn (PropertyBuilder $property): bool => $property->isFilterable()
            )
        );

        return $this->keepExposedTypes($propertyArray);
    }

    public function getSortableProperties(): array
    {
        $propertyArray = array_map(
            static fn (PropertyBuilder $property): ?ResourceTypeInterface => $property->getRelationshipType(),
            array_filter(
                $this->getInitializedProperties(),
                static fn (PropertyBuilder $property): bool => $property->isSortable()
            )
        );

        return $this->keepExposedTypes($propertyArray);
    }

    public function getUpdatableProperties(): array
    {
        $properties = $this->getInitializedProperties();

        return [
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?AttributeUpdatabilityInterface => $property->getAttributeUpdatability(),
                    $properties
                ),
                static fn (?AttributeUpdatabilityInterface $updatability): bool => null !== $updatability
            ),
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?ToOneRelationshipUpdatabilityInterface => $property->getToOneRelationshipUpdatability(),
                    $properties
                ),
                static fn (?ToOneRelationshipUpdatabilityInterface $updatability): bool => null !== $updatability
            ),
            array_filter(
                array_map(
                    static fn (PropertyBuilder $property): ?ToManyRelationshipUpdatabilityInterface => $property->getToManyRelationshipUpdatability(),
                    $properties
                ),
                static fn (?ToManyRelationshipUpdatabilityInterface $updatability): bool => null !== $updatability
            ),
        ];
    }

    /**
     * @return array<non-empty-string, PropertyInitializabilityInterface<TCondition>>
     *
     * @see CreatableTypeInterface::getInitializableProperties()
     */
    public function getInitializableProperties(): array
    {
        return array_filter(
            array_map(
                static fn (PropertyBuilder $property): ?PropertyInitializabilityInterface => $property->getInitializability(),
                $this->getInitializedProperties()
            ),
            static fn (?PropertyInitializabilityInterface $initializability): bool => null !== $initializability
        );
    }

    public function getAliases(): array
    {
        return array_filter(
            array_map(
                static fn (PropertyBuilder $property): ?array => $property->getAliasedPath(),
                $this->getInitializedProperties()
            ),
            static fn (?array $path): bool => null !== $path
        );
    }

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
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface $path
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
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface $path
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
     * @template TType of TypeInterface
     *
     * @param array<non-empty-string, TType|null> $types
     *
     * @return array<non-empty-string, (TType&ExposableRelationshipTypeInterface)|null>
     */
    protected function keepExposedTypes(array $types): array
    {
        return array_filter(
            $types,
            static fn (?TypeInterface $type): bool =>
                null === $type
                || ($type instanceof ExposableRelationshipTypeInterface
                && $type->isExposedAsRelationship())
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
    private function propertiesToArray(array $propertyList): array
    {
        $propertyArray = [];
        foreach ($propertyList as $property) {
            $propertyArray[$property->getName()] = $property;
        }

        return $propertyArray;
    }
}
