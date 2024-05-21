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
use function array_key_exists;

/**
 * @template TEntity of object
 *
 * @template-extends BaseSchemaBuilder<TEntity>
 */
abstract class AbstractResourceConfigBuilder extends BaseSchemaBuilder
{
    /**
     * @var array<non-empty-string, AttributeConfigBuilder<TEntity>>
     */
    private array $attributes = [];

    /**
     * @var array<non-empty-string, ToOneRelationshipConfigBuilder<TEntity, object>>
     */
    private array $toOneRelationships = [];

    /**
     * @var array<non-empty-string, ToManyRelationshipConfigBuilder<TEntity, object>>
     */
    private array $toManyRelationships = [];

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

    /**
     * @param non-empty-string $propertyName
     */
    protected function hasAttributeConfigBuilder(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->attributes);
    }

    public function setAttributeConfigBuilder(string $propertyName, AttributeConfigBuilder $builder): void
    {
        $this->attributes[$propertyName] = $builder;
    }

    public function getToOneRelationshipConfigBuilder(string $propertyName): ?ToOneRelationshipConfigBuilderInterface
    {
        return $this->toOneRelationships[$propertyName] ?? null;
    }

    /**
     * @param non-empty-string $propertyName
     */
    protected function hasToOneRelationshipConfigBuilder(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->toOneRelationships);
    }

    public function setToOneRelationshipConfigBuilder(string $propertyName, ToOneRelationshipConfigBuilder $builder): void
    {
        $this->toOneRelationships[$propertyName] = $builder;
    }

    public function getToManyRelationshipConfigBuilder(string $propertyName): ?ToManyRelationshipConfigBuilderInterface
    {
        return $this->toManyRelationships[$propertyName] ?? null;
    }

    /**
     * @param non-empty-string $propertyName
     */
    protected function hasToManyRelationshipConfigBuilder(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->toManyRelationships);
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
            $this->getBuiltToManyRelationshipConfigs(),
            $this->generalConstructorBehavior,
            $this->generalPostConstructorBehavior,
            $this->generalUpdateBehaviors
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
     * @return array<non-empty-string, AttributeConfigInterface<TEntity>>
     */
    protected function getBuiltAttributeConfigs(): array
    {
        return array_map(
            static fn (AttributeConfigBuilder $property): AttributeConfigInterface => $property->build(),
            $this->attributes
        );
    }

    /**
     * @return array<non-empty-string, ToOneRelationshipConfigInterface<TEntity, object>>
     */
    protected function getBuiltToOneRelationshipConfigs(): array
    {
        return array_map(
            static fn (ToOneRelationshipConfigBuilder $property): ToOneRelationshipConfigInterface => $property->build(),
            $this->toOneRelationships
        );
    }

    /**
     * @return array<non-empty-string, ToManyRelationshipConfigInterface<TEntity, object>>
     */
    protected function getBuiltToManyRelationshipConfigs(): array
    {
        return array_map(
            static fn(ToManyRelationshipConfigBuilder $property): ToManyRelationshipConfigInterface => $property->build(),
            $this->toManyRelationships
        );
    }
}
