<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ApiDocumentation\DefaultField;
use EDT\JsonApi\ApiDocumentation\DefaultInclude;
use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Querying\PropertyPaths\RelationshipLink;
use EDT\Wrapping\Contracts\ResourceTypeProviderInterface;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorInterface;
use Webmozart\Assert\Assert;

/**
 * @template TEntity of object
 * @template TRelationship of object
 * @template TValue of list<TRelationship>|TRelationship|null
 * @template TReadability of RelationshipReadabilityInterface
 *
 * @template-extends AbstractPropertyConfigBuilder<TEntity, TValue, RelationshipConstructorBehaviorFactoryInterface, RelationshipSetBehaviorFactoryInterface<TEntity, TRelationship>, RelationshipSetBehaviorFactoryInterface<TEntity, TRelationship>>
 * @template-implements RelationshipConfigBuilderInterface<TEntity, TRelationship, TValue>
 */
abstract class RelationshipConfigBuilder extends AbstractPropertyConfigBuilder implements RelationshipConfigBuilderInterface
{
     /**
      * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TRelationship>|ResourceTypeProviderInterface<TRelationship>): TReadability
      */
     protected $readabilityFactory;

    /**
     * @var ResourceTypeInterface<TRelationship>|ResourceTypeProviderInterface<TRelationship>|null
     */
    protected ResourceTypeInterface|ResourceTypeProviderInterface|null $relationshipType = null;

    /**
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        protected readonly string $entityClass,
        string $name
    ) {
        parent::__construct($name);
    }

    public function setRelationshipType(ResourceTypeInterface|ResourceTypeProviderInterface $relationshipType): self
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
     * @param FilteringTypeInterface|ResourceTypeProviderInterface<TRelationship> $relationshipType
     */
    protected function getFilterLink(FilteringTypeInterface|ResourceTypeProviderInterface $relationshipType): ?PropertyLinkInterface
    {
        if (!$this->filterable) {
            return null;
        }

        return new RelationshipLink(
            $this->getPropertyPath(),
            static fn (): array => ($relationshipType instanceof FilteringTypeInterface ? $relationshipType : $relationshipType->getType())->getFilteringProperties()
        );
    }

    /**
     * @param SortingTypeInterface|ResourceTypeProviderInterface<TRelationship> $relationshipType
     */
    protected function getSortLink(SortingTypeInterface|ResourceTypeProviderInterface $relationshipType): ?PropertyLinkInterface
    {
        if (!$this->sortable) {
            return null;
        }

        return new RelationshipLink(
            $this->getPropertyPath(),
            static fn (): array => ($relationshipType instanceof SortingTypeInterface ? $relationshipType : $relationshipType->getType())->getSortingProperties()
        );
    }

    /**
     * @return list<RelationshipSetBehaviorInterface<TEntity, TRelationship>>
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
        return array_map(fn(RelationshipConstructorBehaviorFactoryInterface $factory): ConstructorBehaviorInterface => $factory(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass,
            $this->getFinalRelationshipType()
        ), $this->constructorBehaviorFactories);
    }

    /**
     * @return list<RelationshipSetBehaviorInterface<TEntity, TRelationship>>
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
     * @return ResourceTypeInterface<TRelationship>|ResourceTypeProviderInterface<TRelationship>
     */
    protected function getFinalRelationshipType(): ResourceTypeInterface|ResourceTypeProviderInterface
    {
        Assert::notNull($this->relationshipType, 'The relationship type must be set before a config can be build.');

        return $this->relationshipType;
    }

    /**
     * @param ResourceTypeInterface<TRelationship>|ResourceTypeProviderInterface<TRelationship> $relationshipType
     *
     * @return TReadability|null
     */
    protected function getReadability(ResourceTypeInterface|ResourceTypeProviderInterface $relationshipType): ?RelationshipReadabilityInterface
    {
        return ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $relationshipType);
    }
 }
