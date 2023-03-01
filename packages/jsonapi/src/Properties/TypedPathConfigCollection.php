<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\PathBuilding\PropertyAutoPathInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class TypedPathConfigCollection
{
    /**
     * @param ConfigCollection<TCondition, TSorting, TEntity> $configCollection
     */
    public function __construct(
        protected ConfigCollection $configCollection
    ) {}

    /**
     * @return JsonAttributeConfig<TCondition, TEntity>
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function configureJsonAttribute(PropertyPathInterface $propertyPath): AttributeConfig
    {
        $this->validatePathStart($propertyPath);
        return $this->configCollection->configureJsonAttribute($propertyPath);
    }

    /**
     * @template TRelationship of object
     * @template TRelationshipType of ResourceTypeInterface<TCondition, TSorting, TRelationship>
     *
     * @param PropertyPathInterface&TRelationshipType $relationship
     *
     * @return ToOneRelationshipConfig<TCondition, TSorting, TEntity, TRelationship>
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function configureToOneRelationship(PropertyPathInterface&ResourceTypeInterface $relationship): ToOneRelationshipConfig
    {
        $this->validatePathStart($relationship);
        return $this->configCollection->configureToOneRelationship($relationship, $relationship);
    }

    /**
     * @template TRelationship of object
     * @template TRelationshipType of ResourceTypeInterface<TCondition, TSorting, TRelationship>
     *
     * @param PropertyPathInterface&TRelationshipType $relationship
     *
     * @return ToManyRelationshipConfig<TCondition, TSorting, TEntity, TRelationship>
     *
     * @throws ResourcePropertyConfigException
     * @throws PathException
     */
    public function configureToManyRelationship(PropertyPathInterface&ResourceTypeInterface $relationship): ToManyRelationshipConfig
    {
        $this->validatePathStart($relationship);
        return $this->configCollection->configureToManyRelationship($relationship, $relationship);
    }

    /**
     * @param PropertyPathInterface $path
     *
     * @throws ResourcePropertyConfigException
     */
    protected function validatePathStart(PropertyPathInterface $path): void
    {
        if (!is_subclass_of($path, PropertyAutoPathInterface::class)) {
            return;
        }

        $expectedType = $this->configCollection->getType();
        $actualType = $path->getAsValues()[0];

        if ($expectedType === $actualType) {
            return;
        }

        throw ResourcePropertyConfigException::invalidStart($expectedType->getIdentifier());
    }
}
