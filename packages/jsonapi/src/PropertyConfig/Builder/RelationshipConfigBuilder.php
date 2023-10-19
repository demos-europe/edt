<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Querying\PropertyPaths\RelationshipLink;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorInterface;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 */
abstract class RelationshipConfigBuilder extends AbstractPropertyConfigBuilder
{
    /**
     * @var ResourceTypeInterface<TCondition, TSorting, TRelationship>|null
     */
    protected ?ResourceTypeInterface $relationshipType = null;

    /**
     * @var list<RelationshipConstructorBehaviorFactoryInterface<TCondition>>
     */
    protected array $constructorBehaviorFactories = [];

    /**
     * @var list<RelationshipSetBehaviorFactoryInterface<TCondition, TSorting, TEntity, TRelationship>>
     */
    protected array $postConstructorBehaviorFactories = [];

    /**
     * @var list<RelationshipSetBehaviorFactoryInterface<TCondition, TSorting, TEntity, TRelationship>>
     */
    protected array $updateBehaviorFactories = [];

    /**
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        protected readonly string $entityClass,
        string $name
    ) {
        parent::__construct($name);
    }

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     *
     * @return static
     */
    public function setRelationshipType(ResourceTypeInterface $relationshipType): self
    {
        $this->relationshipType = $relationshipType;

        return $this;
    }

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     */
    protected function getFilterLink(ResourceTypeInterface $relationshipType): ?PropertyLinkInterface
    {
        if (!$this->filterable) {
            return null;
        }

        if ($this->isExposedType()) {
            return null;
        }

        return new RelationshipLink(
            $this->getPropertyPath(),
            static fn (): array => $relationshipType->getFilteringProperties()
        );
    }

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     */
    protected function getSortLink(ResourceTypeInterface $relationshipType): ?PropertyLinkInterface
    {
        if (!$this->sortable) {
            return null;
        }

        if ($this->isExposedType()) {
            return null;
        }

        return new RelationshipLink(
            $this->getPropertyPath(),
            static fn (): array => $relationshipType->getSortingProperties()
        );
    }

    /**
     * Even if a relationship property was defined in a type, we do not allow its usage if the
     * target type of the relationship is not set as exposed.
     */
    protected function isExposedType(): bool
    {
        return $this->relationshipType instanceof ExposableRelationshipTypeInterface
            && $this->relationshipType->isExposedAsRelationship();
    }

    /**
     * @return list<RelationshipSetBehaviorInterface<TCondition, TSorting, TEntity, TRelationship>>
     */
    protected function getPostConstructorBehaviors(): array
    {
        return array_map(fn (
            RelationshipSetBehaviorFactoryInterface $factory
        ): RelationshipSetBehaviorInterface => $factory->createRelationshipSetBehavior(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass,
            $this->getFinalRelationshipType()
        ), $this->postConstructorBehaviorFactories);
    }

    /**
     * @return list<ConstructorBehaviorInterface>
     */
    protected function getConstructorBehaviors(): array
    {
        return array_map(fn(
            RelationshipConstructorBehaviorFactoryInterface $factory
        ): ConstructorBehaviorInterface => $factory->createRelationshipConstructorBehavior(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass,
            $this->getFinalRelationshipType()
        ), $this->constructorBehaviorFactories);
    }

    /**
     * @return list<RelationshipSetBehaviorInterface<TCondition, TSorting, TEntity, TRelationship>>
     */
    protected function getUpdateBehaviors(): array
    {
        return array_map(fn(
            RelationshipSetBehaviorFactoryInterface $factory
        ): RelationshipSetBehaviorInterface => $factory->createRelationshipSetBehavior(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass,
            $this->getFinalRelationshipType()
        ), $this->updateBehaviorFactories);
    }

    /**
     * @return ResourceTypeInterface<TCondition, TSorting, TRelationship>
     */
    protected function getFinalRelationshipType(): ResourceTypeInterface
    {
        Assert::notNull($this->relationshipType, 'The relationship type must be set before a config can be build.');

        return $this->relationshipType;
    }
}
