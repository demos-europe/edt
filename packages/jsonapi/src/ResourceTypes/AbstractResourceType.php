<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\Properties\AbstractConfig;
use EDT\JsonApi\Properties\AttributeConfig;
use EDT\JsonApi\Properties\ConfigCollection;
use EDT\JsonApi\Properties\ToManyRelationshipConfig;
use EDT\JsonApi\Properties\ToOneRelationshipConfig;
use EDT\JsonApi\Properties\TypedPathConfigCollection;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Wrapping\Contracts\Types\CreatableTypeInterface;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\AbstractRelationshipReadability;
use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\AttributeUpdatability;
use EDT\Wrapping\Properties\Initializability;
use EDT\Wrapping\Properties\ToManyRelationshipUpdatability;
use EDT\Wrapping\Properties\ToOneRelationshipUpdatability;
use EDT\Wrapping\Properties\ToManyRelationshipReadability;
use EDT\Wrapping\Properties\ToOneRelationshipReadability;
use InvalidArgumentException;

/**
 * @template TCondition of FunctionInterface<bool>
 * @template TSorting of SortMethodInterface
 * @template TEntity of object
 *
 * @template-implements ResourceTypeInterface<TCondition, TSorting, TEntity>
 */
abstract class AbstractResourceType implements ResourceTypeInterface
{
    public function getReadableProperties(): array
    {
        $configCollection = $this->getInitializedConfiguration();

        // phpstan will raise an error for this return because the template parameter
        // `TransferableTypeInterface` should get returned, but actually returned will be
        // `ResourceTypeInterface`.
        return [
            array_filter(
                array_map(
                    static fn (AttributeConfig $config): ?AttributeReadability => $config->getReadability(),
                    $configCollection->getAttributes()
                ),
                static fn (?AttributeReadability $readability): bool => null !== $readability
            ),
            array_filter(
                array_map(
                    static fn (ToOneRelationshipConfig $config): ?ToOneRelationshipReadability => $config->getReadability(),
                    $configCollection->getToOneRelationships()
                ),
                fn (?ToOneRelationshipReadability $readability): bool => null !== $readability && $this->isExposedReadability($readability)
            ),
            array_filter(
                array_map(
                    static fn (ToManyRelationshipConfig $config): ?ToManyRelationshipReadability => $config->getReadability(),
                    $configCollection->getToManyRelationships()
                ),
                fn (?ToManyRelationshipReadability $readability): bool => null !== $readability && $this->isExposedReadability($readability)
            ),
        ];
    }

    public function getFilterableProperties(): array
    {
        return $this->toRelationshipTypes(
            fn (AbstractConfig $config): bool => $config->isFilteringEnabled()
        );
    }

    public function getSortableProperties(): array
    {
        return $this->toRelationshipTypes(
            fn (AbstractConfig $config): bool => $config->isSortingEnabled()
        );
    }

    public function getUpdatableProperties(): array
    {
        $configCollection = $this->getInitializedConfiguration();

        return [
            array_filter(
                array_map(
                    static fn (AttributeConfig $config): ?AttributeUpdatability => $config->getUpdatability(),
                    $configCollection->getAttributes()
                ),
                static fn (?AttributeUpdatability $updatability): bool => null !== $updatability
            ),
            array_filter(
                array_map(
                    static fn (ToOneRelationshipConfig $config): ?ToOneRelationshipUpdatability => $config->getUpdatability(),
                    $configCollection->getToOneRelationships()
                ),
                static fn (?ToOneRelationshipUpdatability $updatability): bool => null !== $updatability
            ),
            array_filter(
                array_map(
                    static fn (ToManyRelationshipConfig $config): ?ToManyRelationshipUpdatability => $config->getUpdatability(),
                    $configCollection->getToManyRelationships()
                ),
                static fn (?ToManyRelationshipUpdatability $updatability): bool => null !== $updatability
            ),
        ];
    }

    /**
     * @return array<non-empty-string, Initializability<TCondition>>
     *
     * @see CreatableTypeInterface::getInitializableProperties()
     */
    public function getInitializableProperties(): array
    {
        $configCollection = $this->getInitializedConfiguration();
        $configs = array_merge(
            $configCollection->getAttributes(),
            $configCollection->getToOneRelationships(),
            $configCollection->getToManyRelationships()
        );

        return array_filter(
            array_map(
                static fn (AbstractConfig $config): ?Initializability => $config->getInitializability(),
                $configs
            ),
            static fn (?Initializability $initializability): bool => null !== $initializability
        );
    }

    public function getAliases(): array
    {
        $configCollection = $this->getInitializedConfiguration();
        $configs = array_merge(
            $configCollection->getAttributes(),
            $configCollection->getToOneRelationships(),
            $configCollection->getToManyRelationships()
        );

        return array_filter(
            array_map(
                static fn (AbstractConfig $config): ?array => $config->getAliasedPath(),
                $configs
            ),
            static fn (?array $path): bool => null !== $path
        );
    }

    /**
     * Array order: Even though the order of the properties returned within the array may have an
     * effect (e.g. determining the order of properties in JSON:API responses) you can not rely on
     * these effects; they may be changed in the future.
     *
     * @param TypedPathConfigCollection<TCondition, TSorting, TEntity> $configCollection
     *
     * @return list<PropertyBuilder>
     *
     * @deprecated implement {@link self::configureProperties()} instead
     */
    protected function getProperties(TypedPathConfigCollection $configCollection): array
    {
        return [];
    }

