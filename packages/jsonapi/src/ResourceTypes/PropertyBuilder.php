<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\Properties\Attributes\CallbackAttributeReadability;
use EDT\JsonApi\Properties\Attributes\CallbackAttributeUpdatability;
use EDT\JsonApi\Properties\Attributes\PathAttributeReadability;
use EDT\JsonApi\Properties\Attributes\PathAttributeUpdatability;
use EDT\JsonApi\Properties\PropertyConfigInterface;
use EDT\JsonApi\Properties\Relationships\CallbackToManyRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\CallbackToManyRelationshipUpdatability;
use EDT\JsonApi\Properties\Relationships\CallbackToOneRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\CallbackToOneRelationshipUpdatability;
use EDT\JsonApi\Properties\Relationships\PathToManyRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\PathToManyRelationshipUpdatability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipUpdatability;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;
use EDT\Wrapping\Properties\AttributeUpdatabilityInterface;
use EDT\Wrapping\Properties\Initializability;
use EDT\Wrapping\Properties\PropertyInitializabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipUpdatabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipUpdatabilityInterface;
use EDT\Wrapping\Utilities\EntityVerifierInterface;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

/**
 * Set up a specific property for accesses via the generic JSON:API implementation.
 *
 * * {@link PropertyBuilder::filterable filtering via property values}
 * * {@link PropertyBuilder::sortable sorting via property values}
 * * {@link PropertyBuilder::readable reading of actual property values}
 * * {@link PropertyBuilder::initializable creating of resources with property values}
 *
 * You can also mark the property as an alias by setting {@link PropertyBuilder::aliasedPath()}.
 * This will result in all accesses mentioned above expecting that the path segments having
 * corresponding properties in the backend entities.
 *
 * Note that the resource type itself must return `true` in
 * {@link ExposablePrimaryResourceTypeInterface::isExposedAsPrimaryResource()} to be accessed
 * directly via the JSON:API or in
 * {@link ExposableRelationshipTypeInterface::isExposedAsRelationship()} to be usable as
 * relationship via the JSON:API.
 *
 * @template TEntity of object
 * @template TValue
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 *
 * @template-implements PropertyConfigInterface<TEntity, TValue, TCondition>
 */
