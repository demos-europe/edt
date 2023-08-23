<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\Properties\Attributes\CallbackAttributeReadability;
use EDT\JsonApi\Properties\Attributes\PathAttributeReadability;
use EDT\JsonApi\Properties\Id\PathIdReadability;
use EDT\JsonApi\Properties\PropertyConfigInterface;
use EDT\JsonApi\Properties\Relationships\CallbackToManyRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\CallbackToOneRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\PathToManyRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipReadability;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;
use EDT\Wrapping\Properties\ConstructorParameterInterface;
use EDT\Wrapping\Properties\IdReadabilityInterface;
use EDT\Wrapping\Properties\PropertySetabilityInterface;
use EDT\Wrapping\Properties\RelationshipSetabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipReadabilityInterface;
use Webmozart\Assert\Assert;

/**
 * Set up a specific property for accesses via the generic JSON:API implementation.
 *
 * * {@link PropertyConfig::filterable filtering via property values}
 * * {@link PropertyConfig::sortable sorting via property values}
 * * {@link PropertyConfig::readable reading of actual property values}
 * * {@link PropertyConfig::initializable creating of resources with property values}
 *
 * You can also mark the property as an alias by setting {@link PropertyConfig::aliasedPath()}.
 * This will result in all accesses mentioned above expecting that the path segments having
 * corresponding properties in the backend entities.
 *
 * @template TEntity of object
 * @template TValue
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 *
 * @template-implements PropertyConfigInterface<TEntity, TValue, TCondition, TSorting>
 */
class PropertyConfig implements PropertyConfigInterface
{
    /**
     * @var non-empty-string
     */
    protected string $name;

    protected bool $readable = false;

    protected bool $filterable = false;

    protected bool $sortable = false;

    /**
     * @var non-empty-list<non-empty-string>|null
     */
    protected ?array $aliasedPath = null;

    protected bool $defaultField = false;

    /**
     * @var null|callable(TEntity): TValue
     */
    protected $customReadCallback;

    /**
     * @var PropertyBehaviorBuilderInterface<PropertySetabilityInterface<TCondition, TEntity>>|null
     */
    protected ?PropertyBehaviorBuilderInterface $initializingSetability = null;

    /**
     * @var PropertyBehaviorBuilderInterface<PropertySetabilityInterface<TCondition, TEntity>>|null
     */
    protected ?PropertyBehaviorBuilderInterface $attributeUpdatingSetability = null;
    /**
     * @var PropertyBehaviorBuilderInterface<RelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>>|null
     */
    protected ?PropertyBehaviorBuilderInterface $toOneUpdatingSetability = null;
    
    /**
     * @var PropertyBehaviorBuilderInterface<RelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>>|null
     */
    protected ?PropertyBehaviorBuilderInterface $toManyUpdatingSetability = null;

    /**
     * @var PropertyBehaviorBuilderInterface<ConstructorParameterInterface<TCondition, TSorting>>|null
     */
    protected ?PropertyBehaviorBuilderInterface $initializingConstructorParameter = null;

    /**
     * @param PropertyPathInterface $path must contain exactly one segment
     * @param class-string<TEntity> $entityClass
     * @param array{relationshipType: ResourceTypeInterface<TCondition, TSorting, object>, defaultInclude: bool, toMany: bool}|null $relationship
     *
     * @throws PathException
     */
    public function __construct(
        PropertyPathInterface $path,
        protected readonly string $entityClass,
        protected readonly ?array $relationship,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver,
    ) {
        $names = $path->getAsNames();
        Assert::count($names, 1);
        $this->name = $names[0];
    }

    /**
     * @param non-empty-list<non-empty-string>|PropertyPathInterface $aliasedPath
     *
     * @return $this
     */
    public function aliasedPath(array|PropertyPathInterface $aliasedPath): self
    {
        $this->aliasedPath = $aliasedPath instanceof PropertyPathInterface
            ? $aliasedPath->getAsNames()
            : $aliasedPath;

        return $this;
    }

