<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\JsonApi\PropertyConfig\AttributeConfigInterface;
use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\IdentifierConfigInterface;
use EDT\JsonApi\PropertyConfig\ToManyRelationshipConfigInterface;
use EDT\JsonApi\PropertyConfig\ToOneRelationshipConfigInterface;
use EDT\JsonApi\ResourceConfig\ResourceConfig;
use EDT\JsonApi\ResourceConfig\ResourceConfigInterface;
use EDT\PathBuilding\DocblockPropertyByTraitEvaluator;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements ResourceConfigBuilderInterface<TCondition, TSorting, TEntity>
 */
abstract class AbstractResourceConfigBuilder implements ResourceConfigBuilderInterface
{
    /**
     * @var array<non-empty-string, AttributeConfigBuilder<TCondition, TEntity>>
     */
    protected array $attributes = [];

    /**
     * @var array<non-empty-string, ToOneRelationshipConfigBuilder<TCondition, TSorting, TEntity, object>>
     */
    protected array $toOneRelationships = [];

    /**
     * @var array<non-empty-string, ToManyRelationshipConfigBuilder<TCondition, TSorting, TEntity, object>>
     */
    protected array $toManyRelationships = [];

    /**
     * @param class-string<TEntity> $entityClass
     * @param IdentifierConfigBuilder<TEntity> $identifier
     */
    public function __construct(
        protected readonly string $entityClass,
        protected IdentifierConfigBuilder $identifier
    ) {}

    /**
     * @return IdentifierConfigBuilderInterface<TEntity>
     */
    public function getIdentifierConfigBuilder(): IdentifierConfigBuilderInterface
    {
        return $this->identifier;
    }

    public function setIdentifierConfigBuilder(IdentifierConfigBuilder $builder): void
    {
        $this->identifier = $builder;
    }

    public function getAttributeConfigBuilder(string $propertyName): ?AttributeConfigBuilderInterface
    {
        return $this->attributes[$propertyName] ?? null;
    }

    public function setAttributeConfigBuilder(string $propertyName, AttributeConfigBuilder $builder): void
    {
        $this->attributes[$propertyName] = $builder;
    }

    public function getToOneRelationshipConfigBuilder(string $propertyName): ?ToOneRelationshipConfigBuilderInterface
    {
        return $this->toOneRelationships[$propertyName] ?? null;
    }

    public function setToOneRelationshipConfigBuilder(string $propertyName, ToOneRelationshipConfigBuilder $builder): void
    {
        $this->toOneRelationships[$propertyName] = $builder;
    }

    public function getToManyRelationshipConfigBuilder(string $propertyName): ?ToManyRelationshipConfigBuilderInterface
    {
        return $this->toManyRelationships[$propertyName] ?? null;
    }

    public function setToManyRelationshipConfigBuilder(string $propertyName, ToManyRelationshipConfigBuilder $builder): void
    {
        $this->toManyRelationships[$propertyName] = $builder;
    }

    public function build(): ResourceConfigInterface
    {
        return new ResourceConfig(
            $this->entityClass,
            $this->getBuiltIdentifierConfig(),
            $this->getBuiltAttributeConfigs(),
            $this->getBuiltToOneRelationshipConfigs(),
            $this->getBuiltToManyRelationshipConfigs()
        );
    }

    /**
     * @return IdentifierConfigInterface<TEntity>
     */
    protected function getBuiltIdentifierConfig(): IdentifierConfigInterface
    {
        return $this->identifier->build();
    }

    /**
     * @return array<non-empty-string, AttributeConfigInterface<TCondition, TEntity>>
     */
    protected function getBuiltAttributeConfigs(): array
    {
        return array_map(
            static fn (AttributeConfigBuilder $property): AttributeConfigInterface => $property->build(),
            $this->attributes
        );
    }

    /**
     * @return array<non-empty-string, ToOneRelationshipConfigInterface<TCondition, TSorting, TEntity, object>>
     */
    protected function getBuiltToOneRelationshipConfigs(): array
    {
        return array_map(
            static fn (ToOneRelationshipConfigBuilder $property): ToOneRelationshipConfigInterface => $property->build(),
            $this->toOneRelationships
        );
    }

    /**
     * @return array<non-empty-string, ToManyRelationshipConfigInterface<TCondition, TSorting, TEntity, object>>
     */
    protected function getBuiltToManyRelationshipConfigs(): array
    {
        return array_map(
            static fn(ToManyRelationshipConfigBuilder $property): ToManyRelationshipConfigInterface => $property->build(),
            $this->toManyRelationships
        );
    }
}