class PropertyBuilder implements PropertyConfigInterface
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

    protected bool $initializable = false;

    protected bool $requiredForCreation = true;

    private bool $updatable = false;

    /**
     * @var list<TCondition>
     */
    private array $updateEntityConditions = [];

    /**
     * @var list<TCondition>
     */
    private array $updateRelationshipConditions = [];

    /**
     * @var null|callable(TEntity, TValue): void
     */
    private $customUpdateCallback;

    /**
     * @param class-string<TEntity> $entityClass
     * @param array{relationshipType: ResourceTypeInterface<TCondition, TSorting, object>, defaultInclude: bool, toMany: bool}|null $relationship
     * @param EntityVerifierInterface<TCondition, TSorting> $entityVerifier
     *
     * @throws PathException
     */
    public function __construct(
        PropertyPathInterface $path,
        protected readonly string $entityClass,
        protected readonly ?array $relationship,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver,
        protected readonly EntityVerifierInterface $entityVerifier
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
    public function updatable(
        array $entityConditions,
        ?callable $customUpdateCallback,
        array $relationshipConditions = [],
    ): self {
        if (null === $this->relationship && [] !== $relationshipConditions) {
            throw new InvalidArgumentException("Can't set relationship conditions for an attribute configuration: $this->name");
        }

        $this->updatable = true;
        $this->updateEntityConditions = $entityConditions;
        $this->updateRelationshipConditions = $relationshipConditions;
        $this->customUpdateCallback = $customUpdateCallback;

        return $this;
    }

    /**
     * @return $this
     */
    public function initializable(bool $optional = false): self
    {
        $this->initializable = true;
        $this->requiredForCreation = !$optional;

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
                $this->propertyAccessor,
                $this->entityVerifier
            );
        }

        return new CallbackToOneRelationshipReadability(
            $this->defaultField,
            $this->relationship['defaultInclude'],
            $this->customReadCallback,
            $this->relationship['relationshipType'],
            $this->entityVerifier
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
                $this->propertyAccessor,
                $this->entityVerifier
            );
        }

        return new CallbackToManyRelationshipReadability(
            $this->defaultField,
            $this->relationship['defaultInclude'],
            $this->customReadCallback,
            $this->relationship['relationshipType'],
            $this->entityVerifier
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

    /**
     * @return ResourceTypeInterface<TCondition, TSorting, object>|null
     */
    public function getRelationshipType(): ?ResourceTypeInterface
    {
        return $this->relationship['relationshipType'] ?? null;
    }

    /**
     * @return FilterableTypeInterface<TCondition, TSorting, object>|null
     */
    public function getFilterableRelationshipType(): ?FilterableTypeInterface
    {
        return $this->relationship['relationshipType'] ?? null;
    }

    /**
     * @return SortableTypeInterface<TCondition, TSorting, object>|null
     */
    public function getSortableRelationshipType(): ?SortableTypeInterface
    {
        return $this->relationship['relationshipType'] ?? null;
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
     * @return PropertyInitializabilityInterface<TCondition>|null
     */
    public function getInitializability(): ?PropertyInitializabilityInterface
    {
        if (!$this->initializable) {
            return null;
        }

        return new Initializability([], !$this->requiredForCreation);
    }

    /**
     * @return AttributeUpdatabilityInterface<TCondition, TEntity>|null
     */
    public function getAttributeUpdatability(): ?AttributeUpdatabilityInterface
    {
        if (!$this->updatable || null !== $this->relationship) {
            return null;
        }

        if (null === $this->customUpdateCallback) {
            return new PathAttributeUpdatability(
                $this->entityClass,
                $this->updateEntityConditions,
                $this->getPropertyPath(),
                $this->propertyAccessor
            );
        }

        return new CallbackAttributeUpdatability(
            $this->updateEntityConditions,
            $this->customUpdateCallback
        );
    }

    /**
     * @return ToOneRelationshipUpdatabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToOneRelationshipUpdatability(): ?ToOneRelationshipUpdatabilityInterface
    {
        if (!$this->updatable || null === $this->relationship || $this->relationship['toMany']) {
            return null;
        }

        if (null === $this->customUpdateCallback) {
            return new PathToOneRelationshipUpdatability(
                $this->entityClass,
                $this->updateEntityConditions,
                $this->updateRelationshipConditions,
                $this->relationship['relationshipType'],
                $this->getPropertyPath(),
                $this->propertyAccessor,
                $this->entityVerifier
            );
        }

        return new CallbackToOneRelationshipUpdatability(
            $this->updateEntityConditions,
            $this->updateRelationshipConditions,
            $this->relationship['relationshipType'],
            $this->customUpdateCallback,
            $this->entityVerifier
        );
    }

    /**
     * @return ToManyRelationshipUpdatabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToManyRelationshipUpdatability(): ?ToManyRelationshipUpdatabilityInterface
    {
        if (!$this->updatable || null === $this->relationship || !$this->relationship['toMany']) {
            return null;
        }

        if (null === $this->customUpdateCallback) {
            return new PathToManyRelationshipUpdatability(
                $this->entityClass,
                $this->updateEntityConditions,
                $this->updateRelationshipConditions,
                $this->relationship['relationshipType'],
                $this->getPropertyPath(),
                $this->propertyAccessor,
                $this->entityVerifier
            );
        }

        return new CallbackToManyRelationshipUpdatability(
            $this->updateEntityConditions,
            $this->updateRelationshipConditions,
            $this->relationship['relationshipType'],
            $this->customUpdateCallback,
            $this->entityVerifier
        );
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function getPropertyPath(): array
    {
        return $this->aliasedPath ?? [$this->name];
    }
}
