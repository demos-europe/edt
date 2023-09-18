<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig;

use EDT\JsonApi\PropertyConfig\AttributeConfigInterface;
use EDT\JsonApi\PropertyConfig\IdentifierConfigInterface;
use EDT\JsonApi\PropertyConfig\PropertyConfigInterface;
use EDT\JsonApi\PropertyConfig\RelationshipConfigInterface;
use EDT\JsonApi\PropertyConfig\ToManyRelationshipConfigInterface;
use EDT\JsonApi\PropertyConfig\ToOneRelationshipConfigInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Querying\Utilities\Iterables;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorParameterInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipReadabilityInterface;
use EDT\Wrapping\ResourceBehavior\ResourceInstantiability;
use EDT\Wrapping\ResourceBehavior\ResourceReadability;
use EDT\Wrapping\ResourceBehavior\ResourceUpdatability;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements ResourceConfigInterface<TCondition, TSorting, TEntity>
 */
class ResourceConfig implements ResourceConfigInterface
{
    /**
     * @var array<non-empty-string, AttributeConfigInterface<TCondition, TEntity>|RelationshipConfigInterface<TCondition, TSorting, TEntity, object>>
     */
    protected readonly array $propertyConfigs;

    /**
     * @param class-string<TEntity> $entityClass
     * @param IdentifierConfigInterface<TEntity> $identifierConfig
     * @param array<non-empty-string, AttributeConfigInterface<TCondition, TEntity>> $attributeConfigs
     * @param array<non-empty-string, ToOneRelationshipConfigInterface<TCondition, TSorting, TEntity, object>> $toOneRelationshipConfigs
     * @param array<non-empty-string, ToManyRelationshipConfigInterface<TCondition, TSorting, TEntity, object>> $toManyRelationshipConfigs
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly IdentifierConfigInterface $identifierConfig,
        protected readonly array $attributeConfigs,
        protected readonly array $toOneRelationshipConfigs,
        protected readonly array $toManyRelationshipConfigs
    ) {
        $this->propertyConfigs =  array_merge(
            $this->attributeConfigs,
            $this->toOneRelationshipConfigs,
            $this->toManyRelationshipConfigs
        );
        Assert::keyNotExists($this->propertyConfigs, ContentField::ID);
        Assert::keyNotExists($this->propertyConfigs, ContentField::TYPE);
    }

    public function getInstantiability(): ResourceInstantiability
    {
        $constructorParameterConfigs = Iterables::removeNull(array_map(
            static fn (PropertyConfigInterface $property): ?ConstructorParameterInterface => $property->getInstantiability(),
            $this->propertyConfigs
        ));

        $setabilities = Iterables::removeNull(array_map(
            static fn (PropertyConfigInterface $property): ?PropertySetabilityInterface => $property->getPostInstantiability(),
            $this->propertyConfigs
        ));

        $identifierInstantiability = $this->identifierConfig->getInstantiability();
        if (null !== $identifierInstantiability) {
            $constructorParameterConfigs[ContentField::ID] = $identifierInstantiability;
        }

        return new ResourceInstantiability(
            $this->entityClass,
            $constructorParameterConfigs,
            $setabilities,
            $this->identifierConfig->getPostInstantiability()
        );
    }

    public function getUpdatability(): ResourceUpdatability
    {
        return new ResourceUpdatability(
            Iterables::removeNull(array_map(
                static fn (AttributeConfigInterface $property): ?PropertySetabilityInterface => $property->getUpdatability(),
                $this->attributeConfigs
            )),
            Iterables::removeNull(array_map(
                static fn (ToOneRelationshipConfigInterface $property): ?RelationshipSetabilityInterface => $property->getUpdatability(),
                $this->toOneRelationshipConfigs
            )),
            Iterables::removeNull(array_map(
                static fn (ToManyRelationshipConfigInterface $property): ?RelationshipSetabilityInterface => $property->getUpdatability(),
                $this->toManyRelationshipConfigs
            ))
        );
    }

    public function getReadability(): ResourceReadability
    {
        return new ResourceReadability(
            Iterables::removeNull(array_map(
                static fn (AttributeConfigInterface $property): ?AttributeReadabilityInterface => $property->getReadability(),
                $this->attributeConfigs
            )),
            Iterables::removeNull(array_map(
                static fn (ToOneRelationshipConfigInterface $property): ?ToOneRelationshipReadabilityInterface => $property->getReadability(),
                $this->toOneRelationshipConfigs
            )),
            Iterables::removeNull(array_map(
                static fn (ToManyRelationshipConfigInterface $property): ?ToManyRelationshipReadabilityInterface => $property->getReadability(),
                $this->toManyRelationshipConfigs
            )),
            $this->identifierConfig->getReadability()
        );
    }

    public function getSortingProperties(): array
    {
        return Iterables::removeNull(array_map(
            static fn (AttributeConfigInterface|RelationshipConfigInterface $property): ?PropertyLinkInterface => $property->getSortLink(),
            $this->propertyConfigs
        ));
    }

    public function getFilteringProperties(): array
    {
        return Iterables::removeNull(array_map(
            static fn (AttributeConfigInterface|RelationshipConfigInterface $property): ?PropertyLinkInterface => $property->getFilterLink(),
            $this->propertyConfigs
        ));
    }
}
