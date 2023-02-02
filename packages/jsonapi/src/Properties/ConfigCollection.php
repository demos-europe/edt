<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use function array_key_exists;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class ConfigCollection
{
    /**
     * @var array<non-empty-string, AttributeConfig<TCondition, TEntity>>
     */
    protected array $attributes = [];

    /**
     * @var array<non-empty-string, ToOneRelationshipConfig<TCondition, TSorting, TEntity, object>>
     */
    protected array $toOneRelationships = [];

    /**
     * @var array<non-empty-string, ToManyRelationshipConfig<TCondition, TSorting, TEntity, object>>
     */
    protected array $toManyRelationships = [];

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TEntity> $type
     */
    public function __construct(
        protected ResourceTypeInterface $type
    ) {}

    /**
     * Remove a configured property from this collection, if it exists as attribute or to-one/to-many relationship.
     *
     * @param non-empty-string $propertyName
     *
     * @return bool `true` if the call actually removed an existing property configuration, `false` otherwise
     */
    public function removeProperty(string $propertyName): bool
    {
        $removed = false;
        if (array_key_exists($propertyName, $this->attributes)) {
            $removed = true;
            unset($this->attributes[$propertyName]);
        }

        if (array_key_exists($propertyName, $this->toOneRelationships)) {
            $removed = true;
            unset($this->toOneRelationships[$propertyName]);
        }

        if (array_key_exists($propertyName, $this->toManyRelationships)) {
            $removed = true;
            unset($this->toManyRelationships[$propertyName]);
        }

        return $removed;
    }

    /**
     * @param non-empty-string $propertyName
     */
    public function hasProperty(string $propertyName): bool
    {
        return $this->hasAttribute($propertyName)
            || $this->hasToOneRelationship($propertyName)
            || $this->hasToManyRelationship($propertyName);
    }

    /**
     * @param non-empty-string $propertyName
     */
    public function hasAttribute(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->attributes);
    }

    /**
     * @param non-empty-string $propertyName
     */
    public function hasToOneRelationship(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->toOneRelationships);
    }

    /**
     * @param non-empty-string $propertyName
     */
    public function hasToManyRelationship(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->toManyRelationships);
    }

    /**
     * @return array<non-empty-string, AttributeConfig<TCondition, TEntity>>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<non-empty-string, ToOneRelationshipConfig<TCondition, TSorting, TEntity, object>>
     */
    public function getToOneRelationships(): array
    {
        return $this->toOneRelationships;
    }

    /**
     * @return array<non-empty-string, ToManyRelationshipConfig<TCondition, TSorting, TEntity, object>>
     */
    public function getToManyRelationships(): array
    {
        return $this->toManyRelationships;
    }

    /**
     * @return ResourceTypeInterface<TCondition, TSorting, TEntity>
     */
    public function getType(): ResourceTypeInterface
    {
        return $this->type;
    }

    /**
     * @param non-empty-string|PropertyPathInterface $propertyPath
     *
     * @return AttributeConfig<TCondition, TEntity>
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function configureAttribute(string|PropertyPathInterface $propertyPath): AttributeConfig
    {
        [$propertyName, $propertyPath] = $this->getNameAndPath($propertyPath);

        if (array_key_exists($propertyName, $this->toOneRelationships)) {
            throw ResourcePropertyConfigException::attributeAlreadyToOneRelationship($propertyName);
        }

        if (array_key_exists($propertyName, $this->toManyRelationships)) {
            throw ResourcePropertyConfigException::attributeAlreadyToManyRelationship($propertyName);
        }

        if (array_key_exists($propertyName, $this->attributes)) {
            $property = $this->attributes[$propertyName];
        } else {
            $property = new AttributeConfig($this->type);
            $this->attributes[$propertyName] = $property;
        }

        $this->maybeSetAlias($propertyPath, $property);

        return $property;
    }

    /**
     * Configure a to-one relationship.
     *
     * If a to-one relationship config already exists for the given property name, then that
     * config will be returned. Otherwise, a new to-one relationship config will be created and
     * returned.
     *
     * @template TRelationship of object
     * @template TRelationshipType of ResourceTypeInterface<TCondition, TSorting, TRelationship>
     *
     * @param non-empty-string|PropertyPathInterface $propertyPath
     * @param TRelationshipType $relationshipType
     *
     * @return ToOneRelationshipConfig<TCondition, TSorting, TEntity, TRelationship>
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function configureToOneRelationship(
        string|PropertyPathInterface $propertyPath,
        ResourceTypeInterface $relationshipType
    ): ToOneRelationshipConfig {
        [$propertyName, $propertyPath] = $this->getNameAndPath($propertyPath);

        if (array_key_exists($propertyName, $this->attributes)) {
            throw ResourcePropertyConfigException::toOneRelationshipAlreadyAttribute($propertyName);
        }

        if (array_key_exists($propertyName, $this->toManyRelationships)) {
            throw ResourcePropertyConfigException::toOneRelationshipAlreadyToManyRelationship($propertyName);
        }

        if (array_key_exists($propertyName, $this->toOneRelationships)) {
            $property = $this->toOneRelationships[$propertyName];
            $this->assertSameRelationshipType($propertyName, $property, $relationshipType);
        } else {
            $property = new ToOneRelationshipConfig($this->type, $relationshipType);
            $this->toOneRelationships[$propertyName] = $property;
        }

        $this->maybeSetAlias($propertyPath, $property);

        return $property;
    }

    /**
     * Configure a to-many relationship.
     *
     * If a to-many relationship config already exists for the given property name, then that
     * config will be returned. Otherwise, a new to-many relationship config will be created and
     * returned.
     *
     * @template TRelationship of object
     * @template TRelationshipType of ResourceTypeInterface<TCondition, TSorting, TRelationship>
     *
     * @param non-empty-string|PropertyPathInterface $propertyPath
     * @param TRelationshipType $relationshipType
     *
     * @return ToManyRelationshipConfig<TCondition, TSorting, TEntity, TRelationship>
     *
     * @throws ResourcePropertyConfigException if the property name is already used for an attribute
     *                                         config or to-one relationship config
     * @throws PathException
     */
    public function configureToManyRelationship(
        string|PropertyPathInterface $propertyPath,
        ResourceTypeInterface $relationshipType
    ): ToManyRelationshipConfig {
        [$propertyName, $propertyPath] = $this->getNameAndPath($propertyPath);

        if (array_key_exists($propertyName, $this->attributes)) {
            throw ResourcePropertyConfigException::toManyRelationshipAlreadyAttribute($propertyName);
        }

        if (array_key_exists($propertyName, $this->toOneRelationships)) {
            throw ResourcePropertyConfigException::toManyRelationshipAlreadyToOneRelationship($propertyName);
        }

        if (array_key_exists($propertyName, $this->toManyRelationships)) {
            $property = $this->toManyRelationships[$propertyName];
            $this->assertSameRelationshipType($propertyName, $property, $relationshipType);
        } else {
            $property = new ToManyRelationshipConfig($this->type, $relationshipType);
            $this->toManyRelationships[$propertyName] = $property;
        }

        $this->maybeSetAlias($propertyPath, $property);

        return $property;
    }

    /**
     * @param non-empty-list<non-empty-string> $propertyPath
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    protected function maybeSetAlias(?array $propertyPath, AbstractConfig $property): void
    {
        if (null !== $propertyPath && 1 < count($propertyPath)) {
            $property->enableAliasing($propertyPath);
        }
    }

    /**
     * Assert that the relationship type of the given config is the same as the relationship type given.
     *
     * A first call to {@link self::configureToOneRelationship()} or {@link self::configureToManyRelationship()}
     * will create the config instance and a custom read function may be set, corresponding to the
     * target-relationship type used in the calls. A second call to the methods may use the same property
     * name but a different target-relationship type, which may make the custom read return type invalid
     * without being detected.
     *
     * By checking if the new target-relationship type is the same as the old one and otherwise throwing
     * an exception, we verify the correctness at runtime. The same is done for the entity class
     * of the relationship types.
     *
     * @param non-empty-string                                 $propertyName
     * @param ToOneRelationshipConfig|ToManyRelationshipConfig $propertyConfig
     * @param ResourceTypeInterface                            $newRelationshipType
     *
     * @throws ResourcePropertyConfigException if the property name is already used for a relationship config and the relationship type differs
     */
    protected function assertSameRelationshipType(
        string $propertyName,
        AbstractConfig $propertyConfig,
        ResourceTypeInterface $newRelationshipType
    ): void {
        $currentRelationshipType = $propertyConfig->getRelationshipType();
        if ($currentRelationshipType !== $newRelationshipType) {
            throw ResourcePropertyConfigException::relationshipType(
                $propertyName,
                $currentRelationshipType->getIdentifier(),
                $newRelationshipType->getIdentifier()
            );
        }
    }

    /**
     * Splits the given path into the actual property name (the last path segment) and its full path.
     *
     * @param non-empty-string|PropertyPathInterface $propertyPath
     *
     * @return array{0: non-empty-string, 1: non-empty-list<non-empty-string>}
     *
     * @throws PathException
     */
    protected function getNameAndPath(string|PropertyPathInterface $propertyPath): array
    {
        if ($propertyPath instanceof PropertyPathInterface) {
            $propertyPath = $propertyPath->getAsNames();

            return [$propertyPath[array_key_last($propertyPath)], $propertyPath];
        }

        return [$propertyPath, [$propertyPath]];
    }
}
