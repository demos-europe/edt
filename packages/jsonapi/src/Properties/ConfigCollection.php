<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathInterface;
use function array_key_exists;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TEntity of object
 */
class ConfigCollection
{
    /**
     * @var array<non-empty-string, AttributeConfig<TCondition, TEntity>>
     */
    protected array $attributes = [];

    /**
     * @var array<non-empty-string, ToOneRelationshipConfig<TCondition, TSorting, TEntity, object, ResourceTypeInterface<TCondition, TSorting, object>>>
     */
    protected array $toOneRelationships = [];

    /**
     * @var array<non-empty-string, ToManyRelationshipConfig<TCondition, TSorting, TEntity, object, ResourceTypeInterface<TCondition, TSorting, object>>>
     */
    protected array $toManyRelationships = [];

    /**
     * @var ResourceTypeInterface<TCondition, TSorting, TEntity>
     */
    protected ResourceTypeInterface $type;

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TEntity> $type
     */
    public function __construct(ResourceTypeInterface $type)
    {
        $this->type = $type;
    }

    /**
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
     * @return array<non-empty-string, ToOneRelationshipConfig<TCondition, TSorting, TEntity, object, ResourceTypeInterface<TCondition, TSorting, object>>>
     */
    public function getToOneRelationships(): array
    {
        return $this->toOneRelationships;
    }

