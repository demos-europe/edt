<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\Properties\Attributes\CallbackAttributeInitializability;
use EDT\JsonApi\Properties\Attributes\CallbackAttributeReadability;
use EDT\JsonApi\Properties\Attributes\CallbackAttributeSetability;
use EDT\JsonApi\Properties\Attributes\PathAttributeInitializability;
use EDT\JsonApi\Properties\Attributes\PathAttributeReadability;
use EDT\JsonApi\Properties\Attributes\PathAttributeSetability;
use EDT\JsonApi\Properties\Id\PathIdReadability;
use EDT\JsonApi\Properties\PropertyConfigInterface;
use EDT\JsonApi\Properties\Relationships\CallbackToManyRelationshipInitializability;
use EDT\JsonApi\Properties\Relationships\CallbackToManyRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\CallbackToManyRelationshipSetability;
use EDT\JsonApi\Properties\Relationships\CallbackToOneRelationshipInitializability;
use EDT\JsonApi\Properties\Relationships\CallbackToOneRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\CallbackToOneRelationshipSetability;
use EDT\JsonApi\Properties\Relationships\PathToManyRelationshipInitializability;
use EDT\JsonApi\Properties\Relationships\PathToManyRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\PathToManyRelationshipSetability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipInitializability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipSetability;
use EDT\JsonApi\RequestHandling\ContentField;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;
use EDT\Wrapping\Properties\AttributeInitializabilityInterface;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;
use EDT\Wrapping\Properties\AttributeSetabilityInterface;
use EDT\Wrapping\Properties\ConstructorParameter;
use EDT\Wrapping\Properties\ConstructorParameterInterface;
use EDT\Wrapping\Properties\IdReadabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipInitializabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipSetabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipInitializabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipSetabilityInterface;
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

    protected bool $initializabilityOptional = true;

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
     * @var null|callable(TEntity, TValue): bool
     */
    private $customUpdateCallback;

    /**
     * @var null|callable(TEntity, TValue): bool
     */
    private mixed $customInitCallback = null;

    /**
     * @var list<TCondition>
     */
    private array $initializeRelationshipConditions = [];

    /**
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
    public function initializable(bool $optional = false, array $relationshipConditions = [], callable $customInitCallback = null): self
    {
        $this->initializable = true;
        $this->initializabilityOptional = $optional;
        $this->customInitCallback = $customInitCallback;
        $this->initializeRelationshipConditions = $relationshipConditions;

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

    public function isToOneRelationship(): bool
    {
        return !$this->isAttribute() && !$this->isToManyRelationship();
    }

    public function isToManyRelationship(): bool
    {
        return $this->relationship['toMany'] ?? false;
    }

    /**
     * @return AttributeSetabilityInterface<TCondition, TEntity>|null
     */
    public function getAttributeUpdatability(): ?AttributeSetabilityInterface
    {
        if (!$this->updatable || null !== $this->relationship) {
            return null;
        }

        if (null === $this->customUpdateCallback) {
            return new PathAttributeSetability(
                $this->entityClass,
                $this->updateEntityConditions,
                $this->getPropertyPath(),
                $this->propertyAccessor
            );
        }

        return new CallbackAttributeSetability(
            $this->updateEntityConditions,
            $this->customUpdateCallback
        );
    }

    /**
     * @return ToOneRelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToOneRelationshipUpdatability(): ?ToOneRelationshipSetabilityInterface
    {
        if (!$this->updatable || null === $this->relationship || $this->relationship['toMany']) {
            return null;
        }

        if (null === $this->customUpdateCallback) {
            return new PathToOneRelationshipSetability(
                $this->entityClass,
                $this->updateEntityConditions,
                $this->updateRelationshipConditions,
                $this->relationship['relationshipType'],
                $this->getPropertyPath(),
                $this->propertyAccessor
            );
        }

        return new CallbackToOneRelationshipSetability(
            $this->updateEntityConditions,
            $this->updateRelationshipConditions,
            $this->relationship['relationshipType'],
            $this->customUpdateCallback
        );
    }

    /**
     * @return ToManyRelationshipSetabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToManyRelationshipUpdatability(): ?ToManyRelationshipSetabilityInterface
    {
        if (!$this->updatable || null === $this->relationship || !$this->relationship['toMany']) {
            return null;
        }

        if (null === $this->customUpdateCallback) {
            return new PathToManyRelationshipSetability(
                $this->entityClass,
                $this->updateEntityConditions,
                $this->updateRelationshipConditions,
                $this->relationship['relationshipType'],
                $this->getPropertyPath(),
                $this->propertyAccessor
            );
        }

        return new CallbackToManyRelationshipSetability(
            $this->updateEntityConditions,
            $this->updateRelationshipConditions,
            $this->relationship['relationshipType'],
            $this->customUpdateCallback
        );
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function getPropertyPath(): array
    {
        return $this->aliasedPath ?? [$this->name];
    }

    /**
     * @return AttributeInitializabilityInterface<TCondition, TEntity>|null
     */
    public function getAttributeInitializability(): ?AttributeInitializabilityInterface
    {
        if (!$this->initializable || null !== $this->relationship) {
            return null;
        }

        $initializability = null === $this->customInitCallback
            ? new PathAttributeInitializability(
                $this->entityClass,
                [],
                $this->getPropertyPath(),
                $this->propertyAccessor
            )
            : new CallbackAttributeInitializability(
                [],
                $this->customInitCallback
            );

        $initializability->setOptional($this->initializabilityOptional);

        return $initializability;
    }

    /**
     * @return ToOneRelationshipInitializabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToOneRelationshipInitializability(): ?ToOneRelationshipInitializabilityInterface
    {
        if (!$this->initializable || null === $this->relationship || $this->relationship['toMany']) {
            return null;
        }

        $initializability = null === $this->customInitCallback
            ? new PathToOneRelationshipInitializability(
                $this->entityClass,
                [],
                $this->initializeRelationshipConditions,
                $this->relationship['relationshipType'],
                $this->getPropertyPath(),
                $this->propertyAccessor
            )
            :  new CallbackToOneRelationshipInitializability(
                [],
                $this->initializeRelationshipConditions,
                $this->relationship['relationshipType'],
                $this->customInitCallback
            );

        $initializability->setOptional($this->initializabilityOptional);

        return $initializability;
    }

    /**
     * @return ToManyRelationshipInitializabilityInterface<TCondition, TSorting, TEntity, object>|null
     */
    public function getToManyRelationshipInitializability(): ?ToManyRelationshipInitializabilityInterface
    {
        if (!$this->initializable || null === $this->relationship || !$this->relationship['toMany']) {
            return null;
        }

        $initializability = null === $this->customInitCallback
            ? new PathToManyRelationshipInitializability(
                $this->entityClass,
                [],
                $this->initializeRelationshipConditions,
                $this->relationship['relationshipType'],
                $this->getPropertyPath(),
                $this->propertyAccessor
            )
            : new CallbackToManyRelationshipInitializability(
                [],
                $this->initializeRelationshipConditions,
                $this->relationship['relationshipType'],
                $this->customInitCallback
            );

        $initializability->setOptional($this->initializabilityOptional);

        return $initializability;
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

    /**
     * @return ConstructorParameterInterface<TCondition, TSorting>|null
     */
    public function getRequiredConstructorParameter(): ?ConstructorParameterInterface
    {
        return $this->initializabilityOptional ? null : $this->createConstructorParameter();

    }

    /**
     * @return ConstructorParameterInterface<TCondition, TSorting>|null
     */
    public function getOptionalConstructorParameter(): ?ConstructorParameterInterface
    {
        return !$this->initializabilityOptional ? null : $this->createConstructorParameter();
    }

    /**
     * @return ConstructorParameterInterface<TCondition, TSorting>
     */
    protected function createConstructorParameter(): ConstructorParameterInterface
    {
        if (null === $this->relationship) {
            return new ConstructorParameter(null);
        }

        return new ConstructorParameter([
            'toMany' => $this->relationship['toMany'],
            'relationshipType' => $this->relationship['relationshipType'],
            'conditions' => [],
        ]);
    }
}