    /**
     * @param TypedPathConfigCollection<TCondition, TSorting, TEntity> $configCollection
     */
    abstract protected function configureProperties(TypedPathConfigCollection $configCollection): void;

    /**
     * @param ConfigCollection<TCondition, TSorting, TEntity> $configCollection
     */
    protected function processProperties(ConfigCollection $configCollection): void
    {
        // do nothing by default
    }

    /**
     * @return PropertyBuilder<TEntity, mixed>
     *
     * @deprecated use {@link TypedPathConfigCollection::configureAttribute()} instead
     */
    protected function createAttribute(PropertyPathInterface $path): PropertyBuilder
    {
        return new PropertyBuilder($path, $this->getEntityClass());
    }

    /**
     * @template TRelationship of object
     *
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface $path
     *
     * @return PropertyBuilder<TEntity, TRelationship>
     *
     * @deprecated use {@link TypedPathConfigCollection::configureToOneRelationship()} instead
     */
    protected function createToOneRelationship(
        PropertyPathInterface&ResourceTypeInterface $path,
        bool $defaultInclude = false
    ): PropertyBuilder {
        return new PropertyBuilder($path, $this->getEntityClass(), [
            'relationshipType' => $path,
            'defaultInclude' => $defaultInclude,
            'toMany' => false,
        ]);
    }

    /**
     * @template TRelationship of object
     *
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface $path
     *
     * @return PropertyBuilder<TEntity, TRelationship>
     *
     * @deprecated use {@link TypedPathConfigCollection::configureToManyRelationship()} instead
     */
    protected function createToManyRelationship(
        PropertyPathInterface&ResourceTypeInterface $path,
        bool $defaultInclude = false
    ): PropertyBuilder {
        return new PropertyBuilder($path, $this->getEntityClass(), [
            'relationshipType' => $path,
            'defaultInclude' => $defaultInclude,
            'toMany' => true,
        ]);
    }

    /**
     * @return ConfigCollection<TCondition, TSorting, TEntity>
     */
    protected function getInitializedConfiguration(): ConfigCollection
    {
        $baseConfigCollection = new ConfigCollection($this);
        $configCollection = new TypedPathConfigCollection($baseConfigCollection);
        $this->configureProperties($configCollection);

        $properties = $this->getProperties($configCollection);
        foreach ($properties as $property) {
            $property->addToConfigCollection($baseConfigCollection);
        }

        $this->processProperties($baseConfigCollection);

        $configs = array_merge(
            $baseConfigCollection->getAttributes(),
            $baseConfigCollection->getToOneRelationships(),
            $baseConfigCollection->getToManyRelationships()
        );
        foreach ($configs as $propertyName => $config) {
            $this->validateConsistency($config, $propertyName);
        }

        return $baseConfigCollection;
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
     * @param AttributeConfig|ToOneRelationshipConfig|ToManyRelationshipConfig $config
     * @param non-empty-string $propertyName
     *
     * @throws InvalidArgumentException
     */
    protected function validateConsistency(AbstractConfig $config, string $propertyName): void
    {
        $readability = $config->getReadability();
        if (null === $readability
            || $readability->isAllowingInconsistencies()
            || null === $readability->getCustomValueFunction()
        ) {
            return;
        }

        $problems = [];
        if ($config->isFilteringEnabled()) {
            $problems[] = 'filterable';
        }

        if ($config->isSortingEnabled()) {
            $problems[] = 'sortable';
        }

        if (null !== $config->getAliasedPath()) {
            $problems[] = 'being an alias';
        }

        if ([] !== $problems) {
            $problems = implode(' and ', $problems);

            throw new InvalidArgumentException("The property '$propertyName' is set as $problems while having a custom read function set. This will likely result in inconsistencies and is not allowed by default.");
        }
    }

    /**
     * @param callable(AbstractConfig): bool $filter
     *
     * @return array<non-empty-string, ResourceTypeInterface<TCondition, TSorting, object>|null>
     */
    private function toRelationshipTypes(callable $filter): array
    {
        $configCollection = $this->getInitializedConfiguration();

        $attributes = array_map(
            static fn (AttributeConfig $config): ?ResourceTypeInterface => null,
            array_filter($configCollection->getAttributes(), $filter)
        );
        $toOneRelationships = array_map(
            static fn (ToOneRelationshipConfig $config): ResourceTypeInterface => $config->getRelationshipType(),
            array_filter($configCollection->getToOneRelationships(), $filter)
        );
        $toManyRelationships = array_map(
            static fn (ToManyRelationshipConfig $config): ResourceTypeInterface => $config->getRelationshipType(),
            array_filter($configCollection->getToManyRelationships(), $filter)
        );

        $properties = array_merge($attributes, $toOneRelationships, $toManyRelationships);

        return $this->keepExposedTypes($properties);
    }

    protected function isExposedReadability(AbstractRelationshipReadability $readability): bool
    {
        $relationshipType = $readability->getRelationshipType();

        return $relationshipType instanceof ExposableRelationshipTypeInterface
            && $relationshipType->isExposedAsRelationship();
    }
}