    /**
     * @return array<non-empty-string, ToManyRelationshipConfig<TCondition, TSorting, TEntity, object, ResourceTypeInterface<TCondition, TSorting, object>>>
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
     * @param non-empty-string $propertyName
     *
     * @throws ResourcePropertyConfigException
     */
    protected function checkAttributeName(string $propertyName, bool $replace): void
    {
        if ($replace) {
            $this->removeProperty($propertyName);
        } elseif (array_key_exists($propertyName, $this->toOneRelationships)) {
            throw ResourcePropertyConfigException::attributeAlreadyToOneRelationship($propertyName);
        } elseif (array_key_exists($propertyName, $this->toManyRelationships)) {
            throw ResourcePropertyConfigException::attributeAlreadyToManyRelationship($propertyName);
        }
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @throws ResourcePropertyConfigException
     */
    protected function checkToOneRelationshipName(string $propertyName, bool $replace): void
    {
        if ($replace) {
            $this->removeProperty($propertyName);
        } elseif (array_key_exists($propertyName, $this->attributes)) {
            throw ResourcePropertyConfigException::toOneRelationshipAlreadyAttribute($propertyName);
        } elseif (array_key_exists($propertyName, $this->toManyRelationships)) {
            throw ResourcePropertyConfigException::toOneRelationshipAlreadyToManyRelationship($propertyName);
        }
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @throws ResourcePropertyConfigException
     */
    protected function checkToManyRelationshipName(string $propertyName, bool $replace): void
    {
        if ($replace) {
            $this->removeProperty($propertyName);
        } elseif (array_key_exists($propertyName, $this->attributes)) {
            throw ResourcePropertyConfigException::toManyRelationshipAlreadyAttribute($propertyName);
        } elseif (array_key_exists($propertyName, $this->toOneRelationships)) {
            throw ResourcePropertyConfigException::toManyRelationshipAlreadyToOneRelationship($propertyName);
        }
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @return AttributeConfig<TCondition, TEntity>
     */
    protected function getOrCreateAttribute(string $propertyName): AttributeConfig
    {
        if (array_key_exists($propertyName, $this->attributes)) {
            $property = $this->attributes[$propertyName];
        } else {
            $property = new AttributeConfig($this->type);
            $this->attributes[$propertyName] = $property;
        }

        return $property;
    }

    /**
     * @template TRelationship of object
     * @template TRelationshipType of \EDT\JsonApi\ResourceTypes\ResourceTypeInterface<TCondition, TSorting, TRelationship>
     *
     * @param non-empty-string  $propertyName
     * @param TRelationshipType $relationshipType
     *
     * @return ToOneRelationshipConfig<TCondition, TSorting, TEntity, TRelationship, TRelationshipType>
     *
     * @throws ResourcePropertyConfigException
     */
    protected function getOrCreateToOneRelationship(string $propertyName, ResourceTypeInterface $relationshipType): ToOneRelationshipConfig
    {
        if (array_key_exists($propertyName, $this->toOneRelationships)) {
            $property = $this->toOneRelationships[$propertyName];
            $this->getWithValidatedRelationshipType($propertyName, $property, $relationshipType);
        } else {
            $property = new ToOneRelationshipConfig($this->type, $relationshipType);
            $this->toOneRelationships[$propertyName] = $property;
        }

        return $property;
    }

    /**
     * @template TRelationship of object
     * @template TRelationshipType of \EDT\JsonApi\ResourceTypes\ResourceTypeInterface<TCondition, TSorting, TRelationship>
     *
     * @param non-empty-string  $propertyName
     * @param TRelationshipType $relationshipType
     *
     * @return ToManyRelationshipConfig<TCondition, TSorting, TEntity, TRelationship, TRelationshipType>
     *
     * @throws ResourcePropertyConfigException
     */
    protected function getOrCreateToManyRelationship(string $propertyName, ResourceTypeInterface $relationshipType): ToManyRelationshipConfig
    {
        if (array_key_exists($propertyName, $this->toManyRelationships)) {
            $property = $this->toManyRelationships[$propertyName];
            $this->getWithValidatedRelationshipType($propertyName, $property, $relationshipType);
        } else {
            $property = new ToManyRelationshipConfig($this->type, $relationshipType);
            $this->toManyRelationships[$propertyName] = $property;
        }

        return $property;
    }

    /**
     * This method tries to work around the type-hint problems of the calling methods.
     *
     * A first call to {@link getOrCreateToOneRelationship} or {@link getOrCreateToManyRelationship}
     * may create the instance and set a custom read function via the return. A second call to the
     * methods may use the same `$propertyName` but a different `$relationshipType`, which may make
     * the custom read return type invalid without being detected.
     *
     * By checking if the new relationship type is the same as the old one and otherwise throwing
     * an exception, we get at least at run-time some kind of validation, though there may be
     * problems with the template parameter types of the relationship type too.
     *
     * @param non-empty-string                                 $propertyName
     * @param ToOneRelationshipConfig|ToManyRelationshipConfig $propertyConfig
     * @param ResourceTypeInterface                            $newRelationshipType
     *
     * @throws ResourcePropertyConfigException
     */
    protected function getWithValidatedRelationshipType(
        string $propertyName,
        AbstractConfig $propertyConfig,
        ResourceTypeInterface $newRelationshipType
    ): void {
        $currentRelationshipType = $propertyConfig->getRelationshipType();
        if ($currentRelationshipType !== $newRelationshipType) {
            throw ResourcePropertyConfigException::relationshipType($propertyName, $currentRelationshipType->getIdentifier(), $newRelationshipType->getIdentifier());
        }
    }

    /**
     * @param non-empty-list<non-empty-string> $propertyPath
     *
     * @return non-empty-string
     */
    protected function getLastName(array $propertyPath): string
    {
        return array_pop($propertyPath);
    }

    /**
     * @param non-empty-list<non-empty-string> $propertyPath
     */
    protected function maybeSetAlias(?array $propertyPath, AbstractConfig $property): void
    {
        if (null !== $propertyPath && 1 < count($propertyPath)) {
            $property->enableAliasing($propertyPath);
        }
    }

    /**
     * @param non-empty-string|PropertyPathInterface $propertyName
     *
     * @return AttributeConfig<TCondition, TEntity>
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function configureAttribute($propertyName, bool $replace = false): AttributeConfig
    {
        if ($propertyName instanceof PropertyPathInterface) {
            $propertyPath = $propertyName->getAsNames();
            $propertyName = $this->getLastName($propertyPath);
        } else {
            $propertyPath = null;
        }

        $this->checkAttributeName($propertyName, $replace);
        $property = $this->getOrCreateAttribute($propertyName);
        $this->maybeSetAlias($propertyPath, $property);

        return $property;
    }

    /**
     * @template TRelationship of object
     * @template TRelationshipType of \EDT\JsonApi\ResourceTypes\ResourceTypeInterface<TCondition, TSorting, TRelationship>
     *
     * @param non-empty-string|PropertyPathInterface $propertyPath
     * @param TRelationshipType $relationshipType
     *
     * @return ToOneRelationshipConfig<TCondition, TSorting, TEntity, TRelationship, TRelationshipType>
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function configureToOneRelationship($propertyPath, ResourceTypeInterface $relationshipType, bool $replace = false): ToOneRelationshipConfig
    {
        if ($propertyPath instanceof PropertyPathInterface) {
            $propertyPathArray = $propertyPath->getAsNames();
            $propertyPath = $this->getLastName($propertyPathArray);
        } else {
            $propertyPathArray = null;
        }

        $this->checkToOneRelationshipName($propertyPath, $replace);
        $property = $this->getOrCreateToOneRelationship($propertyPath, $relationshipType);
        $this->maybeSetAlias($propertyPathArray, $property);

        return $property;
    }

    /**
     * @template TRelationship of object
     * @template TRelationshipType of \EDT\JsonApi\ResourceTypes\ResourceTypeInterface<TCondition, TSorting, TRelationship>
     *
     * @param non-empty-string|PropertyPathInterface $propertyName
     * @param TRelationshipType $relationshipType
     *
     * @return ToManyRelationshipConfig<TCondition, TSorting, TEntity, TRelationship, TRelationshipType>
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function configureToManyRelationship($propertyName, ResourceTypeInterface $relationshipType, bool $replace = false): ToManyRelationshipConfig
    {
        if ($propertyName instanceof PropertyPathInterface) {
            $propertyPath = $propertyName->getAsNames();
            $propertyName = $this->getLastName($propertyPath);
        } else {
            $propertyPath = null;
        }

        $this->checkToManyRelationshipName($propertyName, $replace);
        $property = $this->getOrCreateToManyRelationship($propertyName, $relationshipType);
        $this->maybeSetAlias($propertyPath, $property);

        return $property;
    }
}