    /**
     * @return $this
     */
    public function filterable(): self
    {
        $this->filterable = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function sortable(): self
    {
        $this->sortable = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function readable(bool $defaultField = false, callable $customReadCallback = null): self
    {
        $this->readable = true;
        $this->defaultField = $defaultField;
        $this->customReadCallback = $customReadCallback;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUpdatingSetability(PropertyBehaviorBuilderInterface $builder): self
    {
        if ($this->isAttribute()) {
            $this->attributeUpdatingSetability = $builder;
        }

        if ($this->isToOneRelationship()) {
            $this->toOneUpdatingSetability = $builder;
        }

        if ($this->isToManyRelationship()) {
            $this->toManyUpdatingSetability = $builder;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setInitializingSetability(PropertyBehaviorBuilderInterface $builder): self
    {
        $this->initializingSetability = $builder;

        return $this;
    }

    /**
     * @return $this
     */
    public function setInitializingConstructorParameter(PropertyBehaviorBuilderInterface $builder): self
    {
        $this->initializingConstructorParameter = $builder;

        return $this;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return AttributeReadabilityInterface<TEntity>|null
     */
    public function getAttributeReadability(): ?AttributeReadabilityInterface
    {
        if (ContentField::ID === $this->name) {
            return null;
        }
        if (!$this->readable || null !== $this->relationship) {
            return null;
        }

        if (null === $this->customReadCallback) {
            return new PathAttributeReadability(
                $this->entityClass,
                $this->getPropertyPath(),
                $this->defaultField,
                $this->propertyAccessor,
                $this->typeResolver
            );
        }

        return new CallbackAttributeReadability(
            $this->defaultField,
            $this->customReadCallback,
            $this->typeResolver
        );
    }

    /**
     * @return ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToOneRelationshipReadability(): ?ToOneRelationshipReadabilityInterface
    {
        if (!$this->readable || null === $this->relationship || $this->relationship['toMany']) {
            return null;
        }

        if (null === $this->customReadCallback) {
            return new PathToOneRelationshipReadability(
                $this->entityClass,
                $this->getPropertyPath(),
                $this->defaultField,
                $this->relationship['defaultInclude'],
                $this->relationship['relationshipType'],
                $this->propertyAccessor
            );
        }

        return new CallbackToOneRelationshipReadability(
            $this->defaultField,
            $this->relationship['defaultInclude'],
            $this->customReadCallback,
            $this->relationship['relationshipType']
        );
    }

    /**
     * @return ToManyRelationshipReadabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToManyRelationshipReadability(): ?ToManyRelationshipReadabilityInterface
    {
        if (!$this->readable || null === $this->relationship || !$this->relationship['toMany']) {
            return null;
        }

        if (null === $this->customReadCallback) {
            return new PathToManyRelationshipReadability(
                $this->entityClass,
                $this->getPropertyPath(),
                $this->defaultField,
                $this->relationship['defaultInclude'],
                $this->relationship['relationshipType'],
                $this->propertyAccessor
            );
        }

        return new CallbackToManyRelationshipReadability(
            $this->defaultField,
            $this->relationship['defaultInclude'],
            $this->customReadCallback,
            $this->relationship['relationshipType']
        );
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isAttribute(): bool
    {
        return null === $this->relationship;
    }

    public function isToOneRelationship(): bool
    {
        return !$this->isAttribute() && !$this->isToManyRelationship();
    }

    public function isToManyRelationship(): bool
    {
        return $this->relationship['toMany'] ?? false;
    }

    /**
     * @return ResourceTypeInterface<TCondition, TSorting, object>|null
     */
    public function getRelationshipType(): ?ResourceTypeInterface
    {
        return $this->relationship['relationshipType'] ?? null;
    }

    /**
     * @return FilteringTypeInterface<TCondition, TSorting>|null
     */
    public function getFilterableRelationshipType(): ?FilteringTypeInterface
    {
        return $this->relationship['relationshipType'] ?? null;
    }

    /**
     * @return SortingTypeInterface<TCondition, TSorting>|null
     */
    public function getSortableRelationshipType(): ?SortingTypeInterface
    {
        return $this->relationship['relationshipType'] ?? null;
    }

    /**
     * @return PropertySetabilityInterface<TCondition, TEntity>
     */
    public function getAttributeUpdatingSetability(): ?PropertySetabilityInterface
    {
        if (!$this->isAttribute()) {
            return null;
        }

        return $this->attributeUpdatingSetability?->build($this->getName(), $this->getPropertyPath());
    }

    /**
     * @return RelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToOneRelationshipUpdatingSetabilities(): ?RelationshipSetabilityInterface
    {
        if (!$this->isToOneRelationship()) {
            return null;
        }

        return $this->toOneUpdatingSetability?->build($this->getName(), $this->getPropertyPath());
    }

    /**
     * @return RelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToManyRelationshipUpdatingSetability(): ?RelationshipSetabilityInterface
    {
        if (!$this->isToManyRelationship()) {
            return null;
        }

        return $this->toManyUpdatingSetability?->build($this->getName(), $this->getPropertyPath());
    }

    /**
     * @return PropertySetabilityInterface<TCondition, TEntity>|null
     */
    public function getInitializingSetability(): ?PropertySetabilityInterface
    {
        return $this->initializingSetability?->build($this->getName(), $this->getPropertyPath());
    }

    /**
     * @return ConstructorParameterInterface<TCondition, TSorting>|null
     */
    public function getInitializingConstructorParameter(): ?ConstructorParameterInterface
    {
        return $this->initializingConstructorParameter?->build($this->getName(), $this->getPropertyPath());
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function getPropertyPath(): array
    {
        return $this->aliasedPath ?? [$this->name];
    }

    /**
     * @return IdReadabilityInterface<TEntity>|null
     */
    public function getIdentifierReadability(): ?IdReadabilityInterface
    {
        return ContentField::ID === $this->name
            ? new PathIdReadability($this->entityClass, $this->getPropertyPath(), $this->propertyAccessor, $this->typeResolver)
            : null;
    }
}
