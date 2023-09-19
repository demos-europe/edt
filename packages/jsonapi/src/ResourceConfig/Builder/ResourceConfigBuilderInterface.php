<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilderInterface;
use EDT\JsonApi\ResourceConfig\ResourceConfigInterface;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
interface ResourceConfigBuilderInterface
{
    /**
     * @return IdentifierConfigBuilderInterface<TEntity>|null
     */
    public function getIdentifierConfigBuilder(): ?IdentifierConfigBuilderInterface;

    /**
     * @param IdentifierConfigBuilder<TEntity> $builder
     */
    public function setIdentifierConfigBuilder(IdentifierConfigBuilder $builder): void;

    /**
     * @param non-empty-string $propertyName
     *
     * @return AttributeConfigBuilderInterface<TCondition, TEntity>|null
     */
    public function getAttributeConfigBuilder(string $propertyName): ?AttributeConfigBuilderInterface;

    /**
     * @param non-empty-string $propertyName
     * @param AttributeConfigBuilder<TCondition, TEntity> $builder
     */
    public function setAttributeConfigBuilder(string $propertyName, AttributeConfigBuilder $builder): void;

    /**
     * @param non-empty-string $propertyName
     *
     * @return ToOneRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToOneRelationshipConfigBuilder(string $propertyName): ?ToOneRelationshipConfigBuilderInterface;

    /**
     * @param non-empty-string $propertyName
     * @param ToOneRelationshipConfigBuilder<TCondition, TSorting, TEntity, object> $builder
     */
    public function setToOneRelationshipConfigBuilder(string $propertyName, ToOneRelationshipConfigBuilder $builder): void;

    /**
     * @param non-empty-string $propertyName
     *
     * @return ToManyRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToManyRelationshipConfigBuilder(string $propertyName): ?ToManyRelationshipConfigBuilderInterface;

    /**
     * @param non-empty-string $propertyName
     * @param ToManyRelationshipConfigBuilder<TCondition, TSorting, TEntity, object> $builder
     */
    public function setToManyRelationshipConfigBuilder(string $propertyName, ToManyRelationshipConfigBuilder $builder): void;

    /**
     * @return ResourceConfigInterface<TCondition, TSorting, TEntity>
     */
    public function build(): ResourceConfigInterface;
}
