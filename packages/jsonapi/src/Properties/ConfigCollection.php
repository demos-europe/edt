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
     * @var array<non-empty-string, JsonAttributeConfig<TCondition, TEntity>>
     */
    private array $attributes = [];

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
        protected readonly ResourceTypeInterface $type
    ) {}

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
     * @return array<non-empty-string, JsonAttributeConfig<TCondition, TEntity>>
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
     * @return JsonAttributeConfig<TCondition, TEntity>
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function configureJsonAttribute(string|PropertyPathInterface $propertyPath): AttributeConfig
    {
        [$propertyName, $propertyPath] = $this->getNameAndPath($propertyPath);

        $this->assertPropertyIsNotConfigured($propertyName, $this->attributes);

        if (array_key_exists($propertyName, $this->attributes)) {
            $property = $this->attributes[$propertyName];
        } else {
            $property = new JsonAttributeConfig($this->type);
            $this->attributes[$propertyName] = $property;
        }

        if ($this->isAliasToSet($propertyPath)) {
            $property->enableAliasing($propertyPath);
        }

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

        $this->assertPropertyIsNotConfigured($propertyName, $this->toOneRelationships);

        if (array_key_exists($propertyName, $this->toOneRelationships)) {
            $property = $this->toOneRelationships[$propertyName];
            $this->assertSameRelationshipType($propertyName, $property, $relationshipType);
        } else {
            $property = new ToOneRelationshipConfig($this->type, $relationshipType);
            $this->toOneRelationships[$propertyName] = $property;
        }

        if ($this->isAliasToSet($propertyPath)) {
            $property->enableAliasing($propertyPath);
        }

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

        $this->assertPropertyIsNotConfigured($propertyName, $this->toManyRelationships);

        if (array_key_exists($propertyName, $this->toManyRelationships)) {
            $property = $this->toManyRelationships[$propertyName];
            $this->assertSameRelationshipType($propertyName, $property, $relationshipType);
        } else {
            $property = new ToManyRelationshipConfig($this->type, $relationshipType);
            $this->toManyRelationships[$propertyName] = $property;
        }

        if ($this->isAliasToSet($propertyPath)) {
            $property->enableAliasing($propertyPath);
        }

        return $property;
    }

    /**
     * @param non-empty-list<non-empty-string> $propertyPath
     */
    protected function isAliasToSet(?array $propertyPath): bool
    {
        return null !== $propertyPath && 1 < count($propertyPath);
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
     * @param non-empty-string $propertyName
     * @param ToOneRelationshipConfig<PathsBasedInterface, PathsBasedInterface, object, object>|ToManyRelationshipConfig<PathsBasedInterface, PathsBasedInterface, object, object> $propertyConfig
     * @param ResourceTypeInterface $newRelationshipType
     *
     * @throws ResourcePropertyConfigException if the property name is already used for a relationship config and the relationship type differs
     */
    protected function assertSameRelationshipType(
        string $propertyName,
        ToOneRelationshipConfig|ToManyRelationshipConfig $propertyConfig,
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
     * @param non-empty-string $propertyName
     * @param array<non-empty-string, mixed> $exemption
     *
     * @throws ResourcePropertyConfigException
     */
    protected function assertPropertyIsNotConfigured(string $propertyName, array $exemption): void
    {
        if ($exemption !== $this->attributes && array_key_exists($propertyName, $this->attributes)) {
            throw ResourcePropertyConfigException::propertyAlreadyDefinedAsAttribute($propertyName);
        }

        if ($exemption !== $this->toOneRelationships && array_key_exists($propertyName, $this->toOneRelationships)) {
            throw ResourcePropertyConfigException::propertyAlreadyDefinedAsOneRelationship($propertyName);
        }

        if ($exemption !== $this->toManyRelationships && array_key_exists($propertyName, $this->toManyRelationships)) {
            throw ResourcePropertyConfigException::propertyAlreadyDefinedAsToMany($propertyName);
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
