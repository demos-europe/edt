<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\JsonApi\PropertyConfig\AttributeConfigInterface;
use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\ToManyRelationshipConfigInterface;
use EDT\JsonApi\PropertyConfig\ToOneRelationshipConfigInterface;
use EDT\JsonApi\ResourceConfig\ResourceConfig;
use EDT\JsonApi\ResourceConfig\ResourceConfigInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityInterface;
use Webmozart\Assert\Assert;
use function array_key_exists;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends BaseSchemaBuilder<TCondition, TSorting, TEntity>
 */
class UnifiedResourceConfigBuilder extends BaseSchemaBuilder
{
    /**
     * @param class-string<TEntity> $entityClass
     * @param array<non-empty-string, IdentifierConfigBuilder<TEntity, TCondition>|AttributeConfigBuilder<TCondition, TEntity>|ToOneRelationshipConfigBuilder<TCondition, TSorting, TEntity, object>|ToManyRelationshipConfigBuilder<TCondition, TSorting, TEntity, object>> $properties
     */
    public function __construct(
        protected readonly string $entityClass,
        protected array $properties
    ) {
        if (array_key_exists(ContentField::ID, $this->properties)) {
            Assert::isInstanceOf($this->properties[ContentField::ID], IdentifierConfigBuilder::class);
        }
        Assert::keyNotExists($this->properties, ContentField::TYPE);
    }

    public function build(): ResourceConfigInterface
    {
        $identifierConfigs = array_filter(
            $this->properties,
            static fn (IdentifierConfigBuilder|AttributeConfigBuilder|ToOneRelationshipConfigBuilder|ToManyRelationshipConfigBuilder $property): bool => $property instanceof IdentifierConfigBuilder
        );
        Assert::count($identifierConfigs, 1);
        $identifierConfig = array_pop($identifierConfigs);

        $attributeConfigs = array_map(
            static fn (AttributeConfigBuilder $builder): AttributeConfigInterface => $builder->build(),
            array_filter(
                $this->properties,
                static fn (IdentifierConfigBuilder|AttributeConfigBuilder|ToOneRelationshipConfigBuilder|ToManyRelationshipConfigBuilder $property): bool => $property instanceof AttributeConfigBuilder
            )
        );

        $toOneRelationshipConfigs = array_map(
            static fn (ToOneRelationshipConfigBuilder $builder): ToOneRelationshipConfigInterface => $builder->build(),
            array_filter(
                $this->properties,
                static fn (IdentifierConfigBuilder|AttributeConfigBuilder|ToOneRelationshipConfigBuilder|ToManyRelationshipConfigBuilder $property): bool => $property instanceof ToOneRelationshipConfigBuilder
            )
        );

        $toManyRelationshipConfig = array_map(
            static fn (ToManyRelationshipConfigBuilder $builder): ToManyRelationshipConfigInterface => $builder->build(),
            array_filter(
                $this->properties,
                static fn (IdentifierConfigBuilder|AttributeConfigBuilder|ToOneRelationshipConfigBuilder|ToManyRelationshipConfigBuilder $property): bool => $property instanceof ToManyRelationshipConfigBuilder
            )
        );

        return new ResourceConfig(
            $this->entityClass,
            $identifierConfig->build(),
            $attributeConfigs,
            $toOneRelationshipConfigs,
            $toManyRelationshipConfig,
            $this->generalConstructorBehavior,
            $this->generalPostConstructorBehavior,
            $this->generalUpdateBehaviors
        );
    }

    public function getAttributeConfigBuilder(string $propertyName): ?AttributeConfigBuilderInterface
    {
        if (!array_key_exists($propertyName, $this->properties)) {
            return null;
        }

        $builder = $this->properties[$propertyName];
        Assert::isInstanceOf($builder, AttributeConfigBuilder::class);

        return $builder;
    }

    public function getToOneRelationshipConfigBuilder(string $propertyName): ?ToOneRelationshipConfigBuilderInterface
    {
        if (!array_key_exists($propertyName, $this->properties)) {
            return null;
        }

        $builder = $this->properties[$propertyName];
        Assert::isInstanceOf($builder, ToOneRelationshipConfigBuilder::class);

        return $builder;
    }

    public function getToManyRelationshipConfigBuilder(string $propertyName): ?ToManyRelationshipConfigBuilderInterface
    {
        if (!array_key_exists($propertyName, $this->properties)) {
            return null;
        }

        $builder = $this->properties[$propertyName];
        Assert::isInstanceOf($builder, ToManyRelationshipConfigBuilder::class);

        return $builder;
    }

    public function getIdentifierConfigBuilder(): ?IdentifierConfigBuilderInterface
    {
        $builder = $this->properties[ContentField::ID] ?? null;
        if (null === $builder) {
            return null;
        }

        Assert::isInstanceOf($builder, IdentifierConfigBuilder::class);

        return $builder;
    }

    public function setIdentifierConfigBuilder(IdentifierConfigBuilder $builder): void
    {
        Assert::keyNotExists($this->properties, ContentField::ID);
        $this->properties[ContentField::ID] = $builder;
    }

    public function setAttributeConfigBuilder(string $propertyName, AttributeConfigBuilder $builder): void
    {
        Assert::keyNotExists($this->properties, $propertyName);
        $this->properties[$propertyName] = $builder;
    }

    public function setToOneRelationshipConfigBuilder(string $propertyName, ToOneRelationshipConfigBuilder $builder): void
    {
        Assert::keyNotExists($this->properties, $propertyName);
        $this->properties[$propertyName] = $builder;
    }

    public function setToManyRelationshipConfigBuilder(string $propertyName, ToManyRelationshipConfigBuilder $builder): void
    {
        Assert::keyNotExists($this->properties, $propertyName);
        $this->properties[$propertyName] = $builder;
    }
}
