<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ApiDocumentation\DefaultField;
use EDT\JsonApi\ApiDocumentation\DefaultInclude;
use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Querying\PropertyPaths\RelationshipLink;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorInterface;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 * @template TValue of list<TRelationship>|TRelationship|null
 * @template TReadability of RelationshipReadabilityInterface
 *
 * @template-extends AbstractPropertyConfigBuilder<TEntity, TCondition, TValue, RelationshipConstructorBehaviorFactoryInterface<TCondition>, RelationshipSetBehaviorFactoryInterface<TCondition, TSorting, TEntity, TRelationship>, RelationshipSetBehaviorFactoryInterface<TCondition, TSorting, TEntity, TRelationship>>
 * @template-implements RelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, TRelationship, TValue>
 */
abstract class RelationshipConfigBuilder extends AbstractPropertyConfigBuilder implements RelationshipConfigBuilderInterface
{
     /**
      * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TCondition, TSorting, TRelationship>): TReadability
      */
     protected $readabilityFactory;

    /**
     * @var ResourceTypeInterface<TCondition, TSorting, TRelationship>|null
     */
    protected ?ResourceTypeInterface $relationshipType = null;

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
     * @return $this
     */
    public function setRelationshipType(ResourceTypeInterface $relationshipType): self
    {
        $this->relationshipType = $relationshipType;

        return $this;
    }

    abstract public function addPathCreationBehavior(OptionalField $optional = OptionalField::NO, array $entityConditions = [], array $relationshipConditions = []): self;

    abstract public function readable(bool $defaultField = false, callable $customReadCallback = null, bool $defaultInclude = false): self;

    public function setReadableByPath(DefaultField $defaultField = DefaultField::NO, DefaultInclude $defaultInclude = DefaultInclude::NO): self
    {
        return $this->readable($defaultField->equals(DefaultField::YES), null, $defaultInclude->equals(DefaultInclude::YES));
    }

    public function setNonReadable(): AttributeOrRelationshipBuilderInterface
    {
        $this->readabilityFactory = null;

        return $this;
    }

    public function setReadableByCallable(callable $behavior, DefaultField $defaultField = DefaultField::NO, DefaultInclude $defaultInclude = DefaultInclude::NO): self
    {
        return $this->readable($defaultField->equals(DefaultField::YES), $behavior, $defaultInclude->equals(DefaultInclude::YES));
    }

    public function addPathUpdateBehavior(array $entityConditions = [], array $relationshipConditions = []): self
    {
        return $this->updatable($entityConditions, $relationshipConditions);
    }

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     */
    protected function getFilterLink(ResourceTypeInterface $relationshipType): ?PropertyLinkInterface
    {
        if (!$this->filterable) {
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

        return new RelationshipLink(
            $this->getPropertyPath(),
            static fn (): array => $relationshipType->getSortingProperties()
        );
    }

    /**
     * @return list<RelationshipSetBehaviorInterface<TCondition, TSorting, TEntity, TRelationship>>
     */
    protected function getPostConstructorBehaviors(): array
    {
        return array_map(fn (
            RelationshipSetBehaviorFactoryInterface $factory
        ): RelationshipSetBehaviorInterface => $factory(
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
        return array_map(fn(callable $factory): ConstructorBehaviorInterface => $factory(
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
        ): RelationshipSetBehaviorInterface => $factory(
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

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     *
     * @return TReadability|null
     */
    protected function getReadability(ResourceTypeInterface $relationshipType): ?RelationshipReadabilityInterface
    {
        return ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $relationshipType);
    }
 }
