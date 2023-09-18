<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\JsonApi\PropertyConfig\AttributeConfigInterface;
use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilder;
use EDT\JsonApi\PropertyConfig\ToManyRelationshipConfigInterface;
use EDT\JsonApi\PropertyConfig\ToOneRelationshipConfigInterface;
use EDT\JsonApi\ResourceConfig\ResourceConfig;
use EDT\JsonApi\ResourceConfig\ResourceConfigInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements ResourceConfigBuilderInterface<TCondition, TSorting, TEntity>
 */
class UnifiedResourceConfigBuilder implements ResourceConfigBuilderInterface
{
    /**
     * @var array<non-empty-string, IdentifierConfigBuilder<TEntity>|AttributeConfigBuilder<TCondition, TEntity>|ToOneRelationshipConfigBuilder<TCondition, TSorting, TEntity, object>|ToManyRelationshipConfigBuilder<TCondition, TSorting, TEntity, object>>
     */
    protected array $properties = [];

    /**
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        protected readonly string $entityClass
    ) {}

    /**
     * @param array<non-empty-string, IdentifierConfigBuilder<TEntity>|IdentifierConfigBuilder<TEntity>|AttributeConfigBuilder<TCondition, TEntity>|ToOneRelationshipConfigBuilder<TCondition, TSorting, TEntity, object>|ToManyRelationshipConfigBuilder<TCondition, TSorting, TEntity, object>> $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
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
            $toManyRelationshipConfig
        );
    }
}
